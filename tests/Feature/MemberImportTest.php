<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MemberImportTest extends TestCase
{
    use RefreshDatabase;

    protected function officerFor(UnionGroup $group): User
    {
        $officer = User::factory()->officer()->create();
        $officer->managedUnionGroups()->attach($group);

        return $officer;
    }

    /** @param  list<list<mixed>>  $rows */
    protected function excel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'dv').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'danh-sach.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_officer_sees_import_page_and_template(): void
    {
        $group = UnionGroup::factory()->create();
        $officer = $this->officerFor($group);

        $this->actingAs($officer)->get('/members')->assertSee('Nhập từ Excel');
        $this->actingAs($officer)->get('/members/import')->assertOk()->assertSee($group->name);
        $this->actingAs($officer)->get('/members/import/template')->assertOk()->assertDownload('mau-nhap-doan-vien.xlsx');
    }

    public function test_preview_classifies_rows_without_saving(): void
    {
        $group = UnionGroup::factory()->create();
        $officer = $this->officerFor($group);
        Member::factory()->create(['code' => 'DV-00007', 'union_group_id' => $group->id]);

        $file = $this->excel([
            ['DANH SÁCH ĐOÀN VIÊN CÔNG ĐOÀN KHOA CNTT'],
            ['STT', 'Mã đoàn viên', 'Họ và tên', 'Ngày sinh', 'Giới tính', 'Số điện thoại', 'Trạng thái'],
            [1, 'DV-00100', 'Nguyễn Văn An', '05/03/1990', 'Nam', '912345678', 'Đang hoạt động'],
            [2, 'DV-00007', 'Trần Thị Bình', '', 'Nữ', '', ''],
            [3, '', 'Lê Văn Cường', '31/02/1990', 'Nam', '', ''],
            [4, '', '', '', '', '', ''],
            [5, '', 'Phạm Thị Dung', '', 'Không rõ', '', ''],
        ]);

        $response = $this->actingAs($officer)->post('/members/import', ['union_group_id' => $group->id, 'file' => $file]);

        $response->assertOk()
            ->assertSee('Nguyễn Văn An')
            ->assertSee('1 hợp lệ')
            ->assertSee('1 trùng mã')
            ->assertSee('2 lỗi')
            ->assertSee('Mã đoàn viên đã có trong hệ thống.');

        $rows = collect(session('member_import.rows'))->keyBy('line');
        $this->assertSame('valid', $rows[3]['status']);
        $this->assertSame('1990-03-05', $rows[3]['data']['dob']);
        $this->assertSame('0912345678', $rows[3]['data']['phone']);
        $this->assertSame('male', $rows[3]['data']['gender']);
        $this->assertSame('duplicate', $rows[4]['status']);
        $this->assertSame('error', $rows[5]['status']);
        $this->assertSame('error', $rows[7]['status']);
        $this->assertFalse($rows->has(6));
        $this->assertDatabaseMissing('members', ['full_name' => 'Nguyễn Văn An']);
    }

    public function test_confirm_adds_selected_rows_and_generates_codes(): void
    {
        $group = UnionGroup::factory()->create();
        $officer = $this->officerFor($group);
        Member::factory()->create(['code' => 'DV-00041', 'union_group_id' => $group->id]);

        $file = $this->excel([
            ['Mã đoàn viên', 'Họ và tên', 'Email'],
            ['', 'Nguyễn Văn An', 'an@tdmu.edu.vn'],
            ['', 'Trần Thị Bình', ''],
            ['DV-09000', 'Lê Văn Cường', ''],
        ]);

        $this->actingAs($officer)->post('/members/import', ['union_group_id' => $group->id, 'file' => $file])->assertOk();

        $this->actingAs($officer)->post('/members/import/confirm', ['lines' => [2, 4]])
            ->assertRedirect(route('members.index', ['union_group_id' => $group->id]))
            ->assertSessionHas('success', 'Đã thêm 2 đoàn viên từ tệp Excel.');

        $this->assertDatabaseHas('members', ['full_name' => 'Nguyễn Văn An', 'code' => 'DV-00042', 'union_group_id' => $group->id, 'status' => 'active']);
        $this->assertDatabaseHas('members', ['full_name' => 'Lê Văn Cường', 'code' => 'DV-09000']);
        $this->assertDatabaseMissing('members', ['full_name' => 'Trần Thị Bình']);
        $this->assertNull(session('member_import'));
    }

    public function test_officer_cannot_import_into_group_they_do_not_manage(): void
    {
        $own = UnionGroup::factory()->create();
        $other = UnionGroup::factory()->create();
        $officer = $this->officerFor($own);

        $file = $this->excel([['Họ và tên'], ['Nguyễn Văn An']]);

        $this->actingAs($officer)->post('/members/import', ['union_group_id' => $other->id, 'file' => $file])
            ->assertSessionHasErrors('union_group_id');
        $this->assertNull(session('member_import'));
    }

    public function test_confirm_rejects_when_officer_lost_group_after_preview(): void
    {
        $group = UnionGroup::factory()->create();
        $officer = $this->officerFor($group);

        $this->actingAs($officer)->post('/members/import', ['union_group_id' => $group->id, 'file' => $this->excel([['Họ và tên'], ['Nguyễn Văn An']])]);
        $officer->managedUnionGroups()->detach($group);

        $this->actingAs($officer)->post('/members/import/confirm', ['lines' => [2]])->assertForbidden();
        $this->assertDatabaseMissing('members', ['full_name' => 'Nguyễn Văn An']);
    }

    public function test_file_without_name_column_is_rejected(): void
    {
        $group = UnionGroup::factory()->create();
        $officer = $this->officerFor($group);

        $this->actingAs($officer)->post('/members/import', ['union_group_id' => $group->id, 'file' => $this->excel([['Cột A', 'Cột B'], ['x', 'y']])])
            ->assertSessionHasErrors('file');
    }

    public function test_exported_member_file_can_be_imported_back(): void
    {
        $source = UnionGroup::factory()->create();
        $target = UnionGroup::factory()->create();
        $admin = User::factory()->admin()->create();
        Member::factory()->create(['code' => 'DV-00001', 'full_name' => 'Đoàn Viên Cũ', 'union_group_id' => $source->id]);

        $export = $this->actingAs($admin)->get('/members/export');
        $path = tempnam(sys_get_temp_dir(), 'ex').'.xlsx';
        copy($export->baseResponse->getFile()->getPathname(), $path);

        $this->actingAs($admin)->post('/members/import', [
            'union_group_id' => $target->id,
            'file' => new UploadedFile($path, 'danh-sach-doan-vien.xlsx', null, null, true),
        ])->assertOk()->assertSee('1 trùng mã');
    }
}
