<?php

namespace Database\Seeders;

use App\Models\UnionGroup;
use Illuminate\Database\Seeder;

class UnionGroupSeeder extends Seeder
{
    /**
     * Danh sách 16 tổ Công đoàn thật của Công đoàn Cơ sở Trường Đại học Thủ Dầu Một
     * (trích từ "DS các tổ CĐ của công đoàn trường.xlsx" và bảng thẩm định thi đua 2025-2026).
     */
    public static function realGroups(): array
    {
        return [
            ['code' => 'CD1', 'name' => 'Công đoàn 1', 'department' => null],
            ['code' => 'CD2', 'name' => 'Công đoàn 2', 'department' => null],
            ['code' => 'CD3', 'name' => 'Công đoàn 3', 'department' => null],
            ['code' => 'CD4', 'name' => 'Công đoàn 4', 'department' => null],
            ['code' => 'KCNVH', 'name' => 'Khoa Công nghiệp Văn hóa', 'department' => 'Khoa Công nghiệp Văn hóa'],
            ['code' => 'KNN', 'name' => 'Khoa Ngoại ngữ', 'department' => 'Khoa Ngoại ngữ'],
            ['code' => 'KKTXD', 'name' => 'Khoa Kiến trúc - Xây dựng', 'department' => 'Khoa Kiến trúc - Xây dựng'],
            ['code' => 'VKTCN', 'name' => 'Viện Kỹ thuật Công nghệ', 'department' => 'Viện Kỹ thuật Công nghệ'],
            ['code' => 'VCNS', 'name' => 'Viện Công nghệ số', 'department' => 'Viện Công nghệ số'],
            ['code' => 'VCNXBV', 'name' => 'Viện Công nghệ xanh và bền vững', 'department' => 'Viện Công nghệ xanh và bền vững'],
            ['code' => 'TLQL', 'name' => 'Trường Luật và Quản lý', 'department' => 'Trường Luật và Quản lý'],
            ['code' => 'SP1', 'name' => 'Sư phạm 1', 'department' => null],
            ['code' => 'SP2', 'name' => 'Sư phạm 2', 'department' => null],
            ['code' => 'KTTC1', 'name' => 'Kinh tế tài chính 1', 'department' => null],
            ['code' => 'KTTC2', 'name' => 'Kinh tế tài chính 2', 'department' => null],
            ['code' => 'KTTC3', 'name' => 'Kinh tế tài chính 3', 'department' => null],
        ];
    }

    public function run(): void
    {
        foreach (self::realGroups() as $group) {
            UnionGroup::updateOrCreate(
                ['code' => $group['code']],
                array_merge($group, [
                    'status' => 'active',
                    'description' => 'Tổ công đoàn trực thuộc Công đoàn Cơ sở Trường Đại học Thủ Dầu Một.',
                ])
            );
        }
    }
}
