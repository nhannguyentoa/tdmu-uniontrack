<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\AiUsageLog;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\Ai\DocumentTemplates;
use App\Services\Ai\DocumentType;
use App\Services\Ai\MemberMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.provider' => 'mock']);
    }

    private function group(): UnionGroup
    {
        return UnionGroup::factory()->create();
    }

    private function member(UnionGroup $group, string $name, ?string $code = null): Member
    {
        return Member::factory()->create(array_filter([
            'union_group_id' => $group->id,
            'full_name' => $name,
            'code' => $code,
        ]));
    }

    private function activity(UnionGroup $group, array $attrs = []): Activity
    {
        return Activity::factory()->create($attrs + [
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
        ]);
    }

    private function officerOf(UnionGroup $group): User
    {
        $officer = User::factory()->officer()->create();
        $officer->managedUnionGroups()->attach($group->id);

        return $officer;
    }

    // ---------- Đối khớp tên ----------

    private function matcher(array $members, array $preferred = [], array $existing = []): MemberMatcher
    {
        return new MemberMatcher(collect($members), $preferred, $existing);
    }

    private function row(string $name, ?string $code = null): array
    {
        return ['name' => $name, 'code' => $code, 'confidence' => null, 'note' => null];
    }

    public function test_matcher_matches_exact_name_and_ignores_diacritics_and_case(): void
    {
        $group = $this->group();
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung');
        $this->member($group, 'Trần Văn Bình');

        foreach (['Nguyễn Thị Hồng Nhung', 'NGUYEN THI HONG NHUNG', 'ThS. Nguyễn Thị Hồng Nhung'] as $name) {
            $result = $this->matcher(Member::all()->all())->match([$this->row($name)]);

            $this->assertSame(MemberMatcher::EXACT, $result[0]['status'], $name);
            $this->assertSame($a->id, $result[0]['member_id']);
        }
    }

    public function test_matcher_prefers_member_code(): void
    {
        $group = $this->group();
        $a = $this->member($group, 'Lê Minh Tâm', 'DV-77001');
        $this->member($group, 'Lê Minh Tâm', 'DV-77002');

        $result = $this->matcher(Member::all()->all())->match([$this->row('Le M. Tam', 'dv-77001')]);

        $this->assertSame(MemberMatcher::EXACT, $result[0]['status']);
        $this->assertSame($a->id, $result[0]['member_id']);
    }

    public function test_matcher_fuzzy_matches_small_ocr_errors(): void
    {
        $group = $this->group();
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung');
        $this->member($group, 'Phạm Quốc Khánh');

        $result = $this->matcher(Member::all()->all())->match([$this->row('Nguyen Thi Hong Nhumg')]);

        $this->assertSame(MemberMatcher::FUZZY, $result[0]['status']);
        $this->assertSame($a->id, $result[0]['member_id']);
    }

    public function test_matcher_marks_same_name_as_ambiguous_unless_group_disambiguates(): void
    {
        $g1 = $this->group();
        $g2 = $this->group();
        $a = $this->member($g1, 'Nguyễn Văn An');
        $b = $this->member($g2, 'Nguyễn Văn An');

        $ambiguous = $this->matcher(Member::all()->all())->match([$this->row('Nguyễn Văn An')]);
        $this->assertSame(MemberMatcher::AMBIGUOUS, $ambiguous[0]['status']);
        $this->assertNull($ambiguous[0]['member_id']);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ambiguous[0]['candidates']);

        $preferred = $this->matcher(Member::all()->all(), [$g2->id])->match([$this->row('Nguyễn Văn An')]);
        $this->assertSame(MemberMatcher::EXACT, $preferred[0]['status']);
        $this->assertSame($b->id, $preferred[0]['member_id']);
    }

    public function test_matcher_reports_unmatched_and_duplicates(): void
    {
        $group = $this->group();
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung');
        $b = $this->member($group, 'Trần Văn Bình');

        $result = $this->matcher(Member::all()->all(), [], [$b->id])->match([
            $this->row('Hoàng Xuân Vinh'),
            $this->row('Nguyễn Thị Hồng Nhung'),
            $this->row('Nguyễn Thị Hồng Nhung'),
            $this->row('Trần Văn Bình'),
        ]);

        $this->assertSame(MemberMatcher::UNMATCHED, $result[0]['status']);
        $this->assertSame(MemberMatcher::EXACT, $result[1]['status']);
        $this->assertSame(MemberMatcher::DUPLICATE, $result[2]['status']);
        $this->assertSame(MemberMatcher::DUPLICATE, $result[3]['status']);
        $this->assertSame($a->id, $result[1]['member_id']);
    }

    // ---------- Nhập điểm danh ----------

    public function test_text_file_is_parsed_matched_and_saved_after_confirmation(): void
    {
        $group = $this->group();
        $activity = $this->activity($group);
        $officer = $this->officerOf($group);
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung', 'DV-90001');
        $b = $this->member($group, 'Trần Văn Bình', 'DV-90002');

        $file = UploadedFile::fake()->createWithContent('ds.txt', "STT, Họ tên, Mã\n1. Nguyễn Thị Hồng Nhung\n2) Tran Van Binh, DV-90002\n3. Người Lạ Hoắc\n");

        $preview = $this->actingAs($officer)->post("/activities/{$activity->id}/participants/import", ['file' => $file]);

        $preview->assertOk();
        $preview->assertViewHas('rows', function (array $rows) use ($a, $b) {
            return count($rows) === 3
                && $rows[0]['member_id'] === $a->id
                && $rows[1]['member_id'] === $b->id
                && $rows[2]['status'] === MemberMatcher::UNMATCHED;
        });
        $this->assertDatabaseCount('activity_participants', 0); // xem trước chưa ghi gì

        $log = AiUsageLog::firstOrFail();
        $this->assertSame(3, $log->stats['rows_read']);
        $this->assertSame('text', $log->stats['source']);

        $confirm = $this->actingAs($officer)->post("/activities/{$activity->id}/participants/import/confirm", [
            'log_id' => $log->id,
            'status' => 'attended',
            'rows' => [
                ['member_id' => $a->id, 'suggested_id' => $a->id],
                ['member_id' => $a->id, 'suggested_id' => $b->id], // chọn trùng người: chỉ thêm một lần
                ['member_id' => '', 'suggested_id' => ''],
            ],
        ]);

        $confirm->assertRedirect("/activities/{$activity->id}");
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $activity->id, 'member_id' => $a->id, 'status' => 'attended']);
        $this->assertSame(1, $activity->participants()->count());

        $stats = $log->fresh()->stats;
        $this->assertTrue($stats['confirmed']);
        $this->assertSame(1, $stats['added']);
        $this->assertSame(1, $stats['auto_kept']);
        $this->assertSame(1, $stats['corrected']);
        $this->assertSame(1, $stats['skipped']);
    }

    public function test_mock_provider_returns_labelled_demo_for_images(): void
    {
        $group = $this->group();
        $activity = $this->activity($group);
        $officer = $this->officerOf($group);
        foreach (['Nguyễn Thị Hồng Nhung', 'Trần Văn Bình', 'Lê Quốc Cường', 'Phạm Thu Hà', 'Đỗ Minh Quân'] as $name) {
            $this->member($group, $name);
        }

        $response = $this->actingAs($officer)->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->create('ds.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertOk();
        $response->assertSee('Chế độ giả lập', false);
        $response->assertViewHas('simulated', true);
        $response->assertViewHas('rows', fn (array $rows) => count($rows) === 6);
    }

    public function test_confirmed_members_already_in_activity_are_not_duplicated(): void
    {
        $group = $this->group();
        $activity = $this->activity($group);
        $officer = $this->officerOf($group);
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung');
        $activity->participants()->create(['member_id' => $a->id, 'status' => 'registered']);

        $this->actingAs($officer)->post("/activities/{$activity->id}/participants/import/confirm", [
            'status' => 'attended',
            'rows' => [['member_id' => $a->id, 'suggested_id' => $a->id]],
        ])->assertRedirect();

        $this->assertSame(1, $activity->participants()->count());
    }

    public function test_confirm_requires_at_least_one_member(): void
    {
        $group = $this->group();
        $activity = $this->activity($group);

        $this->actingAs($this->officerOf($group))->post("/activities/{$activity->id}/participants/import/confirm", [
            'status' => 'attended',
            'rows' => [['member_id' => '', 'suggested_id' => '']],
        ])->assertSessionHasErrors('rows');
    }

    public function test_officer_of_other_group_cannot_import(): void
    {
        $activity = $this->activity($this->group());
        $stranger = $this->officerOf($this->group());

        $this->actingAs($stranger)->get("/activities/{$activity->id}/participants/import")->assertForbidden();
        $this->actingAs($stranger)->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->createWithContent('ds.txt', "A\n"),
        ])->assertForbidden();
        $this->actingAs($stranger)->post("/activities/{$activity->id}/participants/import/confirm", [
            'status' => 'attended', 'rows' => [['member_id' => 1]],
        ])->assertForbidden();
    }

    public function test_upload_rejects_unsupported_file_types(): void
    {
        $group = $this->group();
        $activity = $this->activity($group);

        $this->actingAs($this->officerOf($group))->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('file');
    }

    public function test_gemini_reads_image_without_sending_member_names(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key', 'ai.gemini.model' => 'gemini-test']);

        $group = $this->group();
        $activity = $this->activity($group);
        $officer = $this->officerOf($group);
        $a = $this->member($group, 'Nguyễn Thị Hồng Nhung');
        $this->member($group, 'Bí Mật Đoàn Viên');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '```json'."\n".json_encode([
                    'rows' => [['full_name' => 'Nguyen Thi Hong Nhung', 'member_code' => null, 'confidence' => 0.8, 'note' => null]],
                    'document_note' => 'Ảnh hơi mờ',
                ])."\n".'```']]]]],
                'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 30],
            ]),
        ]);

        $response = $this->actingAs($officer)->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->create('ds.png', 100, 'image/png'),
        ]);

        $response->assertOk();
        $response->assertViewHas('rows', fn (array $rows) => count($rows) === 1 && $rows[0]['member_id'] === $a->id);
        $response->assertViewHas('notice', 'Ảnh hơi mờ');

        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->url(), 'gemini-test:generateContent')
                && str_contains($body, 'inlineData')
                && ! str_contains($body, 'Bí Mật')
                && ! str_contains($body, 'Nguyễn Thị Hồng Nhung');
        });

        $log = AiUsageLog::firstOrFail();
        $this->assertSame('gemini', $log->provider);
        $this->assertSame(120, $log->input_tokens);
    }

    public function test_gemini_quota_error_is_shown_friendly_and_logged(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $group = $this->group();
        $activity = $this->activity($group);

        $response = $this->actingAs($this->officerOf($group))->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->create('ds.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertStringContainsString('hạn mức', session('errors')->first('file'));
        $this->assertFalse(AiUsageLog::firstOrFail()->success);
    }

    public function test_missing_gemini_key_gives_clear_message(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => null]);

        $group = $this->group();
        $activity = $this->activity($group);

        $response = $this->actingAs($this->officerOf($group))->post("/activities/{$activity->id}/participants/import", [
            'file' => UploadedFile::fake()->create('ds.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertStringContainsString('GEMINI_API_KEY', session('errors')->first('file'));
    }

    // ---------- Soạn văn bản ----------

    public function test_all_document_types_render_with_mock_provider(): void
    {
        $admin = User::factory()->admin()->create();
        $group = $this->group();
        $activity = $this->activity($group, ['name' => 'Hội thao truyền thống ABC', 'start_time' => '2026-03-10 08:00', 'end_time' => '2026-03-10 11:00']);
        $member = $this->member($group, 'Tuyệt Mật Không Được Lộ');
        $activity->participants()->create(['member_id' => $member->id, 'status' => 'attended']);

        $cases = [
            [DocumentType::ACTIVITY_SUMMARY, ['activity_id' => $activity->id], 'BÁO CÁO TỔNG KẾT HOẠT ĐỘNG'],
            [DocumentType::INVITATION, ['activity_id' => $activity->id], 'THÔNG BÁO'],
            [DocumentType::MONTHLY_GROUP, ['union_group_id' => $group->id, 'year' => 2026, 'month' => 3], 'THÁNG 3/2026'],
            [DocumentType::YEARLY_SCHOOL, ['academic_year' => '2025-2026'], 'NĂM HỌC 2025-2026'],
        ];

        foreach ($cases as [$type, $params, $expected]) {
            $response = $this->actingAs($admin)->post('/ai/documents', ['type' => $type] + $params);

            $response->assertOk();
            $response->assertSee($expected, false);
            $response->assertViewHas('draft', fn (array $draft) => $draft['simulated'] === true && ! str_contains($draft['text'], 'Tuyệt Mật'));
        }

        $this->assertSame(4, AiUsageLog::where('feature', AiUsageLog::FEATURE_DOCUMENT)->where('success', true)->count());
    }

    public function test_monthly_draft_mentions_activities_of_that_month_only(): void
    {
        $group = $this->group();
        $this->activity($group, ['name' => 'Hoạt động tháng ba', 'start_time' => '2026-03-10 08:00', 'end_time' => '2026-03-10 10:00', 'status' => 'completed', 'progress' => 100]);
        $this->activity($group, ['name' => 'Hoạt động tháng tư', 'start_time' => '2026-04-10 08:00', 'end_time' => '2026-04-10 10:00']);

        $response = $this->actingAs($this->officerOf($group))->post('/ai/documents', [
            'type' => DocumentType::MONTHLY_GROUP, 'union_group_id' => $group->id, 'year' => 2026, 'month' => 3,
        ]);

        $response->assertViewHas('draft', fn (array $d) => str_contains($d['text'], 'Hoạt động tháng ba') && ! str_contains($d['text'], 'Hoạt động tháng tư'));
    }

    public function test_officer_cannot_draft_for_other_group_or_school_year(): void
    {
        $mine = $this->group();
        $other = $this->group();
        $officer = $this->officerOf($mine);
        $foreignActivity = $this->activity($other);

        $this->actingAs($officer)->post('/ai/documents', ['type' => DocumentType::MONTHLY_GROUP, 'union_group_id' => $other->id, 'year' => 2026, 'month' => 3])->assertForbidden();
        $this->actingAs($officer)->post('/ai/documents', ['type' => DocumentType::INVITATION, 'activity_id' => $foreignActivity->id])->assertForbidden();
        $this->actingAs($officer)->post('/ai/documents', ['type' => DocumentType::YEARLY_SCHOOL, 'academic_year' => '2025-2026'])->assertSessionHasErrors('type');

        $this->actingAs($officer)->get('/ai/documents')->assertOk()->assertDontSee('Báo cáo năm học toàn trường');
    }

    public function test_document_form_requires_subject(): void
    {
        $group = $this->group();

        $this->actingAs($this->officerOf($group))->post('/ai/documents', ['type' => DocumentType::ACTIVITY_SUMMARY])->assertSessionHasErrors('activity_id');
    }

    public function test_gemini_document_prompt_uses_facts_and_forbids_invention(): void
    {
        config(['ai.provider' => 'gemini', 'ai.gemini.key' => 'test-key', 'ai.gemini.model' => 'gemini-test']);

        $group = $this->group();
        $activity = $this->activity($group, ['name' => 'Chương trình Trung thu']);
        $member = $this->member($group, 'Tuyệt Mật Không Được Lộ');
        $activity->participants()->create(['member_id' => $member->id, 'status' => 'attended']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => "# BÁO CÁO TỔNG KẾT\n\nNội dung do AI soạn."]]]]],
            'usageMetadata' => ['promptTokenCount' => 500, 'candidatesTokenCount' => 200],
        ])]);

        $response = $this->actingAs($this->officerOf($group))->post('/ai/documents', [
            'type' => DocumentType::ACTIVITY_SUMMARY, 'activity_id' => $activity->id, 'extra' => 'Viết ngắn gọn',
        ]);

        $response->assertOk();
        $response->assertViewHas('draft', fn (array $d) => $d['simulated'] === false && $d['provider'] === 'gemini' && str_contains($d['text'], 'Nội dung do AI soạn'));

        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return str_contains($body, 'Chương trình Trung thu')
                && str_contains($body, 'Viết ngắn gọn')
                && str_contains($body, 'không bịa')
                && ! str_contains($body, 'Tuyệt Mật');
        });
    }

    public function test_download_builds_valid_docx(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/ai/documents/download', [
            'title' => 'Báo cáo thử',
            'content' => "# TIÊU ĐỀ\n\n## I. Mục\n- Gạch đầu dòng & ký tự <đặc biệt>\nĐoạn văn.",
        ]);

        $response->assertOk();
        $this->assertStringContainsString('.docx', $response->headers->get('Content-Disposition'));

        $tmp = tempnam(sys_get_temp_dir(), 'tst');
        file_put_contents($tmp, $response->getContent());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertNotFalse($xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'document.xml phải là XML hợp lệ');
        $this->assertStringContainsString('TIÊU ĐỀ', $xml);
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&lt;đặc biệt&gt;', $xml);
    }

    public function test_download_requires_content(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/ai/documents/download', ['title' => 'x'])->assertSessionHasErrors('content');
    }

    public function test_templates_mark_missing_information_instead_of_inventing(): void
    {
        $text = DocumentTemplates::render(DocumentType::INVITATION, [
            'ten_hoat_dong' => 'Giải chạy', 'to_chu_tri' => 'Tổ A', 'muc_tieu' => null, 'bat_dau' => '08:00 01/01/2027',
            'ket_thuc' => null, 'dia_diem' => '', 'so_luong_du_kien' => null, 'noi_dung' => null,
        ]);

        $this->assertStringContainsString('[cần bổ sung]', $text);
        $this->assertStringNotContainsString('Mục đích: Mục', $text);
    }

    public function test_guests_cannot_use_ai_features(): void
    {
        $this->get('/ai/documents')->assertRedirect('/login');
        $this->post('/ai/documents/download', ['content' => 'x'])->assertRedirect('/login');
    }
}
