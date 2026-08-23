<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected static int $sequence = 1;

    protected array $names = [
        'Hội thao truyền thống', 'Giải bóng đá công đoàn', 'Hội thi văn nghệ chào mừng',
        'Chương trình hiến máu nhân đạo', 'Thăm hỏi đoàn viên có hoàn cảnh khó khăn',
        'Tọa đàm tuyên truyền pháp luật lao động', 'Chuyến về nguồn tri ân', 'Hội trại truyền thống',
        'Chương trình Trung thu cho con em đoàn viên', 'Tập huấn kỹ năng nghiệp vụ công đoàn',
        'Ngày hội gia đình đoàn viên', 'Chương trình thiện nguyện vùng khó khăn',
    ];

    public function definition(): array
    {
        $number = self::$sequence++;
        $start = $this->faker->dateTimeBetween('-6 months', '+3 months');
        $end = (clone $start)->modify('+'.$this->faker->numberBetween(2, 8).' hours');

        return [
            'code' => 'HD-'.now()->year.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'name' => $this->faker->randomElement($this->names).' '.now()->year,
            'union_group_id' => UnionGroup::factory(),
            'activity_type_id' => ActivityType::factory(),
            'responsible_name' => $this->faker->name(),
            'start_time' => $start,
            'end_time' => $end,
            'location' => $this->faker->randomElement(['Hội trường A', 'Sân vận động trường', 'Khu hiệu bộ', 'Nhà văn hóa sinh viên', 'Trực tuyến (Online)']),
            'content' => $this->faker->paragraph(4),
            'goal' => $this->faker->sentence(15),
            'expected_quantity' => $this->faker->numberBetween(20, 150),
            'actual_quantity' => 0,
            'status' => Activity::STATUS_NOT_STARTED,
            'progress' => 0,
            'budget' => $this->faker->randomElement([null, 2000000, 5000000, 10000000, 15000000]),
            'note' => null,
        ];
    }

    public function notStarted(): static
    {
        return $this->state(function () {
            $start = $this->faker->dateTimeBetween('+1 week', '+3 months');

            return [
                'start_time' => $start,
                'end_time' => (clone $start)->modify('+4 hours'),
                'status' => Activity::STATUS_NOT_STARTED,
                'progress' => 0,
                'actual_quantity' => 0,
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(function () {
            $start = $this->faker->dateTimeBetween('-2 weeks', 'now');

            return [
                'start_time' => $start,
                'end_time' => (clone $start)->modify('+'.$this->faker->numberBetween(1, 4).' weeks'),
                'status' => $this->faker->randomElement([Activity::STATUS_PREPARING, Activity::STATUS_IN_PROGRESS]),
                'progress' => $this->faker->numberBetween(10, 90),
                'actual_quantity' => 0,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function () {
            $start = $this->faker->dateTimeBetween('-6 months', '-1 week');

            return [
                'start_time' => $start,
                'end_time' => (clone $start)->modify('+'.$this->faker->numberBetween(2, 8).' hours'),
                'status' => Activity::STATUS_COMPLETED,
                'progress' => 100,
                'actual_quantity' => $this->faker->numberBetween(20, 150),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(function () {
            $start = $this->faker->dateTimeBetween('-3 months', '+1 month');

            return [
                'start_time' => $start,
                'end_time' => (clone $start)->modify('+4 hours'),
                'status' => Activity::STATUS_CANCELLED,
                'progress' => $this->faker->numberBetween(0, 40),
                'actual_quantity' => 0,
                'note' => 'Hoạt động bị hủy do điều kiện khách quan.',
            ];
        });
    }
}
