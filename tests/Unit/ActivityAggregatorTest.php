<?php

namespace Tests\Unit;

use App\Exports\Activities\ActivityAggregator;
use App\Models\Activity;
use App\Models\UnionGroup;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ActivityAggregatorTest extends TestCase
{
    private function group(int $id, string $name): UnionGroup
    {
        $group = new UnionGroup(['name' => $name]);
        $group->id = $id;

        return $group;
    }

    private function activity(UnionGroup $group, string $name, string $start, array $attrs = []): Activity
    {
        static $id = 0;
        $activity = new Activity(['name' => $name, 'start_time' => $start, 'end_time' => $start] + $attrs);
        $activity->id = ++$id;
        $activity->code = 'HD-'.$id;
        $activity->union_group_id = $group->id;
        $activity->status = $attrs['status'] ?? Activity::STATUS_NOT_STARTED;
        $activity->progress = $attrs['progress'] ?? 0;
        $activity->setRelation('unionGroup', $group);
        $activity->setRelation('activityType', null);

        return $activity;
    }

    private function scored(array $extra = []): array
    {
        return array_merge([
            'status' => Activity::STATUS_COMPLETED, 'progress' => 100,
            'counts_for_evaluation' => true, 'evaluation_max_score' => 4, 'evaluation_approved_at' => '2026-10-01 08:00:00',
        ], $extra);
    }

    public function test_same_name_with_different_case_and_spacing_is_merged_within_a_group(): void
    {
        $g = $this->group(1, 'Công đoàn 1');
        $aggregator = new ActivityAggregator(new Collection([
            $this->activity($g, 'Giải bóng đá  công đoàn', '2026-09-10 08:00', $this->scored()),
            $this->activity($g, ' giải BÓNG đá công đoàn ', '2026-08-01 08:00', $this->scored(['progress' => 50])),
            $this->activity($g, 'Hội thao', '2026-07-01 08:00'),
        ]), new Collection([$g]));

        $rows = $aggregator->byGroup()[0]['rows'];

        $this->assertCount(2, $rows);
        $this->assertSame('Hội thao', $rows[0]['name']);
        $merged = $rows[1];
        $this->assertSame(2, $merged['count']);
        $this->assertSame('giải BÓNG đá công đoàn', $merged['name']); // tên của lần sớm nhất
        $this->assertSame(6.0, $merged['earned']); // 4 × 100% + 4 × 50%
        $this->assertSame(8.0, $merged['max_score']);
        $this->assertSame(0.75, $merged['progress']);
        $this->assertCount(2, $merged['codes']);
        $this->assertSame('2026-08-01', $merged['start']->format('Y-m-d'));
    }

    public function test_same_name_in_two_groups_is_two_rows_by_group_but_one_matrix_row(): void
    {
        $a = $this->group(1, 'Công đoàn 1');
        $b = $this->group(2, 'Công đoàn 2');
        $aggregator = new ActivityAggregator(new Collection([
            $this->activity($a, 'Hiến máu nhân đạo', '2026-09-10 08:00'),
            $this->activity($a, 'Hiến máu nhân đạo', '2026-10-10 08:00'),
            $this->activity($b, 'hiến máu nhân đạo', '2026-11-10 08:00'),
        ]), new Collection([$a, $b]));

        $byGroup = $aggregator->byGroup();
        $this->assertCount(2, $byGroup);
        $this->assertSame(2, $byGroup[0]['rows'][0]['count']);
        $this->assertSame(1, $byGroup[1]['rows'][0]['count']);

        $matrix = $aggregator->matrix();
        $this->assertCount(1, $matrix);
        $this->assertSame(3, $matrix[0]['count']);
        $this->assertSame(2, $matrix[0]['groups']);
        $this->assertSame(2, $matrix[0]['cells'][1]['count']);
        $this->assertSame(1, $matrix[0]['cells'][2]['count']);
    }

    public function test_warnings_flag_inconsistent_status_and_pending_approval(): void
    {
        $g = $this->group(1, 'Công đoàn 1');

        $this->assertSame(["'Đã hoàn thành' nhưng tiến độ 75%"], ActivityAggregator::warnings($this->activity($g, 'A', '2026-09-01', ['status' => Activity::STATUS_COMPLETED, 'progress' => 75])));
        $this->assertSame(["'Đã hủy' nhưng tiến độ 20%"], ActivityAggregator::warnings($this->activity($g, 'B', '2026-09-01', ['status' => Activity::STATUS_CANCELLED, 'progress' => 20])));
        $this->assertSame(['Chờ duyệt điểm'], ActivityAggregator::warnings($this->activity($g, 'C', '2026-09-01', $this->scored(['evaluation_approved_at' => null]))));
        $this->assertSame([], ActivityAggregator::warnings($this->activity($g, 'D', '2026-09-01', $this->scored())));
    }

    public function test_summary_counts_statuses_and_distinct_names(): void
    {
        $g = $this->group(1, 'Công đoàn 1');
        $aggregator = new ActivityAggregator(new Collection([
            $this->activity($g, 'A', '2026-09-01', ['status' => Activity::STATUS_COMPLETED, 'progress' => 100, 'expected_quantity' => 10, 'actual_quantity' => 8]),
            $this->activity($g, 'a', '2026-09-02', ['status' => Activity::STATUS_CANCELLED, 'expected_quantity' => 5]),
            $this->activity($g, 'B', '2026-09-03'),
        ]), new Collection([$g]));

        $row = $aggregator->summary()[0];
        $this->assertSame(3, $row['total']);
        $this->assertSame(2, $row['distinct']);
        $this->assertSame(1, $row['statuses'][Activity::STATUS_COMPLETED]);
        $this->assertSame(1, $row['statuses'][Activity::STATUS_CANCELLED]);
        $this->assertSame(1, $row['statuses'][Activity::STATUS_NOT_STARTED]);
        $this->assertSame(15, $row['expected']);
        $this->assertSame(8, $row['actual']);
    }
}
