<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ActivityType>
 */
class ActivityTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Hoạt động văn hóa', 'Hoạt động thể thao', 'Hoạt động xã hội', 'Hoạt động thiện nguyện',
            'Hoạt động tuyên truyền', 'Hoạt động chăm lo đoàn viên', 'Hoạt động phong trào', 'Hoạt động khác',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }
}
