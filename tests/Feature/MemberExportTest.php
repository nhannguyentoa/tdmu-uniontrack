<?php

namespace Tests\Feature;

use App\Exports\Members\MemberGroupSheet;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MemberExportTest extends TestCase
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
    private function seedMembers(): array
    {
        $b = UnionGroup::factory()->create(['name' => 'Công đoàn B']);
        $a = UnionGroup::factory()->create(['name' => 'Công đoàn A']);
        Member::factory()->create(['union_group_id' => $a->id, 'full_name' => 'Trần Văn Bình', 'code' => 'DV-2',
            'phone' => '0912345678', 'dob' => '1990-03-05', 'status' => 'active']);
        Member::factory()->create(['union_group_id' => $a->id, 'full_name' => 'Lê Thị An', 'code' => 'DV-1', 'status' => 'inactive']);
        Member::factory()->create(['union_group_id' => $b->id, 'full_name' => 'Phạm Văn Cường', 'code' => 'DV-3', 'status' => 'active']);

        return [$a, $b];
    }

    public function test_each_union_group_gets_its_own_sheet_followed_by_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedMembers();

        $response = $this->actingAs($admin)->get('/members/export');
        $response->assertOk();
        $this->assertMatchesRegularExpression('/danh-sach-doan-vien_\d{4}-\d{2}-\d{2}_\d{4}\.xlsx/', $response->headers->get('Content-Disposition'));

        $book = $this->load($response);
        $this->assertSame(['Công đoàn A', 'Công đoàn B', 'Tổng hợp'], $book->getSheetNames());

        $a = $book->getSheetByName('Công đoàn A');
        $this->assertSame('DANH SÁCH ĐOÀN VIÊN – CÔNG ĐOÀN A', $a->getCell('A1')->getValue());
        $this->assertSame('Mã đoàn viên', $a->getCell('B4')->getValue());
        $this->assertSame('Lê Thị An', $a->getCell('C5')->getValue()); // sắp theo họ tên
        $this->assertSame('Trần Văn Bình', $a->getCell('C6')->getValue());
        $this->assertNull($a->getCell('C7')->getValue());
        $this->assertSame('0912345678', $a->getCell('H6')->getValue());
        $this->assertTrue(Date::isDateTime($a->getCell('D6')));
        $this->assertSame('Ngưng hoạt động', $a->getCell('K5')->getValue());
        $this->assertSame('A4:L6', $a->getAutoFilter()->getRange());

        $summary = $book->getSheetByName('Tổng hợp');
        $this->assertSame('Công đoàn A', $summary->getCell('B5')->getValue());
        $this->assertSame(2, $summary->getCell('C5')->getValue());
        $this->assertSame(1, $summary->getCell('E5')->getValue());
        $this->assertSame('TỔNG CỘNG', $summary->getCell('A7')->getValue());
    }

    public function test_officer_only_gets_sheets_of_managed_groups_and_filter_is_described(): void
    {
        [$a] = $this->seedMembers();
        $officer = User::factory()->officer()->create();
        $officer->managedUnionGroups()->attach($a);

        $book = $this->load($this->actingAs($officer)->get('/members/export?status=active'));

        $this->assertSame(['Công đoàn A', 'Tổng hợp'], $book->getSheetNames());
        $sheet = $book->getSheetByName('Công đoàn A');
        $this->assertSame('Trần Văn Bình', $sheet->getCell('C5')->getValue());
        $this->assertNull($sheet->getCell('C6')->getValue());
        $this->assertStringContainsString('Trạng thái: Đang hoạt động', $sheet->getCell('A2')->getValue());
    }

    public function test_no_members_gives_single_explanatory_sheet(): void
    {
        $admin = User::factory()->admin()->create();

        $book = $this->load($this->actingAs($admin)->get('/members/export'));

        $this->assertSame(['Danh sách đoàn viên'], $book->getSheetNames());
        $this->assertStringContainsString('Không có đoàn viên nào', $book->getSheet(0)->getCell('A2')->getValue());
    }

    public function test_long_or_invalid_group_names_become_valid_unique_sheet_titles(): void
    {
        $used = ['tổng hợp'];

        $first = MemberGroupSheet::safeTitle('Tổ Công đoàn Khoa Kỹ thuật / Công nghệ thông tin', $used);
        $second = MemberGroupSheet::safeTitle('Tổ Công đoàn Khoa Kỹ thuật / Công nghệ thông tin', $used);

        $this->assertLessThanOrEqual(31, mb_strlen($first));
        $this->assertStringNotContainsString('/', $first);
        $this->assertNotSame($first, $second);
        $this->assertStringEndsWith('(2)', $second);
    }
}
