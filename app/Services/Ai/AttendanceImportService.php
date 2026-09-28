<?php

namespace App\Services\Ai;

use App\Models\Activity;
use App\Models\AiUsageLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceImportService
{
    private const MAX_ROWS = 200;

    private const MIME_BY_EXTENSION = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    public function __construct(private readonly AiManager $ai)
    {
    }

    /**
     * Đọc tệp danh sách (ảnh/PDF qua AI; txt/csv đọc trực tiếp không cần AI) rồi đối khớp với đoàn viên.
     *
     * @return array{rows: list<array<string, mixed>>, roster: Collection<int, Member>, log: AiUsageLog, notice: ?string, simulated: bool, provider: string}
     *
     * @throws AiException
     */
    public function analyze(Activity $activity, UploadedFile $file, User $user): array
    {
        $roster = Member::query()->with('unionGroup')->where('status', 'active')->orderBy('full_name')->get();
        $existingIds = $activity->participants()->pluck('member_id')->all();
        $preferredGroups = array_values(array_filter(array_merge(
            [$activity->union_group_id],
            $activity->collaboratingGroups()->pluck('union_groups.id')->all(),
        )));

        $extension = strtolower($file->getClientOriginalExtension());
        $isText = in_array($extension, ['txt', 'csv'], true);
        $started = microtime(true);

        try {
            if ($isText) {
                $read = new AttendanceRead($this->parseText((string) file_get_contents($file->getRealPath())), 'text', 'local-parser');
                $provider = null;
            } else {
                $provider = $this->ai->provider();
                $sampleNames = $provider->isSimulated()
                    ? $roster->reject(fn (Member $m) => in_array($m->id, $existingIds, true))
                        ->sortBy(fn (Member $m) => in_array($m->union_group_id, $preferredGroups, true) ? 0 : 1)
                        ->pluck('full_name')->take(8)->values()->all()
                    : [];

                $read = $provider->readAttendance(new AttendanceRequest(
                    (string) file_get_contents($file->getRealPath()),
                    self::MIME_BY_EXTENSION[$extension] ?? ($file->getMimeType() ?: 'application/octet-stream'),
                    $file->getClientOriginalName(),
                    $sampleNames,
                ));
            }
        } catch (AiException $e) {
            $this->log($user, $activity, $this->ai->activeName(), '-', false, $started, null, null, ['error_message' => $e->getMessage()], $e->getMessage());

            throw $e;
        }

        $rows = (new MemberMatcher($roster, $preferredGroups, $existingIds, (float) config('ai.fuzzy_threshold')))
            ->match(array_slice($read->rows, 0, self::MAX_ROWS));

        $counts = collect($rows)->countBy('status');
        $log = $this->log($user, $activity, $read->provider, $read->model, true, $started, $read->inputTokens, $read->outputTokens, [
            'rows_read' => count($rows),
            'exact' => (int) $counts->get(MemberMatcher::EXACT, 0),
            'fuzzy' => (int) $counts->get(MemberMatcher::FUZZY, 0),
            'ambiguous' => (int) $counts->get(MemberMatcher::AMBIGUOUS, 0),
            'unmatched' => (int) $counts->get(MemberMatcher::UNMATCHED, 0),
            'duplicate' => (int) $counts->get(MemberMatcher::DUPLICATE, 0),
            'source' => $isText ? 'text' : 'image',
        ]);

        return [
            'rows' => $rows,
            'roster' => $roster,
            'log' => $log,
            'notice' => $read->notice,
            'simulated' => $provider?->isSimulated() ?? false,
            'provider' => $read->provider,
        ];
    }

    /**
     * Ghi các đoàn viên đã xác nhận vào danh sách tham gia và cập nhật số liệu độ chính xác trong nhật ký.
     *
     * @param  list<array{member_id: ?int, suggested_id: ?int}>  $rows
     * @return int số người thực sự được thêm mới
     */
    public function confirm(Activity $activity, array $rows, string $status, ?string $roleNote, ?AiUsageLog $log): int
    {
        $accepted = collect($rows)->filter(fn (array $r) => ! empty($r['member_id']));
        $memberIds = $accepted->pluck('member_id')->map(fn ($id) => (int) $id)->unique();
        $existing = $activity->participants()->pluck('member_id')->all();
        $toAdd = $memberIds->reject(fn (int $id) => in_array($id, $existing, true));

        DB::transaction(function () use ($activity, $toAdd, $status, $roleNote, $log, $accepted, $rows, $memberIds) {
            foreach ($toAdd as $memberId) {
                $activity->participants()->create([
                    'member_id' => $memberId,
                    'role_note' => $roleNote,
                    'status' => $status,
                    'registered_at' => now(),
                    'note' => 'Nhập từ ảnh danh sách',
                ]);
            }

            if ($log) {
                $log->update(['stats' => array_merge($log->stats ?? [], [
                    'confirmed' => true,
                    'accepted' => $memberIds->count(),
                    'added' => $toAdd->count(),
                    'auto_kept' => $accepted->filter(fn (array $r) => ! empty($r['suggested_id']) && (int) $r['suggested_id'] === (int) $r['member_id'])->count(),
                    'corrected' => $accepted->filter(fn (array $r) => ! empty($r['suggested_id']) && (int) $r['suggested_id'] !== (int) $r['member_id'])->count(),
                    'added_manually' => $accepted->filter(fn (array $r) => empty($r['suggested_id']))->count(),
                    'skipped' => count($rows) - $accepted->count(),
                ])]);
            }
        });

        return $toAdd->count();
    }

    /**
     * Mỗi dòng một người; cột thứ hai (nếu có) là mã đoàn viên. Bỏ số thứ tự đầu dòng và dòng tiêu đề.
     *
     * @return list<array{name: string, code: ?string, confidence: ?float, note: ?string}>
     */
    private function parseText(string $text): array
    {
        $rows = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $parts = array_map('trim', preg_split('/[\t;,]/u', $line) ?: []);
            $name = preg_replace('/^\s*\d+\s*[.)\-:]?\s*/u', '', $parts[0] ?? '') ?? '';
            $name = trim($name);

            if ($name === '' || in_array(MemberMatcher::normalize($name), ['stt', 'ho ten', 'ho va ten', 'ten', 'danh sach'], true)) {
                continue;
            }

            $code = isset($parts[1]) && preg_match('/^[A-Za-z]{1,5}[-_ ]?\d+$/', $parts[1]) ? $parts[1] : null;
            $rows[] = ['name' => mb_substr($name, 0, 120), 'code' => $code, 'confidence' => null, 'note' => null];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function log(User $user, Activity $activity, string $provider, string $model, bool $success, float $started, ?int $in, ?int $out, array $stats, ?string $error = null): AiUsageLog
    {
        return AiUsageLog::create([
            'feature' => AiUsageLog::FEATURE_ATTENDANCE,
            'provider' => $provider,
            'model' => $model,
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'success' => $success,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'input_tokens' => $in,
            'output_tokens' => $out,
            'stats' => $stats,
        ]);
    }
}
