<?php

namespace App\Services\Ai;

use App\Models\Activity;
use App\Models\AiUsageLog;
use App\Models\UnionGroup;
use App\Models\User;

class DocumentDraftService
{
    public function __construct(private readonly AiManager $ai, private readonly DocumentFacts $facts)
    {
    }

    /**
     * @param  array{activity?: Activity, group?: UnionGroup, year?: int, month?: int, academic_year?: string}  $subject
     * @return array{title: string, text: string, provider: string, model: string, simulated: bool}
     *
     * @throws AiException
     */
    public function draft(User $user, string $type, array $subject, ?string $extra = null): array
    {
        $facts = match ($type) {
            DocumentType::ACTIVITY_SUMMARY, DocumentType::INVITATION => $this->facts->forActivity($subject['activity']),
            DocumentType::MONTHLY_GROUP => $this->facts->forMonth($subject['group'], $subject['year'], $subject['month']),
            DocumentType::YEARLY_SCHOOL => $this->facts->forSchoolYear($subject['academic_year']),
            default => throw new AiException('Loại văn bản không hợp lệ.'),
        };

        $request = new DocumentRequest($type, $facts, Prompts::documentSystem(), Prompts::documentUser($type, $facts, $extra));
        $provider = $this->ai->provider();
        $started = microtime(true);

        try {
            $result = $provider->draftDocument($request);
        } catch (AiException $e) {
            $this->log($user, $subject['activity'] ?? null, $type, $provider->name(), $provider->model(), false, $started, null, null, $e->getMessage());

            throw $e;
        }

        $text = $this->clean($result->text);
        if ($text === '') {
            throw new AiException('AI không trả về nội dung văn bản. Vui lòng thử lại.');
        }

        $this->log($user, $subject['activity'] ?? null, $type, $result->provider, $result->model, true, $started, $result->inputTokens, $result->outputTokens);

        return [
            'title' => $this->titleOf($text, $type),
            'text' => $text,
            'provider' => $result->provider,
            'model' => $result->model,
            'simulated' => $provider->isSimulated(),
        ];
    }

    /** Bỏ khối mã bọc ngoài nếu mô hình lỡ thêm vào. */
    private function clean(string $text): string
    {
        $text = trim($text);

        return trim(preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text) ?? $text);
    }

    private function titleOf(string $text, string $type): string
    {
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }

        return DocumentType::LABELS[$type] ?? 'Văn bản';
    }

    private function log(User $user, ?Activity $activity, string $type, string $provider, string $model, bool $success, float $started, ?int $in, ?int $out, ?string $error = null): void
    {
        AiUsageLog::create([
            'feature' => AiUsageLog::FEATURE_DOCUMENT,
            'provider' => $provider,
            'model' => $model,
            'user_id' => $user->id,
            'activity_id' => $activity?->id,
            'subject' => $type,
            'success' => $success,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'input_tokens' => $in,
            'output_tokens' => $out,
        ]);
    }
}
