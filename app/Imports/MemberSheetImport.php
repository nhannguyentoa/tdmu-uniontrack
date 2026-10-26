<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Chỉ dùng để đọc thô các ô của tệp danh sách đoàn viên; MemberImportService tự dò dòng tiêu đề và kiểm tra dữ liệu.
 */
class MemberSheetImport implements ToArray
{
    public function array(array $array): void {}
}
