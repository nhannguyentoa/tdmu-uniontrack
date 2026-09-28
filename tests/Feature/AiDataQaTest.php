<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
use App\Models\AiUsageLog;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\Ai\DataTools;
use App\Services\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiDataQaTest extends TestCase
{
    use RefreshDatabase;

    private UnionGroup $a;

    private UnionGroup $b;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.provider' => 'mock']);
        $this->a = UnionGroup::factory()->create(['name' => 'Tổ Công đoàn Khoa Luật']);
        $this->b = UnionGroup::factory()->create(['name' => 'Tổ Công đoàn Khoa Toán']);
    }

    private function overdue(UnionGroup $group, string $name): Activity
    {
        return Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
            'name' => $name,
            'status' => Activity::STATUS_IN_PROGRESS,
            'progress' => 30,
            'start_time' => now()->startOfMonth()->addHour(),
            'end_time' => now()->startOfMonth()->addHours(2),
        ]);
    }

    private function officer(UnionGroup $group): User
    {
        $officer = User::factory()->officer()->create();
        $officer->managedUnionGroups()->attach($group->id);

        return $officer;
    }

    private function tools(User $user): DataTools
    {
        return new DataTools($user, app(EvaluationService::class));
    }

    private function ask(User $user, string $question, array $history = [])
    {
        return $this->actingAs($user)->postJson('/ai/assistant', ['question' => $question, 'history' => $history]);
    }

    public function test_admin_sees_overdue_activities_of_all_groups(): void
    {
        $this->overdue($this->a, 'Giải cầu lông A');
        $this->overdue($this->b, 'Hội thi B');

        $response = $this->ask(User::factory()->admin()->create(), 'Tổ nào chậm tiến độ tháng này?');

        $response->assertOk()->assertJsonPath('simulated', true)->assertJsonPath('tools', ['list_activities']);
        $answer = $response->json('answer');
        $this->assertStringContainsString('Khoa Luật', $answer);
        $this->assertStringContainsString('Khoa Toán', $answer);
        $this->assertStringContainsString('Giải cầu lông A', $answer);
    }

    public function test_officer_only_sees_own_group_data(): void
    {
        $this->overdue($this->a, 'Giải cầu lông A');
        $this->overdue($this->b, 'Hội thi B');

        $answer = $this->ask($this->officer($this->a), 'Tổ nào chậm tiến độ tháng này?')->assertOk()->json('answer');

        $this->assertStringContainsString('Khoa Luật', $answer);
        $this->assertStringNotContainsString('Khoa Toán', $answer);
        $this->assertStringNotContainsString('Hội thi B', $answer);
    }

    public function test_tools_refuse_groups_outside_officer_scope(): void
    {
        $tools = $this->tools($this->officer($this->a));

        $denied = $tools->run('list_activities', ['union_group' => 'Khoa Toán']);
        $this->assertStringContainsString('không có quyền', $denied['loi']);

        $missing = $tools->run('group_overview', ['union_group' => 'Khoa Không Tồn Tại']);
        $this->assertStringContainsString('Không tìm thấy', $missing['loi']);

        $ok = $tools->run('group_overview', []);
        $this->assertCount(1, $ok['cac_to']);
        $this->assertSame('Tổ Công đoàn Khoa Luật', $ok['cac_to'][0]['to']);
    }

    public function test_officer_without_groups_gets_no_data(): void
    {
        $result = $this->tools(User::factory()->officer()->create())->run('group_overview', []);

        $this->assertArrayHasKey('loi', $result);
    }

    public function test_unknown_tool_returns_error_not_exception(): void
    {
        $this->assertArrayHasKey('loi', $this->tools(User::factory()->admin()->create())->run('drop_database', []));
    }

    public function test_group_overview_and_participation_numbers(): void
    {
        $done = Activity::factory()->create(['union_group_id' => $this->a->id, 'activity_type_id' => ActivityType::factory(), 'status' => 'completed', 'start_time' => now()->startOfMonth()->addHour(), 'end_time' => now()->startOfMonth()->addHours(2)]);
        $this->overdue($this->a, 'Chậm');
        $member = Member::factory()->create(['union_group_id' => $this->a->id, 'full_name' => 'Tuyệt Mật Không Lộ']);
        $done->participants()->create(['member_id' => $member->id, 'status' => 'attended']);

        $tools = $this->tools(User::factory()->admin()->create());
        $overview = $tools->run('group_overview', ['month' => now()->month, 'year' => now()->year, 'union_group' => 'Khoa Luật']);
        $stats = $tools->run('participation_stats', ['month' => now()->month, 'year' => now()->year, 'union_group' => 'Khoa Luật']);

        $row = $overview['cac_to'][0];
        $this->assertSame(2, $row['so_hoat_dong']);
        $this->assertSame(1, $row['da_hoan_thanh']);
        $this->assertSame(1, $row['qua_han']);
        $this->assertSame(50.0, (float) $row['ty_le_hoan_thanh_phan_tram']);
        $this->assertSame(1, $stats['cac_to'][0]['da_tham_gia']);
        $this->assertStringNotContainsString('Tuyệt Mật', json_encode([$overview, $stats], JSON_UNESCAPED_UNICODE));
    }

    public function test_evaluation_summary_is_filtered_by_scope_and_plan_status_works(): void
    {
        $past = now()->subMonthNoOverflow();
        $year = \App\Support\AcademicYear::forDate($past);
        ActivityPlan::create(['academic_year' => $year, 'month' => $past->month, 'title' => 'Kế hoạch trễ hạn', 'host_union_group_id' => $this->a->id, 'status' => 'planned']);

        $tools = $this->tools($this->officer($this->a));
        $eval = $tools->run('evaluation_summary', []);
        $plans = $tools->run('plan_status', ['academic_year' => $year]);

        $this->assertSame(['Tổ Công đoàn Khoa Luật'], array_column($eval['cac_to'], 'to'));
        $this->assertSame(1, $plans['tong_ke_hoach']);
        $this->assertSame(['Kế hoạch trễ hạn'], array_column($plans['ke_hoach_qua_han'], 'ten'));
    }

    public function test_mock_understands_evaluation_and_help_questions(): void
    {
        $admin = User::factory()->admin()->create();

        $eval = $this->ask($admin, 'So sánh điểm thưởng các tổ')->assertOk();
        $this->assertSame(['evaluation_summary'], $eval->json('tools'));
        $this->assertStringContainsString('Khoa Luật', $eval->json('answer'));

        $help = $this->ask($admin, 'Xin chào bạn khỏe không')->assertOk();
        $this->assertSame([], $help->json('tools'));
        $this->assertStringContainsString('câu hỏi mẫu', $help->json('answer'));
    }

    public function test_gemini_function_calling_loop_uses_scoped_tool_results(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key', 'ai.gemini.model' => 'gemini-test']);
        $this->overdue($this->a, 'Giải cầu lông A');
        $this->overdue($this->b, 'Hội thi B');

        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'list_activities', 'args' => ['overdue_only' => true]]]]]]], 'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 10]])
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => '- Tổ Khoa Luật có 1 hoạt động chậm tiến độ.']]]]], 'usageMetadata' => ['promptTokenCount' => 150, 'candidatesTokenCount' => 20]]),
        ]);

        $response = $this->ask($this->officer($this->a), 'Tổ nào chậm tiến độ?', [['role' => 'user', 'text' => 'chào'], ['role' => 'assistant', 'text' => 'Xin chào']]);

        $response->assertOk()->assertJsonPath('answer', '- Tổ Khoa Luật có 1 hoạt động chậm tiến độ.')->assertJsonPath('tools', ['list_activities'])->assertJsonPath('simulated', false);

        Http::assertSentCount(2);
        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();

        $first = $requests[0]->data();
        $this->assertSame('list_activities', $first['tools'][0]['functionDeclarations'][0]['name']);
        $this->assertStringContainsString('Khoa Luật', $first['systemInstruction']['parts'][0]['text']); // phạm vi được nêu trong hướng dẫn
        $this->assertCount(3, $first['contents']); // 2 lượt lịch sử + câu hỏi

        $secondBody = json_encode($requests[1]->data(), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('functionResponse', $secondBody);
        $this->assertStringContainsString('Giải cầu lông A', $secondBody);
        $this->assertStringNotContainsString('Hội thi B', $secondBody); // tổ ngoài quyền không bao giờ tới AI
        $this->assertStringContainsString('"args":{"overdue_only":true}', $requests[1]->body());

        $log = AiUsageLog::where('feature', AiUsageLog::FEATURE_QA)->firstOrFail();
        $this->assertSame(250, $log->input_tokens);
        $this->assertSame(['list_activities'], $log->stats['tools']);
    }

    public function test_gemini_empty_args_are_sent_back_as_json_object(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'group_overview', 'args' => new \stdClass()]]]]]]])
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Xong']]]]]]),
        ]);

        $this->ask(User::factory()->admin()->create(), 'so sánh các tổ')->assertOk()->assertJsonPath('answer', 'Xong');

        $second = Http::recorded()->map(fn ($p) => $p[0])->values()[1];
        $this->assertStringContainsString('"args":{}', $second->body());
    }

    public function test_gemini_errors_are_reported_and_logged(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $response = $this->ask(User::factory()->admin()->create(), 'so sánh các tổ');

        $response->assertStatus(422);
        $this->assertStringContainsString('hạn mức', $response->json('error'));
        $this->assertFalse(AiUsageLog::firstOrFail()->success);
    }

    public function test_gemini_loop_stops_when_model_keeps_calling_tools(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'group_overview', 'args' => ['month' => 3]]]]]]]])]);

        $this->ask(User::factory()->admin()->create(), 'lặp mãi')->assertStatus(422);

        Http::assertSentCount(6);
    }

    public function test_validation_and_authentication(): void
    {
        $user = User::factory()->admin()->create();

        $this->ask($user, '')->assertStatus(422);
        $this->ask($user, str_repeat('a', 501))->assertStatus(422);
        $this->actingAs($user)->postJson('/ai/assistant', ['question' => 'x', 'history' => [['role' => 'system', 'text' => 'hack']]])->assertStatus(422);

        auth()->logout();
        $this->postJson('/ai/assistant', ['question' => 'x'])->assertUnauthorized();
        $this->get('/ai/assistant')->assertRedirect('/login');
    }

    public function test_assistant_page_shows_scope_for_officer(): void
    {
        $this->actingAs($this->officer($this->a))->get('/ai/assistant')
            ->assertOk()
            ->assertSee('Khoa Luật')
            ->assertDontSee('Khoa Toán');
    }
}
