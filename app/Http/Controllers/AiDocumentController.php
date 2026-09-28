<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\UnionGroup;
use App\Services\Ai\AiException;
use App\Services\Ai\AiManager;
use App\Services\Ai\DocumentDraftService;
use App\Services\Ai\DocumentType;
use App\Services\Ai\SimpleDocx;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Trợ lý soạn bản nháp văn bản (báo cáo tổng kết, báo cáo tháng, báo cáo năm học, thông báo) từ số liệu hệ thống.
 */
class AiDocumentController extends Controller
{
    public function __construct(
        private readonly DocumentDraftService $drafts,
        private readonly AiManager $ai,
        private readonly EvaluationService $evaluation,
    ) {
    }

    public function create(Request $request): View
    {
        return $this->form($request, null);
    }

    public function generate(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $types = $this->allowedTypes($request);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys($types))],
            'activity_id' => ['nullable', 'integer', Rule::exists('activities', 'id')->whereNull('deleted_at')],
            'union_group_id' => ['nullable', 'integer', 'exists:union_groups,id'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'academic_year' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'extra' => ['nullable', 'string', 'max:500'],
        ], [
            'type.required' => 'Vui lòng chọn loại văn bản.',
            'type.in' => 'Loại văn bản không hợp lệ hoặc bạn không có quyền soạn.',
        ]);

        $type = $data['type'];
        $subject = [];

        if (DocumentType::needsActivity($type)) {
            $activity = ! empty($data['activity_id']) ? Activity::find($data['activity_id']) : null;
            if (! $activity) {
                return back()->withInput()->withErrors(['activity_id' => 'Vui lòng chọn hoạt động.']);
            }
            $this->authorize('view', $activity);
            if ($type === DocumentType::INVITATION) {
                abort_unless($user->managesUnionGroup($activity->union_group_id), 403);
            }
            $subject['activity'] = $activity;
        } elseif ($type === DocumentType::MONTHLY_GROUP) {
            $group = ! empty($data['union_group_id']) ? UnionGroup::find($data['union_group_id']) : null;
            if (! $group) {
                return back()->withInput()->withErrors(['union_group_id' => 'Vui lòng chọn tổ công đoàn.']);
            }
            abort_unless($user->managesUnionGroup($group->id), 403);
            $subject += ['group' => $group, 'year' => (int) ($data['year'] ?? now()->year), 'month' => (int) ($data['month'] ?? now()->month)];
        } else {
            $subject['academic_year'] = $data['academic_year'] ?? $this->evaluation->defaultAcademicYear();
        }

        try {
            $draft = $this->drafts->draft($user, $type, $subject, $data['extra'] ?? null);
        } catch (AiException $e) {
            return back()->withInput()->withErrors(['ai' => $e->getMessage()]);
        }

        return $this->form($request, $draft);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:60000'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $name = Str::slug($data['title'] ?? '') ?: 'ban-nhap-van-ban';

        return response(SimpleDocx::build($data['content']), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$name.'.docx"',
        ]);
    }

    /**
     * @param  array{title: string, text: string, provider: string, model: string, simulated: bool}|null  $draft
     */
    private function form(Request $request, ?array $draft): View
    {
        $user = $request->user();
        // Ưu tiên giá trị vừa gửi; nếu vừa bị chuyển hướng lại vì lỗi thì lấy từ dữ liệu cũ đã lưu trong phiên.
        $pick = fn (string $key, mixed $default = null) => $request->input($key, $request->old($key, $default));
        $groups = $user->isAdmin() ? UnionGroup::orderBy('name')->get() : $user->managedUnionGroups()->orderBy('name')->get();

        $activities = Activity::query()
            ->with('unionGroup')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereIn('union_group_id', $groups->pluck('id')))
            ->latest('start_time')
            ->limit(300)
            ->get();

        return view('ai.documents', [
            'types' => $this->allowedTypes($request),
            'groups' => $groups,
            'activities' => $activities,
            'academicYears' => $this->evaluation->academicYears(),
            'draft' => $draft,
            'selected' => [
                'type' => $pick('type'),
                'activity_id' => $pick('activity_id'),
                'union_group_id' => $pick('union_group_id') ?? ($groups->count() === 1 ? $groups->first()->id : null),
                'year' => $pick('year', now()->year),
                'month' => $pick('month', now()->month),
                'academic_year' => $pick('academic_year', $this->evaluation->defaultAcademicYear()),
                'extra' => $pick('extra'),
            ],
            'provider' => $this->ai->activeName(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function allowedTypes(Request $request): array
    {
        return collect(DocumentType::LABELS)
            ->reject(fn (string $label, string $type) => DocumentType::adminOnly($type) && ! $request->user()->isAdmin())
            ->all();
    }
}
