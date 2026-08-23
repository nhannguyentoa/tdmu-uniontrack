<?php

namespace Database\Factories;

use App\Models\UnionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnionGroup>
 */
class UnionGroupFactory extends Factory
{
    protected static int $sequence = 1;

    public function definition(): array
    {
        $number = self::$sequence++;

        return [
            'code' => 'TCD-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'name' => 'Tổ Công đoàn '.$this->faker->randomElement([
                'Khoa Công nghệ Thông tin', 'Khoa Kinh tế', 'Khoa Ngoại ngữ', 'Khoa Xây dựng',
                'Khoa Kỹ thuật - Công nghệ', 'Khoa Sư phạm', 'Khoa Luật', 'Phòng Đào tạo',
                'Phòng Công tác Sinh viên', 'Trung tâm Ngoại ngữ - Tin học',
            ]).' '.$number,
            'department' => $this->faker->randomElement(['Khoa CNTT', 'Khoa Kinh tế', 'Khoa Ngoại ngữ', 'Phòng Đào tạo', 'Phòng TC-HC']),
            'leader_name' => $this->faker->name(),
            'leader_phone' => '09'.$this->faker->numerify('########'),
            'leader_email' => $this->faker->unique()->safeEmail(),
            'established_date' => $this->faker->dateTimeBetween('-10 years', '-1 year'),
            'status' => 'active',
            'description' => $this->faker->sentence(12),
        ];
    }
}
