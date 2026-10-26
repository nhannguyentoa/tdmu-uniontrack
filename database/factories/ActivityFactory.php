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

    /**
     * Mô tả và mục tiêu tiếng Việt cho từng hoạt động mẫu.
     * (Faker không có bộ sinh văn bản tiếng Việt: paragraph()/sentence() luôn ra chữ Latin "lorem ipsum".)
     */
    protected const TEXTS = [
        'Hội thao truyền thống' => [
            'Tổ chức các nội dung thi đấu kéo co, chạy tiếp sức, cầu lông và bóng bàn cho đoàn viên trong tổ; trao giải cho các đội và cá nhân đạt thành tích cao.',
            'Rèn luyện sức khỏe, tăng cường giao lưu và tinh thần đoàn kết giữa các đoàn viên.',
        ],
        'Giải bóng đá công đoàn' => [
            'Tổ chức giải bóng đá mini giữa các đội của tổ công đoàn theo thể thức vòng tròn tính điểm; mời trọng tài và ban tổ chức điều hành các trận đấu.',
            'Tạo sân chơi thể thao lành mạnh, gắn kết đoàn viên và chào mừng các ngày lễ lớn của Nhà trường.',
        ],
        'Hội thi văn nghệ chào mừng' => [
            'Tổ chức hội thi văn nghệ với các tiết mục đơn ca, song ca, múa và hợp xướng do đoàn viên tự dàn dựng và biểu diễn.',
            'Chào mừng ngày lễ, phát huy năng khiếu văn nghệ và tạo không khí vui tươi trong đơn vị.',
        ],
        'Chương trình hiến máu nhân đạo' => [
            'Phối hợp với Hội Chữ thập đỏ tổ chức ngày hội hiến máu tình nguyện; vận động đoàn viên đăng ký, khám sàng lọc và hiến máu.',
            'Lan tỏa tinh thần tương thân tương ái, góp phần bổ sung nguồn máu cho công tác cấp cứu và điều trị.',
        ],
        'Thăm hỏi đoàn viên có hoàn cảnh khó khăn' => [
            'Đến thăm, động viên và trao quà hỗ trợ cho đoàn viên có hoàn cảnh khó khăn, ốm đau hoặc gặp hoạn nạn trong năm.',
            'Thể hiện sự quan tâm, chăm lo đời sống vật chất và tinh thần của tổ chức công đoàn đối với đoàn viên.',
        ],
        'Tọa đàm tuyên truyền pháp luật lao động' => [
            'Tổ chức buổi tọa đàm giới thiệu các quy định mới của Bộ luật Lao động, Luật Công đoàn và chế độ bảo hiểm xã hội; giải đáp thắc mắc của đoàn viên.',
            'Nâng cao hiểu biết pháp luật, giúp đoàn viên nắm rõ quyền và nghĩa vụ của người lao động.',
        ],
        'Chuyến về nguồn tri ân' => [
            'Tổ chức chuyến tham quan di tích lịch sử, dâng hương tưởng niệm và thăm hỏi gia đình chính sách tại địa phương.',
            'Giáo dục truyền thống "Uống nước nhớ nguồn", bồi đắp lòng biết ơn đối với các thế hệ đi trước.',
        ],
        'Hội trại truyền thống' => [
            'Tổ chức hội trại với các hoạt động dựng trại, trò chơi tập thể, đêm lửa trại và giao lưu văn nghệ giữa các tổ.',
            'Tăng cường sự gắn kết, rèn luyện kỹ năng làm việc nhóm và tinh thần tập thể của đoàn viên.',
        ],
        'Chương trình Trung thu cho con em đoàn viên' => [
            'Tổ chức đêm hội Trung thu, rước đèn, phá cỗ và trao quà cho con em đoàn viên; khen thưởng các cháu có thành tích học tập tốt.',
            'Mang lại niềm vui cho các cháu thiếu nhi và động viên tinh thần gia đình đoàn viên.',
        ],
        'Tập huấn kỹ năng nghiệp vụ công đoàn' => [
            'Tổ chức lớp tập huấn về nghiệp vụ công tác công đoàn, kỹ năng tổ chức hoạt động và quản lý hồ sơ cho cán bộ công đoàn các tổ.',
            'Nâng cao năng lực, kỹ năng nghiệp vụ cho đội ngũ cán bộ công đoàn cơ sở.',
        ],
        'Ngày hội gia đình đoàn viên' => [
            'Tổ chức ngày hội với các trò chơi dân gian, hội thi nấu ăn và hoạt động vui chơi dành cho đoàn viên cùng gia đình.',
            'Tôn vinh giá trị gia đình, tạo không khí thân thiện và gắn bó giữa đoàn viên với tập thể.',
        ],
        'Chương trình thiện nguyện vùng khó khăn' => [
            'Vận động đoàn viên quyên góp tiền, sách vở và nhu yếu phẩm; tổ chức đoàn trao tặng cho học sinh và người dân tại địa phương khó khăn.',
            'Chia sẻ khó khăn với cộng đồng, phát huy tinh thần trách nhiệm xã hội của đoàn viên.',
        ],
    ];

    /**
     * Mô tả (content) và mục tiêu (goal) tiếng Việt theo tên hoạt động; tên có kèm năm vẫn tra được.
     *
     * @return array{content: string, goal: string}
     */
    public static function vietnameseText(string $name): array
    {
        $base = trim(preg_replace('/\s+\d{4}$/', '', trim($name)) ?? $name);
        [$content, $goal] = self::TEXTS[$base] ?? [
            'Tổ chức hoạt động "'.$base.'" cho đoàn viên của tổ theo kế hoạch năm học của Công đoàn Trường.',
            'Góp phần thực hiện tốt các chỉ tiêu thi đua và chăm lo đời sống tinh thần cho đoàn viên.',
        ];

        return ['content' => $content, 'goal' => $goal];
    }

    public function definition(): array
    {
        $number = self::$sequence++;
        $start = $this->faker->dateTimeBetween('-6 months', '+3 months');
        $end = (clone $start)->modify('+'.$this->faker->numberBetween(2, 8).' hours');
        $name = $this->faker->randomElement($this->names);

        return [
            'code' => 'HD-'.now()->year.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'name' => $name.' '.now()->year,
            'union_group_id' => UnionGroup::factory(),
            'activity_type_id' => ActivityType::factory(),
            'responsible_name' => $this->faker->name(),
            'start_time' => $start,
            'end_time' => $end,
            'location' => $this->faker->randomElement(['Hội trường A', 'Sân vận động trường', 'Khu hiệu bộ', 'Nhà văn hóa sinh viên', 'Trực tuyến (Online)']),
            ...self::vietnameseText($name),
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
