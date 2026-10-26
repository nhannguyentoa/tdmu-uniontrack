<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ActivityExportTest extends TestCase
{
    use RefreshDatabase;

    private function load(TestResponse $response): Spreadsheet
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        copy($response->baseResponse->getFile()->getPathname(), $tmp);
        $book = IOFactory::load($tmp);
        @unlink($tmp);

        return $book;
    }

    /** @return array{0: UnionGroup, 1: UnionGroup} */
    private function seedActivities(): array
    {
        $a = UnionGroup::factory()->create(['name' => 'Công đoàn A']);
        $b = UnionGroup::factory()->create(['name' => 'Công đoàn B']);
        $type = ActivityType::factory()->create(['name' => 'Hoạt động thể thao']);

        $base = ['activity_type_id' => $type->id, 'expected_quantity' => 10, 'actual_quantity' => 0];
        Activity::factory()->create($base + ['code' => 'HD-1', 'name' => 'Giải bóng đá', 'union_group_id' => $a->id,
            'start_time' => '2026-09-01 08:00', 'end_time' => '2026-09-01 10:00', 'status' => 'not_started', 'progress' => 0]);
        Activity::factory()->create($base + ['code' => 'HD-2', 'name' => ' giải BÓNG  đá', 'union_group_id' => $a->id,
            'start_time' => '2026-10-01 08:00', 'end_time' => '2026-10-01 10:00', 'status' => 'completed', 'progress' => 100]);
        Activity::factory()->create($base + ['code' => 'HD-3', 'name' => 'Giải bóng đá', 'union_group_id' => $b->id,
            'start_time' => '2026-11-01 08:00', 'end_time' => '2026-11-01 10:00', 'status' => 'cancelled', 'progress' => 30]);

        return [$a, $b];
    }

    public function test_export_has_five_sheets_with_merged_headers_dates_and_zero_values(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Người xuất']);
        $this->seedActivities();

        $response = $this->actingAs($admin)->get('/activities/export');
        $response->assertOk();
        $this->assertMatchesRegularExpression('/danh-sach-hoat-dong_\d{4}-\d{2}-\d{2}_\d{4}\.xlsx/', $response->headers->get('Content-Disposition'));

        $book = $this->load($response);
        $this->assertSame(['Bảng 2 chiều', 'Theo tổ công đoàn', 'Tổng hợp', 'Dữ liệu chi tiết', 'Ghi chú'], $book->getSheetNames());

        $matrix = $book->getSheetByName('Bảng 2 chiều');
        $this->assertSame('Công đoàn A', $matrix->getCell('B4')->getValue());
        $this->assertContains('B4:C4', $matrix->getMergeCells());
        $this->assertSame('Giải bóng đá', $matrix->getCell('A6')->getValue()); // 3 lần ở 2 tổ gom thành 1 hàng
        $this->assertSame(2, $matrix->getCell('B6')->getValue());
        $this->assertSame(1, $matrix->getCell('D6')->getValue());
        $this->assertSame('Tổng theo tổ', $matrix->getCell('A7')->getValue());

        $byGroup = $book->getSheetByName('Theo tổ công đoàn');
        $this->assertSame('Công đoàn A', $byGroup->getCell('A6')->getValue());
        $this->assertContains('A6:A7', $byGroup->getMergeCells()); // 1 dòng đã gom + dòng Cộng tổ
        $this->assertSame(2, $byGroup->getCell('D6')->getValue());
        $this->assertSame("HD-1\nHD-2", $byGroup->getCell('E6')->getValue());
        $this->assertSame('Cộng tổ', $byGroup->getCell('C7')->getValue());
        $this->assertTrue(Date::isDateTime($byGroup->getCell('G6')));

        $detail = $book->getSheetByName('Dữ liệu chi tiết');
        $this->assertSame('HD-1', $detail->getCell('A2')->getValue());
        $this->assertSame(0, $detail->getCell('I2')->getValue()); // tiến độ 0 không bị mất
        $this->assertTrue(Date::isDateTime($detail->getCell('E2')));
        $this->assertSame('A1:O4', $detail->getAutoFilter()->getRange());
        $this->assertSame("'Đã hủy' nhưng tiến độ 30%", $detail->getCell('O4')->getValue());

        $notes = $book->getSheetByName('Ghi chú');
        $text = implode("\n", array_map(fn ($r) => (string) $r[0], $notes->toArray()));
        $this->assertStringContainsString('Người xuất: Người xuất', $text);
        $this->assertStringContainsString('Bộ lọc: Tất cả', $text);
    }

    public function test_union_group_filter_keeps_only_that_group_and_is_described_in_row_two(): void
    {
        $admin = User::factory()->admin()->create();
        [$a, $b] = $this->seedActivities();

        $book = $this->load($this->actingAs($admin)->get('/activities/export?union_group_id='.$b->id));

        $matrix = $book->getSheetByName('Bảng 2 chiều');
        $this->assertSame('Công đoàn B', $matrix->getCell('B4')->getValue());
        $this->assertSame('Toàn trường', $matrix->getCell('D4')->getValue());
        $this->assertStringContainsString('Tổ: Công đoàn B', $matrix->getCell('A2')->getValue());

        $detail = $book->getSheetByName('Dữ liệu chi tiết');
        $this->assertSame('HD-3', $detail->getCell('A2')->getValue());
        $this->assertNull($detail->getCell('A3')->getValue());
    }

    public function test_text_that_looks_like_a_formula_is_written_as_plain_text(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        Activity::factory()->create(['name' => '=1+1', 'union_group_id' => $group->id]);

        $detail = $this->load($this->actingAs($admin)->get('/activities/export'))->getSheetByName('Dữ liệu chi tiết');

        $this->assertSame('=1+1', $detail->getCell('B2')->getValue());
        $this->assertSame('s', $detail->getCell('B2')->getDataType());
    }
}
