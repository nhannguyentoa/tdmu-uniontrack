<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'TTTD', 'name' => 'Ban Thi đua - Khen thưởng', 'description' => 'Phụ trách xây dựng tổ chức, phát triển đảng, chế độ báo cáo và các cuộc thi chính trị - tư tưởng.'],
            ['code' => 'NCDS', 'name' => 'Ban Nữ công - Đời sống', 'description' => 'Phụ trách chăm lo đời sống đoàn viên, hoạt động vì sự tiến bộ phụ nữ, các tọa đàm sức khỏe - gia đình.'],
            ['code' => 'HCHC', 'name' => 'Ban Hành chính - Hậu cần', 'description' => 'Phụ trách thông tin nội bộ, hậu cần sự kiện, hiến máu nhân đạo và các hoạt động chăm lo hậu cần khác.'],
            ['code' => 'PT', 'name' => 'Ban Phong trào', 'description' => 'Phụ trách phong trào văn - thể - mỹ, hội thao, hội thi và các hoạt động phong trào toàn trường.'],
            ['code' => 'TT', 'name' => 'Ban Truyền thông', 'description' => 'Phụ trách công tác tuyên truyền, các cuộc thi trực tuyến và truyền thông hoạt động công đoàn.'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
