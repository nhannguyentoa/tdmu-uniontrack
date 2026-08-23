<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Hoạt động văn hóa',
            'Hoạt động thể thao',
            'Hoạt động xã hội',
            'Hoạt động thiện nguyện',
            'Hoạt động tuyên truyền',
            'Hoạt động chăm lo đoàn viên',
            'Hoạt động phong trào',
            'Hoạt động khác',
        ];

        foreach ($types as $name) {
            ActivityType::create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::random(4),
                'description' => "Nhóm các hoạt động thuộc loại: {$name}.",
                'is_active' => true,
            ]);
        }
    }
}
