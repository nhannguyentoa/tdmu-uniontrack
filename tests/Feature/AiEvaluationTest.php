<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityEvidence;
use App\Models\ActivityParticipant;
use App\Models\ActivityType;
use App\Models\AiEvaluationSuggestion;
use App\Models\Department;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\Ai\AiException;
use App\Services\Ai\AiResult;
use App\Services\Ai\BenchmarkCases;
use App\Services\Ai\ClaudeProvider;
use App\Services\Ai\EvaluationContext;
use App\Services\Ai\EvidenceEvaluator;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\MockProvider;
use App\Services\Ai\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.provider' => 'mock']);
    }

    protected function makeCriterion(array $overrides = []): EvaluationCriterion
    {
        return EvaluationCriterion::create(array_merge([
            'academic_year' => '2026-2027',
            'group_label' => 'II',
            'order_no' => 1,
            'content' => 'Vận động CĐV tham gia hiến máu nhân đạo',
            'max_score' => 5,
            'department_id' => Department::create(['code' => 'PT', 'name' => 'Ban Phong trào'])->id,
        ], $overrides));
    }

    protected function makeActivity(UnionGroup $group, array $overrides = []): Activity
    {
        return Activity::factory()->create(array_merge([
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
            'name' => 'Hiến máu nhân đạo đợt 33',
            'start_time' => '2026-10-15 08:00',
            'end_time' => '2026-10-15 11:00',
            'status' => Activity::STATUS_COMPLETED,
            'progress' => 100,
            'expected_quantity' => 20,
            'actual_quantity' => 20,
        ], $overrides));
    }

    // ------------------------------------------------------------------ AiResult / JSON

    public function test_result_is_sanitized_and_clamped(): void
    {
        $result = AiResult::fromArray([
            'suggested_score' => 99,
            'confidence' => 'extreme',
            'evidence_status' => 'weird',
            'reasoning' => 'Lý do',
            'matched_activity_ids' => [1, 2, 999, '2'],
            'missing_items' => ['Thiếu ảnh', '', 5],
        ], 5.0, [1, 2], 'mock', 'm');

        $this->assertSame(5.0, $result->suggestedScore);
        $this->assertSame('low', $result->confidence);
        $this->assertSame('insufficient', $result->evidenceStatus);
        $this->assertSame([1, 2], $result->matchedActivityIds);
        $this->assertSame(['Thiếu ảnh'], $result->missingItems);
    }

    public function test_json_is_extracted_from_fenced_or_chatty_output(): void
    {
        $this->assertSame(['a' => 1], PromptBuilder::decodeJson("```json\n{\"a\":1}\n```"));
        $this->assertSame(['a' => 1], PromptBuilder::decodeJson('Đây là kết quả: {"a":1} cảm ơn.'));

        $this->expectException(AiException::class);
        PromptBuilder::decodeJson('không có json nào');
    }

    // ------------------------------------------------------------------ Mock provider & benchmark

    public function test_mock_provider_covers_all_evidence_statuses(): void
    {
        $byScenario = collect(BenchmarkCases::all())->groupBy('scenario');
        $mock = new MockProvider();

        $this->assertSame('sufficient', $mock->evaluate($byScenario['full'][0]['context'])->evidenceStatus);
        $this->assertSame('partial', $mock->evaluate($byScenario['partial'][0]['context'])->evidenceStatus);
        $this->assertSame('insufficient', $mock->evaluate($byScenario['no_evidence'][0]['context'])->evidenceStatus);
        $this->assertSame('no_data', $mock->evaluate($byScenario['unrelated'][0]['context'])->evidenceStatus);
        $this->assertSame('no_data', $mock->evaluate($byScenario['cancelled'][0]['context'])->evidenceStatus);
    }

    public function test_benchmark_has_54_cases_and_keyword_baseline_falls_for_lookalikes(): void
    {
        $cases = BenchmarkCases::all();
        $this->assertCount(54, $cases);

        $mock = new MockProvider();
        $lookalikeWrong = 0;
        $others = 0;
        $othersOk = 0;
        foreach ($cases as $case) {
            $status = $mock->evaluate($case['context'])->evidenceStatus;
            if ($case['scenario'] === 'lookalike') {
                $lookalikeWrong += (int) ($status !== $case['expected_status']);
            } elseif ($case['scenario'] !== 'paraphrase') {
                $others++;
                $othersOk += (int) ($status === $case['expected_status']);
            }
        }

        $this->assertSame(6, $lookalikeWrong, 'Mốc từ khóa phải bị lừa bởi các hoạt động trùng chữ khác nghĩa.');
        $this->assertSame($others, $othersOk);
    }

    // ------------------------------------------------------------------ Context & privacy

    public function test_context_only_contains_the_groups_activities_in_the_academic_year_and_no_member_names(): void
    {
        $group = UnionGroup::factory()->create();
        $other = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion();

        $own = $this->makeActivity($group);
        $collab = $this->makeActivity($other, ['name' => 'Hoạt động phối hợp với tổ']);
        $collab->collaboratingGroups()->attach($group->id, ['role' => 'phoi_hop']);
        $this->makeActivity($other, ['name' => 'Hoạt động của tổ khác']);
        $this->makeActivity($group, ['name' => 'Hoạt động năm học trước', 'start_time' => '2025-10-01 08:00', 'end_time' => '2025-10-01 09:00']);

        $member = Member::factory()->create(['union_group_id' => $group->id, 'full_name' => 'Nguyễn Văn Kín Danh', 'code' => 'DV-SECRET-77']);
        ActivityParticipant::create(['activity_id' => $own->id, 'member_id' => $member->id, 'status' => 'attended']);
        EvaluationScore::create(['evaluation_criterion_id' => $criterion->id, 'union_group_id' => $group->id, 'self_score' => 4, 'self_note' => 'Đã tổ chức đầy đủ']);

        $context = app(EvidenceEvaluator::class)->buildContext($criterion, $group);
        $names = array_column($context->activities, 'name');

        $this->assertEqualsCanonicalizing(['Hiến máu nhân đạo đợt 33', 'Hoạt động phối hợp với tổ'], $names);
        $this->assertSame(4.0, $context->selfScore);
        $ownFact = collect($context->activities)->firstWhere('id', $own->id);
        $this->assertSame(1, $ownFact['participants_total']);
        $this->assertSame(1, $ownFact['participants_attended']);

        $prompt = PromptBuilder::user($context);
        $this->assertStringNotContainsString('Nguyễn Văn Kín Danh', $prompt);
        $this->assertStringNotContainsString('DV-SECRET-77', $prompt);
        $this->assertStringContainsString('1 đăng ký, 1 có mặt', $prompt);
    }

    public function test_attachments_come_from_the_most_related_activities_within_limits(): void
    {
        Storage::fake('public');
        config(['ai.max_files' => 2]);
        $group = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion();
        $related = $this->makeActivity($group);
        $unrelated = $this->makeActivity($group, ['name' => 'Họp giao ban tháng của Ban Chấp hành']);

        foreach ([[$related, 'a.jpg', 'jpg'], [$related, 'b.pdf', 'pdf'], [$related, 'c.png', 'png'], [$unrelated, 'x.jpg', 'jpg']] as [$activity, $name, $ext]) {
            Storage::disk('public')->put("evidences/{$activity->id}/{$name}", 'data');
            ActivityEvidence::create(['activity_id' => $activity->id, 'file_name' => $name, 'file_path' => "evidences/{$activity->id}/{$name}", 'file_type' => $ext, 'file_size' => 4]);
        }

        $attachments = app(EvidenceEvaluator::class)->buildContext($criterion, $group)->attachments;

        $this->assertCount(2, $attachments);
        $this->assertSame(['a.jpg', 'b.pdf'], array_column($attachments, 'name'));
        $this->assertSame(['image/jpeg', 'application/pdf'], array_column($attachments, 'mime'));
    }

    // ------------------------------------------------------------------ HTTP endpoints

    public function test_admin_gets_suggestion_and_it_is_stored(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion();
        $this->makeActivity($group);

        $response = $this->actingAs($admin)->postJson("/evaluation/ai/suggest/{$criterion->id}/{$group->id}");

        $response->assertOk()->assertJsonStructure(['suggestion' => ['score', 'status', 'status_label', 'reasoning', 'provider', 'matched']]);
        $this->assertDatabaseHas('ai_evaluation_suggestions', ['evaluation_criterion_id' => $criterion->id, 'union_group_id' => $group->id, 'provider' => 'mock']);
    }

    public function test_second_run_updates_the_same_row(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion();

        $this->actingAs($admin)->postJson("/evaluation/ai/suggest/{$criterion->id}/{$group->id}")->assertOk();
        $this->actingAs($admin)->postJson("/evaluation/ai/suggest/{$criterion->id}/{$group->id}")->assertOk();

        $this->assertSame(1, AiEvaluationSuggestion::count());
    }

    public function test_officer_cannot_use_admin_suggest_but_can_precheck_own_group_without_seeing_score(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $criterion = $this->makeCriterion();
        $activity = $this->makeActivity($group);
        ActivityEvidence::create(['activity_id' => $activity->id, 'file_name' => 'a.jpg', 'file_path' => 'evidences/a.jpg', 'file_type' => 'jpg', 'file_size' => 1]);

        $this->actingAs($officer)->postJson("/evaluation/ai/suggest/{$criterion->id}/{$group->id}")->assertForbidden();
        $this->actingAs($officer)->postJson("/evaluation/ai/precheck/{$criterion->id}/{$otherGroup->id}")->assertForbidden();

        $response = $this->actingAs($officer)->postJson("/evaluation/ai/precheck/{$criterion->id}/{$group->id}");
        $response->assertOk()->assertJsonPath('suggestion.status', 'sufficient');
        $this->assertArrayNotHasKey('score', $response->json('suggestion'));
    }

    public function test_bonus_criterion_is_rejected_and_guests_are_redirected(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $bonus = $this->makeCriterion(['group_label' => 'thuong', 'order_no' => 2, 'content' => 'Điểm thưởng']);

        $this->actingAs($admin)->postJson("/evaluation/ai/suggest/{$bonus->id}/{$group->id}")
            ->assertStatus(422)->assertJsonPath('message', 'Điểm thưởng được tính tự động từ hoạt động nên không cần AI gợi ý.');

        auth()->logout();
        $this->postJson("/evaluation/ai/suggest/{$bonus->id}/{$group->id}")->assertUnauthorized();
    }

    public function test_missing_api_key_gives_a_friendly_error(): void
    {
        config(['ai.provider' => 'claude', 'ai.claude.key' => null]);
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion();

        $this->actingAs($admin)->postJson("/evaluation/ai/suggest/{$criterion->id}/{$group->id}")
            ->assertStatus(422)->assertJsonPath('message', 'Chưa cấu hình ANTHROPIC_API_KEY nên chưa thể dùng Claude.');
    }

    public function test_verify_and_self_pages_show_ai_controls(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $criterion = $this->makeCriterion();
        AiEvaluationSuggestion::create([
            'evaluation_criterion_id' => $criterion->id, 'union_group_id' => $group->id, 'provider' => 'mock', 'model' => 'heuristic-v1',
            'suggested_score' => 4.5, 'confidence' => 'high', 'evidence_status' => 'sufficient', 'reasoning' => 'Lý do kiểm thử',
        ]);

        $this->actingAs($admin)->get("/evaluation/verify?academic_year=2026-2027&department_id={$criterion->department_id}")
            ->assertOk()->assertSee('AI gợi ý cả cột')->assertSee('Lý do kiểm thử');
        $this->actingAs($admin)->get("/evaluation/{$group->id}/self?academic_year=2026-2027")
            ->assertOk()->assertSee('AI kiểm tra trước');
    }

    // ------------------------------------------------------------------ Providers

    protected function simpleContext(): EvaluationContext
    {
        return new EvaluationContext('Tiêu chí', 5.0, null, '2026-2027', 'Tổ A', null, null, [
            ['id' => 7, 'code' => 'HD-1', 'name' => 'Hoạt động', 'status' => 'completed', 'evidences' => []],
        ]);
    }

    public function test_gemini_provider_parses_a_successful_response(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '{"suggested_score": 3.5, "confidence": "medium", "evidence_status": "partial", "reasoning": "Ổn", "matched_activity_ids": [7], "missing_items": ["Ảnh"]}']]]]],
            'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 40],
        ])]);

        $result = (new GeminiProvider('key', 'gemini-test'))->evaluate($this->simpleContext());

        $this->assertSame(3.5, $result->suggestedScore);
        $this->assertSame([7], $result->matchedActivityIds);
        $this->assertSame(120, $result->inputTokens);
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'key')
            && str_contains($request->url(), 'models/gemini-test:generateContent')
            && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'DỮ LIỆU'));
    }

    public function test_gemini_provider_reports_quota_and_missing_key(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        try {
            (new GeminiProvider('key', 'm'))->evaluate($this->simpleContext());
            $this->fail('Phải ném AiException khi hết hạn mức.');
        } catch (AiException $e) {
            $this->assertStringContainsString('hết hạn mức', $e->getMessage());
        }

        $this->expectException(AiException::class);
        (new GeminiProvider(null, 'm'))->evaluate($this->simpleContext());
    }

    public function test_claude_provider_builds_request_and_parses_text_blocks(): void
    {
        $captured = [];
        $fake = new class($captured)
        {
            public object $messages;

            public function __construct(array &$captured)
            {
                $this->messages = new class($captured)
                {
                    public function __construct(private array &$captured)
                    {
                    }

                    public function create(...$args): object
                    {
                        $this->captured = $args;

                        return (object) [
                            'stopReason' => 'end_turn',
                            'content' => [(object) ['type' => 'text', 'text' => '{"suggested_score": 2, "confidence": "high", "evidence_status": "insufficient", "reasoning": "Thiếu", "matched_activity_ids": [7], "missing_items": []}']],
                            'usage' => (object) ['inputTokens' => 500, 'outputTokens' => 60],
                        ];
                    }
                };
            }
        };

        $result = (new ClaudeProvider(null, 'claude-opus-5', $fake))->evaluate($this->simpleContext());

        $this->assertSame(2.0, $result->suggestedScore);
        $this->assertSame(500, $result->inputTokens);
        $this->assertSame('claude-opus-5', $captured['model']);
        $this->assertStringContainsString('DỮ LIỆU', $captured['system']);
        $this->assertSame('text', $captured['messages'][0]['content'][0]['type']);
    }
}
