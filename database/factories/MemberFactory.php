<?php

namespace Database\Factories;

use App\Models\UnionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected static int $sequence = 1;

    public function definition(): array
    {
        $number = self::$sequence++;
        $gender = $this->faker->randomElement(['male', 'female']);

        return [
            'code' => 'DV-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
            'full_name' => $gender === 'male' ? $this->faker->name('male') : $this->faker->name('female'),
            'dob' => $this->faker->dateTimeBetween('-45 years', '-22 years'),
            'gender' => $gender,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '09'.$this->faker->numerify('########'),
            'position' => $this->faker->randomElement(['Giảng viên', 'Chuyên viên', 'Nhân viên', 'Trưởng bộ môn', 'Tổ trưởng công đoàn', 'Giáo vụ']),
            'department' => $this->faker->randomElement(['Khoa CNTT', 'Khoa Kinh tế', 'Khoa Ngoại ngữ', 'Phòng Đào tạo', 'Phòng TC-HC']),
            'union_group_id' => UnionGroup::factory(),
            'joined_union_date' => $this->faker->dateTimeBetween('-15 years', '-1 year'),
            'status' => 'active',
            'note' => null,
        ];
    }
}
