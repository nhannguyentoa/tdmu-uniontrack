<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityPlanSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function makePlan(array $overrides = []): ActivityPlan
    {
        return ActivityPlan::create(array_merge([
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => 'Tổ chức Tết Trung thu',
            'status' => ActivityPlan::STATUS_PLANNED,
        ], $overrides));
    }

    // ---------------------------------------------------------------- trạng thái tự tính

    public function test_plan_in_current_or_future_month_is_planned(): void
    {
        Carbon::setTestNow('2026-09-15');

        $this->assertSame('planned', $this->makePlan(['month' => 9])->displayStatusKey());
        $this->assertSame('planned', $this->makePlan(['month' => 12])->displayStatusKey());
        $this->assertSame('planned', $this->makePlan(['month' => 3])->displayStatusKey());
    }

    public function test_plan_whose_month_has_passed_without_activity_is_overdue(): void
    {
        Carbon::setTestNow('2026-09-15');

        $plan = $this->makePlan(['month' => 8]);

        $this->assertTrue($plan->isOverdue());
        $this->assertSame('overdue', $plan->displayStatusKey());
    }

    public function test_plan_becomes_overdue_from_the_first_day_of_the_next_month(): void
    {
        $plan = $this->makePlan(['month' => 8]);

        Carbon::setTestNow('2026-08-31 23:00');
        $this->assertSame('planned', $plan->displayStatusKey());

        Carbon::setTestNow('2026-09-01 00:01');
        $this->assertSame('overdue', $plan->displayStatusKey());
    }

    public function test_january_plan_belongs_to_the_second_calendar_year_of_the_academic_year(): void
    {
        Carbon::setTestNow('2026-12-20');
        $plan = $this->makePlan(['month' => 1]);

        $this->assertSame('2027-01-01', $plan->monthStart()->toDateString());
        $this->assertSame('planned', $plan->displayStatusKey());
    }

    public function test_cancelled_plan_is_never_overdue(): void
    {
        Carbon::setTestNow('2026-09-15');

        $plan = $this->makePlan(['month' => 8, 'status' => ActivityPlan::STATUS_CANCELLED]);

        $this->assertFalse($plan->isOverdue());
        $this->assertSame('cancelled', $plan->displayStatusKey());
    }

    public function test_linked_plan_mirrors_every_activity_status_even_when_month_has_passed(): void
    {
        Carbon::setTestNow('2026-09-15');
        $plan = $this->makePlan(['month' => 8]);
        $activity = Activity::factory()->create();
        $plan->update(['activity_id' => $activity->id]);

        foreach (array_keys(Activity::STATUSES) as $status) {
            $activity->update(['status' => $status]);

            $this->assertSame($status, $plan->fresh()->displayStatusKey());
        }
    }

    public function test_deleting_the_linked_activity_returns_plan_to_unlinked_state(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();
        $plan = $this->makePlan(['activity_id' => $activity->id]);

        $this->actingAs($admin)->delete("/activities/{$activity->id}")->assertRedirect();

        $this->assertNull($plan->fresh()->activity_id);
        $this->assertContains($plan->fresh()->displayStatusKey(), ['planned', 'overdue']);
    }

    public function test_index_shows_overdue_and_mirrored_status_labels(): void
    {
        Carbon::setTestNow('2026-09-15');
        $admin = User::factory()->admin()->create();
        $this->makePlan(['month' => 8, 'title' => 'Kế hoạch tháng tám chưa làm']);
        $activity = Activity::factory()->create(['status' => Activity::STATUS_IN_PROGRESS]);
        $this->makePlan(['month' => 8, 'title' => 'Kế hoạch đã chuyển', 'activity_id' => $activity->id]);

        $response = $this->actingAs($admin)->get('/activity-plans?academic_year=2026-2027');

        $response->assertOk()->assertSee('Quá hạn')->assertSee('Đang thực hiện');
    }

    // ---------------------------------------------------------------- điền sẵn khi chuyển thành hoạt động

    public function test_create_page_is_prefilled_from_the_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $host = UnionGroup::factory()->create(['name' => 'Tổ đăng cai kiểm thử']);
        $partner = UnionGroup::factory()->create(['name' => 'Tổ phối hợp kiểm thử']);
        $type = ActivityType::factory()->create(['name' => 'Loại phong trào kiểm thử']);
        $plan = $this->makePlan([
            'title' => 'Hội thi nấu ăn 20/10',
            'host_union_group_id' => $host->id,
            'activity_type_id' => $type->id,
            'counts_for_evaluation' => true,
            'evaluation_max_score' => 6,
            'note' => 'Ghi chú của kế hoạch',
        ]);
        $plan->collaboratingGroups()->sync([$partner->id => ['role' => 'phoi_hop']]);

        $response = $this->actingAs($admin)->get('/activities/create?from_activity_plan_id='.$plan->id);

        $response->assertOk()
            ->assertSee('Đang tạo hoạt động từ kế hoạch: Hội thi nấu ăn 20/10', false)
            ->assertSee('value="Hội thi nấu ăn 20/10"', false)
            ->assertSee('Ghi chú của kế hoạch')
            ->assertSee('name="from_activity_plan_id" value="'.$plan->id.'"', false);
        $this->assertMatchesRegularExpression('/<option value="'.$host->id.'"\s+selected/', $response->getContent());
        $this->assertMatchesRegularExpression('/<option value="'.$type->id.'"\s+selected/', $response->getContent());
        $this->assertMatchesRegularExpression('/name="collaborating_groups\[phoi_hop\]\[\]" value="'.$partner->id.'"\s+checked/', $response->getContent());
        $this->assertStringContainsString('value="6.00"', $response->getContent());
    }

    public function test_create_page_suggests_next_activity_code(): void
    {
        $admin = User::factory()->admin()->create();
        Activity::factory()->create(['code' => 'HD-2026-0041']);
        Activity::factory()->create(['code' => 'HD-2026-0068']);
        Carbon::setTestNow('2026-09-15');

        $this->actingAs($admin)->get('/activities/create')->assertOk()->assertSee('value="HD-2026-0069"', false);
    }

    public function test_suggested_code_skips_soft_deleted_activities(): void
    {
        $activity = Activity::factory()->create(['code' => 'HD-2026-0100']);
        $activity->delete();

        $this->assertSame('HD-2026-0101', Activity::suggestNextCode(2026));
        $this->assertSame('HD-2027-0001', Activity::suggestNextCode(2027));
    }

    public function test_plan_that_is_already_linked_or_cancelled_cannot_be_converted(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();
        $linked = $this->makePlan(['activity_id' => $activity->id]);
        $cancelled = $this->makePlan(['status' => ActivityPlan::STATUS_CANCELLED]);

        $this->actingAs($admin)->get('/activities/create?from_activity_plan_id='.$linked->id)
            ->assertRedirect(route('activity-plans.index'));
        $this->actingAs($admin)->get('/activities/create?from_activity_plan_id='.$cancelled->id)
            ->assertRedirect(route('activity-plans.index'));
        $this->actingAs($admin)->get('/activities/create?from_activity_plan_id=999999')
            ->assertRedirect(route('activity-plans.index'));
    }

    public function test_converting_keeps_plan_status_synced_with_the_new_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();
        $plan = $this->makePlan(['host_union_group_id' => $group->id]);

        $this->actingAs($admin)->post('/activities', [
            'code' => 'HD-2026-0500',
            'name' => 'Tổ chức Tết Trung thu',
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-09-15 08:00',
            'end_time' => '2026-09-15 10:00',
            'status' => 'preparing',
            'progress' => 0,
            'from_activity_plan_id' => $plan->id,
        ])->assertRedirect();

        $plan = $plan->fresh();
        $this->assertNotNull($plan->activity_id);
        $this->assertSame('planned', $plan->status);
        $this->assertSame('preparing', $plan->displayStatusKey());

        $plan->activity->update(['status' => Activity::STATUS_COMPLETED]);
        $this->assertSame('completed', $plan->fresh()->displayStatusKey());
    }

    // ---------------------------------------------------------------- form kế hoạch với các ô mới

    public function test_plan_can_be_saved_with_type_evaluation_and_collaborating_groups(): void
    {
        $admin = User::factory()->admin()->create();
        $host = UnionGroup::factory()->create();
        $phoiHop = UnionGroup::factory()->create();
        $thamGia = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();

        $this->actingAs($admin)->post('/activity-plans', [
            'academic_year' => '2026-2027',
            'month' => 10,
            'title' => 'Hoạt động 20/10',
            'host_union_group_id' => $host->id,
            'activity_type_id' => $type->id,
            'counts_for_evaluation' => 1,
            'evaluation_max_score' => 5,
            'collaborating_groups' => ['phoi_hop' => [$phoiHop->id], 'tham_gia' => [$thamGia->id]],
        ])->assertRedirect();

        $plan = ActivityPlan::where('title', 'Hoạt động 20/10')->firstOrFail();
        $this->assertSame($type->id, $plan->activity_type_id);
        $this->assertTrue($plan->counts_for_evaluation);
        $this->assertEquals(5, $plan->evaluation_max_score);
        $this->assertSame('planned', $plan->status);
        $this->assertSame(
            ['phoi_hop', 'tham_gia'],
            $plan->collaboratingGroups()->orderBy('union_groups.id')->get()->pluck('pivot.role')->all()
        );
    }

    public function test_plan_requires_max_score_when_counting_for_evaluation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/activity-plans', [
            'academic_year' => '2026-2027',
            'month' => 10,
            'title' => 'Thiếu điểm tối đa',
            'counts_for_evaluation' => 1,
        ])->assertSessionHasErrors('evaluation_max_score');
    }

    public function test_plan_host_group_cannot_also_be_a_collaborating_group(): void
    {
        $admin = User::factory()->admin()->create();
        $host = UnionGroup::factory()->create();

        $this->actingAs($admin)->post('/activity-plans', [
            'academic_year' => '2026-2027',
            'month' => 10,
            'title' => 'Trùng tổ',
            'host_union_group_id' => $host->id,
            'collaborating_groups' => ['phoi_hop' => [$host->id]],
        ])->assertSessionHasErrors('collaborating_groups');
    }

    public function test_cancelled_checkbox_marks_plan_cancelled_and_score_is_dropped_when_not_counting(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = $this->makePlan(['counts_for_evaluation' => true, 'evaluation_max_score' => 4]);

        $this->actingAs($admin)->put("/activity-plans/{$plan->id}", [
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => $plan->title,
            'is_cancelled' => 1,
        ])->assertRedirect();

        $plan = $plan->fresh();
        $this->assertSame('cancelled', $plan->status);
        $this->assertFalse($plan->counts_for_evaluation);
        $this->assertNull($plan->evaluation_max_score);
    }

    public function test_plan_form_pages_render_with_new_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = $this->makePlan();

        $this->actingAs($admin)->get('/activity-plans/create')->assertOk()
            ->assertSee('Loại hoạt động')->assertSee('Hoạt động này có tính điểm thi đua')->assertSee('Đánh dấu kế hoạch này đã hủy');
        $this->actingAs($admin)->get("/activity-plans/{$plan->id}/edit")->assertOk();
    }
}
