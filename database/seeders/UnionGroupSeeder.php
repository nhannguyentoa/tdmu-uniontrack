<?php

namespace Database\Seeders;

use App\Models\UnionGroup;
use Illuminate\Database\Seeder;

class UnionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['code' => 'TCD-CNTT', 'name' => 'Tổ Công đoàn Khoa Công nghệ Thông tin', 'department' => 'Khoa Công nghệ Thông tin'],
            ['code' => 'TCD-KT', 'name' => 'Tổ Công đoàn Khoa Kinh tế', 'department' => 'Khoa Kinh tế'],
            ['code' => 'TCD-NN', 'name' => 'Tổ Công đoàn Khoa Ngoại ngữ', 'department' => 'Khoa Ngoại ngữ'],
            ['code' => 'TCD-XD', 'name' => 'Tổ Công đoàn Khoa Kỹ thuật - Xây dựng', 'department' => 'Khoa Kỹ thuật - Xây dựng'],
            ['code' => 'TCD-SP', 'name' => 'Tổ Công đoàn Khoa Sư phạm', 'department' => 'Khoa Sư phạm'],
            ['code' => 'TCD-DT', 'name' => 'Tổ Công đoàn Phòng Đào tạo', 'department' => 'Phòng Đào tạo'],
            ['code' => 'TCD-CTSV', 'name' => 'Tổ Công đoàn Phòng Công tác Sinh viên', 'department' => 'Phòng Công tác Sinh viên'],
        ];

        foreach ($groups as $index => $group) {
            UnionGroup::create(array_merge($group, [
                'leader_name' => fake('vi_VN')->name(),
                'leader_phone' => '09'.fake()->numerify('########'),
                'leader_email' => 'to'.($index + 1).'@tdmu.edu.vn',
                'established_date' => now()->subYears(5 + $index),
                'status' => 'active',
                'description' => 'Tổ công đoàn trực thuộc Công đoàn Trường Đại học Thủ Dầu Một, phụ trách '.$group['department'].'.',
            ]));
        }

        // Một tổ ngưng hoạt động để kiểm thử bộ lọc trạng thái.
        UnionGroup::create([
            'code' => 'TCD-TT01',
            'name' => 'Tổ Công đoàn Trung tâm Ngoại ngữ - Tin học (đã giải thể)',
            'department' => 'Trung tâm Ngoại ngữ - Tin học',
            'leader_name' => fake('vi_VN')->name(),
            'leader_phone' => '09'.fake()->numerify('########'),
            'leader_email' => 'to.giaithe@tdmu.edu.vn',
            'established_date' => now()->subYears(10),
            'status' => 'inactive',
            'description' => 'Tổ công đoàn đã ngừng hoạt động do sáp nhập đơn vị.',
        ]);
    }
}
