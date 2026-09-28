<?php

namespace App\Services\Ai;

use App\Models\Activity;
use App\Models\AiEvaluationSuggestion;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Models\User;
use App\Support\AcademicYear;

/**
 * Dựng ngữ cảnh (hoạt động, minh chứng của một tổ trong năm học) rồi nhờ nhà cung cấp AI gợi ý điểm thẩm định cho một tiêu chí.
 */
class EvidenceEvaluator
{
    public function __construct(private readonly AiManager $manager)
    {
    }

    public function evaluate(EvaluationCriterion $criterion, UnionGroup $group, ?User $requestedBy = null, ?AiProvider $provider = null): AiEvaluationSuggestion
    {
        if ($criterion->group_label === EvaluationCriterion::GROUP_BONUS) {
            throw new AiException('Điểm thưởng được tính tự động từ hoạt động nên không cần AI gợi ý.');
        }

        $provider ??= $this->manager->provider();
        $context = $this->buildContext($criterion, $group);
        $result = $provider->evaluate($context);

        return AiEvaluationSuggestion::updateOrCreate(
            [
                'evaluation_criterion_id' => $criterion->id,
                'union_group_id' => $group->id,
                'provider' => $result->provider,
            ],
            [
                'model' => $result->model,
                'suggested_score' => $result->suggestedScore,
                'confidence' => $result->confidence,
                'evidence_status' => $result->evidenceStatus,
                'reasoning' => $result->reasoning,
                'matched_activity_ids' => $result->matchedActivityIds,
                'missing_items' => $result->missingItems,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'requested_by' => $requestedBy?->id,
            ]
        );
    }

    public function buildContext(EvaluationCriterion $criterion, UnionGroup $group): EvaluationContext
    {
        [$start, $end] = AcademicYear::dateRange($criterion->academic_year);

        $activities = Activity::query()
            ->where(function ($query) use ($group) {
                $query->where('union_group_id', $group->id)
                    ->orWhereHas('collaboratingGroups', fn ($q) => $q->where('union_groups.id', $group->id));
            })
            ->whereBetween('start_time', [$start, $end])
            ->with(['activityType', 'evidences', 'collaboratingGroups' => fn ($q) => $q->where('union_groups.id', $group->id)])
            ->withCount([
                'participants as participants_total',
                'participants as participants_attended' => fn ($q) => $q->where('status', 'attended'),
            ])
            ->orderByDesc('start_time')
            ->limit(config('ai.max_activities'))
            ->get();

        $facts = $activities->map(fn (Activity $a) => [
            'id' => $a->id,
            'code' => $a->code,
            'name' => $a->name,
            'type' => $a->activityType?->name,
            'role' => $a->union_group_id === $group->id ? 'chu_tri' : ($a->collaboratingGroups->first()?->pivot->role ?? 'phoi_hop'),
            'status' => $a->status,
            'progress' => (int) $a->progress,
            'start' => $a->start_time?->format('Y-m-d'),
            'end' => $a->end_time?->format('Y-m-d'),
            'location' => $a->location,
            'goal' => mb_substr(trim(($a->goal ?? '').' '.($a->content ?? '')), 0, 300),
            'expected_quantity' => (int) $a->expected_quantity,
            'actual_quantity' => (int) $a->actual_quantity,
            'participants_total' => (int) $a->participants_total,
            'participants_attended' => (int) $a->participants_attended,
            'evidences' => $a->evidences->map(fn ($e) => ['name' => $e->file_name, 'type' => $e->file_type, 'description' => $e->description])->all(),
        ])->values()->all();

        $score = EvaluationScore::where('evaluation_criterion_id', $criterion->id)->where('union_group_id', $group->id)->first();

        return new EvaluationContext(
            criterionContent: $criterion->content,
            maxScore: (float) $criterion->max_score,
            department: $criterion->department?->name,
            academicYear: $criterion->academic_year,
            groupName: $group->name,
            selfScore: $score?->self_score !== null ? (float) $score->self_score : null,
            selfNote: $score?->self_note,
            activities: $facts,
            attachments: $this->pickAttachments($criterion, $activities),
        );
    }

    /**
     * Chọn tệp minh chứng (ảnh/PDF) từ tối đa 5 hoạt động có nội dung gần tiêu chí nhất, trong giới hạn số tệp cấu hình.
     *
     * @param  \Illuminate\Support\Collection<int, Activity>  $activities
     * @return array<int, array<string, mixed>>
     */
    protected function pickAttachments(EvaluationCriterion $criterion, $activities): array
    {
        $candidates = $activities
            ->reject(fn (Activity $a) => $a->status === Activity::STATUS_CANCELLED)
            ->map(fn (Activity $a) => ['activity' => $a, 'score' => TextMatcher::overlap($criterion->content, $a->name.' '.$a->goal)])
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->take(5);

        $attachments = [];
        foreach ($candidates as $row) {
            foreach ($row['activity']->evidences as $evidence) {
                if (count($attachments) >= config('ai.max_files')) {
                    break 2;
                }

                $mime = match (strtolower($evidence->file_type)) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'pdf' => 'application/pdf',
                    default => null,
                };

                if ($mime) {
                    $attachments[] = ['disk' => 'public', 'path' => $evidence->file_path, 'mime' => $mime, 'name' => $evidence->file_name];
                }
            }
        }

        return $attachments;
    }
}
