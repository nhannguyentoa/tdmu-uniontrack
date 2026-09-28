<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private UnionGroup $a;

    private UnionGroup $b;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-28 10:00:00'); // năm học 2026-2027, tháng 9
        $this->a = UnionGroup::factory()->create(['name' => 'Tổ Luật']);
        $this->b = UnionGroup::factory()->create(['name' => 'Tổ Toán']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function activity(UnionGroup $group, string $name, array $attrs = []): Activity
    {
        return Activity::factory()->create($attrs + [
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
            'name' => $name,
            'status' => Activity::STATUS_IN_PROGRESS,
            'progress' => 40,
            'start_time' => '2026-09-20 08:00',
            'end_time' => '2026-09-25 17:00',
            'counts_for_evaluation' => false,
        ]);
    }

    private function officer(UnionGroup ...$groups): User
    {
        $officer = User::factory()->officer()->create();
        foreach ($groups as $group) {
            $officer->managedUnionGroups()->attach($group->id);
        }

        return $officer;
    }

    /** @return array<string, list<array<string, mixed>>> loại => các mục */
    private function itemsOf(User $user): array
    {
        $result = [];
        foreach (app(NotificationService::class)->groups($user) as $group) {
            $result[$group['type']] = $group['items'];
        }

        return $result;
    }

    private function titles(array $items, string $type): array
    {
        return array_column($items[$type] ?? [], 'title');
    }

    public function test_admin_gets_every_activity_notification_type(): void
    {
        $officer = $this->officer($this->a);
        $this->activity($this->a, 'Quá hạn 1');
        $this->activity($this->a, 'Sắp trễ 1', ['start_time' => '2026-09-26 08:00', 'end_time' => '2026-09-30 17:00']);
        $this->activity($this->a, 'Sắp diễn ra 1', ['status' => 'not_started', 'start_time' => '2026-09-29 08:00', 'end_time' => '2026-10-20 17:00']);
        $this->activity($this->a, 'Cần duyệt 1', ['status' => 'completed', 'progress' => 100, 'counts_for_evaluation' => true, 'evaluation_max_score' => 2, 'end_time' => '2026-09-10 17:00']);
        $recent = $this->activity($this->a, 'Vừa cập nhật 1', ['status' => 'completed', 'end_time' => '2026-09-10 17:00']);
        $recent->statusHistories()->create(['status' => 'completed', 'progress' => 100, 'changed_by' => $officer->id]);

        $items = $this->itemsOf(User::factory()->admin()->create());

        $this->assertContains('Quá hạn 1', $this->titles($items, 'overdue'));
        $this->assertContains('Sắp trễ 1', $this->titles($items, 'due_soon'));
        $this->assertContains('Sắp diễn ra 1', $this->titles($items, 'upcoming'));
        $this->assertSame(['Cần duyệt 1'], $this->titles($items, 'needs_approval'));
        $this->assertSame(['Cần duyệt 1'], $this->titles($items, 'missing_evidence'));
        $this->assertSame(['Vừa cập nhật 1'], $this->titles($items, 'recent'));
        $this->assertStringContainsString('Quá hạn 3 ngày', $items['overdue'][0]['message']);
        $this->assertStringContainsString('/activities/', $items['overdue'][0]['url']);
    }

    public function test_finished_cancelled_and_far_deadlines_are_not_notified(): void
    {
        $this->activity($this->a, 'Xong rồi', ['status' => 'completed']);
        $this->activity($this->a, 'Đã hủy', ['status' => 'cancelled']);
        $this->activity($this->a, 'Còn xa', ['start_time' => '2026-10-15 08:00', 'end_time' => '2026-10-20 17:00', 'status' => 'not_started']);
        $this->activity($this->a, 'Quá cũ', ['start_time' => '2024-01-01 08:00', 'end_time' => '2024-01-02 08:00']);

        $items = $this->itemsOf(User::factory()->admin()->create());

        $this->assertArrayNotHasKey('overdue', $items);
        $this->assertArrayNotHasKey('due_soon', $items);
        $this->assertArrayNotHasKey('upcoming', $items);
    }

    public function test_notifications_disappear_when_work_is_done(): void
    {
        $admin = User::factory()->admin()->create();
        $overdue = $this->activity($this->a, 'Quá hạn');
        $upcoming = $this->activity($this->a, 'Sắp diễn ra', ['status' => 'not_started', 'start_time' => '2026-09-29 08:00', 'end_time' => '2026-10-20 17:00']);
        $done = $this->activity($this->a, 'Cần duyệt', ['status' => 'completed', 'progress' => 100, 'counts_for_evaluation' => true, 'evaluation_max_score' => 2, 'end_time' => '2026-09-10 17:00']);

        $before = $this->itemsOf($admin);
        $this->assertArrayHasKey('overdue', $before);
        $this->assertArrayHasKey('upcoming', $before);
        $this->assertArrayHasKey('needs_approval', $before);
        $this->assertArrayHasKey('missing_evidence', $before);

        $overdue->update(['status' => 'completed']);
        $member = Member::factory()->create(['union_group_id' => $this->a->id]);
        $upcoming->participants()->create(['member_id' => $member->id, 'status' => 'registered']);
        $done->evidences()->create(['file_name' => 'a.jpg', 'file_path' => 'x/a.jpg', 'file_type' => 'jpg', 'file_size' => 10, 'uploaded_by' => $admin->id]);
        $done->update(['evaluation_approved_at' => now(), 'evaluation_approved_by' => $admin->id]);

        $after = $this->itemsOf($admin);
        $this->assertArrayNotHasKey('overdue', $after);
        $this->assertArrayNotHasKey('upcoming', $after);
        $this->assertArrayNotHasKey('needs_approval', $after);
        $this->assertArrayNotHasKey('missing_evidence', $after);
    }

    public function test_officer_only_sees_own_group_and_no_admin_only_types(): void
    {
        $officer = $this->officer($this->a);
        $this->activity($this->a, 'Của tổ mình');
        $this->activity($this->b, 'Của tổ khác');
        $this->activity($this->a, 'Chờ duyệt', ['status' => 'completed', 'counts_for_evaluation' => true, 'evaluation_max_score' => 2, 'end_time' => '2026-09-10 17:00']);
        $other = $this->activity($this->a, 'Do người khác cập nhật', ['status' => 'completed', 'end_time' => '2026-09-10 17:00']);
        $other->statusHistories()->create(['status' => 'completed', 'progress' => 100, 'changed_by' => User::factory()->create()->id]);

        $items = $this->itemsOf($officer);

        $this->assertSame(['Của tổ mình'], $this->titles($items, 'overdue'));
        $this->assertSame(['Chờ duyệt'], $this->titles($items, 'missing_evidence')); // thấy để bổ sung minh chứng
        $this->assertArrayNotHasKey('needs_approval', $items); // chỉ Quản trị viên duyệt
        $this->assertArrayNotHasKey('recent', $items);
    }

    public function test_officer_without_groups_has_no_notifications(): void
    {
        $this->activity($this->a, 'Quá hạn');

        $this->assertSame([], app(NotificationService::class)->groups(User::factory()->officer()->create()));
    }

    public function test_admin_does_not_get_recent_notice_for_own_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->activity($this->a, 'Tôi vừa sửa', ['status' => 'completed']);
        $activity->statusHistories()->create(['status' => 'completed', 'progress' => 100, 'changed_by' => $admin->id]);

        $this->assertArrayNotHasKey('recent', $this->itemsOf($admin));

        Carbon::setTestNow('2026-10-05 10:00:00');
        $other = User::factory()->officer()->create();
        $old = $this->activity($this->a, 'Cũ', ['status' => 'completed']);
        $old->statusHistories()->create(['status' => 'completed', 'progress' => 100, 'changed_by' => $other->id]);
        Carbon::setTestNow('2026-10-10 10:00:00');
        $this->assertArrayNotHasKey('recent', $this->itemsOf($admin)); // quá 3 ngày tự biến mất
    }

    public function test_plan_notifications(): void
    {
        $officer = $this->officer($this->a);
        $year = '2026-2027';
        ActivityPlan::create(['academic_year' => $year, 'month' => 8, 'title' => 'Kế hoạch tháng 8', 'host_union_group_id' => $this->a->id, 'status' => 'planned']);
        ActivityPlan::create(['academic_year' => $year, 'month' => 9, 'title' => 'Kế hoạch tháng 9', 'host_union_group_id' => $this->a->id, 'status' => 'planned']);
        ActivityPlan::create(['academic_year' => $year, 'month' => 10, 'title' => 'Kế hoạch tháng 10', 'host_union_group_id' => $this->a->id, 'status' => 'planned']);
        ActivityPlan::create(['academic_year' => $year, 'month' => 8, 'title' => 'Đã hủy', 'host_union_group_id' => $this->a->id, 'status' => 'cancelled']);
        ActivityPlan::create(['academic_year' => $year, 'month' => 8, 'title' => 'Của tổ khác', 'host_union_group_id' => $this->b->id, 'status' => 'planned']);

        $items = $this->itemsOf($officer);

        $this->assertSame(['Kế hoạch tháng 8'], $this->titles($items, 'plan_overdue'));
        $this->assertSame(['Kế hoạch tháng 9'], $this->titles($items, 'plan_due'));
    }

    public function test_evaluation_notifications_for_officer_and_admin(): void
    {
        $year = '2026-2027';
        $c1 = EvaluationCriterion::create(['academic_year' => $year, 'group_label' => 'I', 'order_no' => 1, 'content' => 'TC1', 'max_score' => 5]);
        EvaluationCriterion::create(['academic_year' => $year, 'group_label' => 'I', 'order_no' => 2, 'content' => 'TC2', 'max_score' => 5]);
        EvaluationScore::create(['evaluation_criterion_id' => $c1->id, 'union_group_id' => $this->a->id, 'self_score' => 3]);

        $officerItems = $this->itemsOf($this->officer($this->a));
        $adminItems = $this->itemsOf(User::factory()->admin()->create());

        $this->assertStringContainsString('Còn 1/2 tiêu chí chưa tự chấm', $officerItems['evaluation'][0]['message']);
        $this->assertStringContainsString('/evaluation/'.$this->a->id.'/self', $officerItems['evaluation'][0]['url']);
        $this->assertStringContainsString('2/2 tổ chưa được thẩm định', $adminItems['evaluation'][0]['message']);
    }

    public function test_no_evaluation_notice_when_criteria_do_not_exist_or_all_scored(): void
    {
        $this->assertArrayNotHasKey('evaluation', $this->itemsOf($this->officer($this->a)));

        $c = EvaluationCriterion::create(['academic_year' => '2026-2027', 'group_label' => 'I', 'order_no' => 1, 'content' => 'TC1', 'max_score' => 5]);
        EvaluationScore::create(['evaluation_criterion_id' => $c->id, 'union_group_id' => $this->a->id, 'self_score' => 5]);

        $this->assertArrayNotHasKey('evaluation', $this->itemsOf($this->officer($this->a)));
    }

    public function test_bell_badge_and_page_render(): void
    {
        $this->activity($this->a, 'Việc quá hạn của tổ');
        $this->activity($this->b, 'Việc quá hạn tổ khác');
        $officer = $this->officer($this->a);

        $this->actingAs($officer)->get('/dashboard')->assertOk()->assertSee('Việc quá hạn của tổ')->assertDontSee('tổ khác');

        $page = $this->actingAs($officer)->get('/notifications?type=overdue');
        $page->assertOk()->assertSee('Hoạt động quá hạn')->assertSee('Việc quá hạn của tổ');

        $this->actingAs($officer)->get('/notifications?type=khong-ton-tai')->assertOk()->assertViewHas('type', null);
        $this->actingAs(User::factory()->admin()->create())->get('/notifications')->assertOk()->assertSee('Việc quá hạn tổ khác');
    }

    public function test_empty_state_and_guest_redirect(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/notifications')->assertOk()->assertSee('Không có thông báo');

        auth()->logout();
        $this->get('/notifications')->assertRedirect('/login');
    }
}
