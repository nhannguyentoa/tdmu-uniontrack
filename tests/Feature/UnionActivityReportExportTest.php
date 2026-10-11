<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class UnionActivityReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function load(TestResponse $response): Worksheet
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        file_put_contents($tmp, $response->streamedContent());
        $sheet = IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        return $sheet;
    }

    public function test_report_is_grouped_by_activity_type_and_filtered_by_academic_year(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        // Thứ tự nhóm trong file xuất theo tên Loại hoạt động (A-Z): "thao" < "văn hóa".
        $sport = ActivityType::factory()->create(['name' => 'Hoạt động thể thao']);
        $culture = ActivityType::factory()->create(['name' => 'Hoạt động văn hóa']);

        Activity::factory()->create([
            'name' => 'Hội thao mùa thu', 'content' => 'Nội dung hội thao',
            'union_group_id' => $group->id, 'activity_type_id' => $sport->id,
            'start_time' => '2026-09-10 08:00', 'end_time' => '2026-09-10 08:00',
        ]);
        Activity::factory()->create([
            'name' => 'Hội diễn văn nghệ', 'content' => 'Nội dung văn nghệ',
            'union_group_id' => $group->id, 'activity_type_id' => $culture->id,
            'start_time' => '2026-11-01 08:00', 'end_time' => '2026-11-02 08:00',
        ]);
        // Ngoài năm học 2026-2027 (tháng 8/2026 đến tháng 7/2027) -> không được xuất.
        Activity::factory()->create([
            'name' => 'Hoạt động năm trước',
            'union_group_id' => $group->id, 'activity_type_id' => $sport->id,
            'start_time' => '2025-05-01 08:00', 'end_time' => '2025-05-01 08:00',
        ]);

        $response = $this->actingAs($admin)->get('/activities/export/union-report?academic_year=2026-2027');
        $response->assertOk();
        $this->assertStringContainsString('hoat-dong-cong-doan-2026-2027.xlsx', $response->headers->get('Content-Disposition'));

        $sheet = $this->load($response);

        $this->assertSame('Hoạt động thể thao', $sheet->getCell('A8')->getValue());
        $this->assertSame(1, $sheet->getCell('A9')->getValue());
        $this->assertSame('Hội thao mùa thu', $sheet->getCell('B9')->getValue());

        $this->assertSame('Hoạt động văn hóa', $sheet->getCell('A10')->getValue());
        $this->assertSame(2, $sheet->getCell('A11')->getValue());
        $this->assertSame('Hội diễn văn nghệ', $sheet->getCell('B11')->getValue());
        $this->assertSame('01/11/2026 - 02/11/2026', $sheet->getCell('D11')->getValue());

        // hoạt động năm trước không xuất hiện ở đâu trong file
        $this->assertStringNotContainsString('Hoạt động năm trước', $sheet->toArray() ? implode(' ', array_merge(...$sheet->toArray())) : '');
    }

    public function test_officer_cannot_export_union_report(): void
    {
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->get('/activities/export/union-report?academic_year=2026-2027')->assertForbidden();
    }

    public function test_officer_does_not_see_union_report_button(): void
    {
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->get('/activities')
            ->assertOk()
            ->assertDontSee('Xuất báo cáo');
    }

    public function test_admin_sees_union_report_button(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/activities')
            ->assertOk()
            ->assertSee('Xuất báo cáo');
    }

    public function test_invalid_academic_year_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/activities/export/union-report?academic_year=abc')->assertSessionHasErrors('academic_year');
    }

    public function test_exported_sheet_has_expected_layout_and_content(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create(['name' => 'Hoạt động thiện nguyện']);

        $activity = Activity::factory()->create([
            'name' => 'Hiến máu nhân đạo',
            'content' => 'Tổ chức hiến máu tình nguyện cho toàn thể đoàn viên.',
            'goal' => null,
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-10-05 08:00',
            'end_time' => '2026-10-05 11:00',
        ]);
        $activity->evidences()->create([
            'file_name' => 'minh-chung.jpg', 'file_path' => 'x/minh-chung.jpg', 'file_type' => 'jpg',
            'file_size' => 10, 'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/activities/export/union-report?academic_year=2026-2027');
        $response->assertOk();

        $sheet = $this->load($response);

        $this->assertSame('LIÊN ĐOÀN LAO ĐỘNG TP HỒ CHÍ MINH', $sheet->getCell('A1')->getValue());
        $this->assertSame('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', $sheet->getCell('D1')->getValue());
        $this->assertSame('HOẠT ĐỘNG CÔNG ĐOÀN', $sheet->getCell('A4')->getValue());
        $this->assertSame('NĂM HỌC 2026-2027', $sheet->getCell('A5')->getValue());
        $this->assertSame('STT', $sheet->getCell('A7')->getValue());
        $this->assertSame('HOẠT ĐỘNG', $sheet->getCell('B7')->getValue());

        $this->assertSame('Hoạt động thiện nguyện', $sheet->getCell('A8')->getValue());
        $this->assertSame(1, $sheet->getCell('A9')->getValue());
        $this->assertSame('Hiến máu nhân đạo', $sheet->getCell('B9')->getValue());
        $this->assertSame('Tổ chức hiến máu tình nguyện cho toàn thể đoàn viên.', $sheet->getCell('C9')->getValue());
        $this->assertSame('05/10/2026', $sheet->getCell('D9')->getValue());
        $this->assertSame('minh-chung.jpg', $sheet->getCell('E9')->getValue());
    }

    public function test_description_falls_back_to_goal_when_content_is_empty(): void
    {
        $admin = User::factory()->admin()->create();
        $type = ActivityType::factory()->create();
        Activity::factory()->create([
            'name' => 'Chỉ có mục tiêu',
            'content' => null,
            'goal' => 'Mục tiêu thay thế',
            'activity_type_id' => $type->id,
            'union_group_id' => UnionGroup::factory(),
            'start_time' => '2026-09-15 08:00',
            'end_time' => '2026-09-20 08:00',
        ]);

        $response = $this->actingAs($admin)->get('/activities/export/union-report?academic_year=2026-2027');
        $sheet = $this->load($response);

        $this->assertSame('Mục tiêu thay thế', $sheet->getCell('C9')->getValue());
        $this->assertSame('15/09/2026 - 20/09/2026', $sheet->getCell('D9')->getValue());
    }
}
