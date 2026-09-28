<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\AiUsageLog;
use App\Services\Ai\AiException;
use App\Services\Ai\AiManager;
use App\Services\Ai\AttendanceImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Nhập danh sách người tham gia từ ảnh/PDF (AI đọc chữ) hoặc tệp văn bản: xem trước, chỉnh sửa rồi mới ghi vào hệ thống.
 */
class ParticipantImportController extends Controller
{
    public function __construct(private readonly AttendanceImportService $import, private readonly AiManager $ai)
    {
    }

    public function create(Activity $activity): View
    {
        $this->authorize('manageParticipants', $activity);

        return view('activities.participants.import', [
            'activity' => $activity,
            'provider' => $this->ai->activeName(),
            'maxKb' => config('ai.max_upload_kb'),
        ]);
    }

    public function analyze(Request $request, Activity $activity): View|RedirectResponse
    {
        $this->authorize('manageParticipants', $activity);

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,txt,csv', 'max:'.config('ai.max_upload_kb')],
        ], [
            'file.required' => 'Vui lòng chọn ảnh hoặc tệp danh sách.',
            'file.mimes' => 'Chỉ hỗ trợ ảnh JPG/PNG/WebP, PDF hoặc tệp văn bản TXT/CSV.',
            'file.max' => 'Tệp quá lớn (tối đa '.round(config('ai.max_upload_kb') / 1024, 1).' MB).',
        ]);

        try {
            $result = $this->import->analyze($activity, $request->file('file'), $request->user());
        } catch (AiException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return view('activities.participants.import-preview', $result + ['activity' => $activity]);
    }

    public function confirm(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('manageParticipants', $activity);

        $data = $request->validate([
            'rows' => ['required', 'array', 'max:300'],
            'rows.*.member_id' => ['nullable', 'integer', Rule::exists('members', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'rows.*.suggested_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(array_keys(ActivityParticipant::STATUSES))],
            'role_note' => ['nullable', 'string', 'max:255'],
            'log_id' => ['nullable', 'integer'],
        ]);

        $log = isset($data['log_id'])
            ? AiUsageLog::where('id', $data['log_id'])
                ->where('feature', AiUsageLog::FEATURE_ATTENDANCE)
                ->where('activity_id', $activity->id)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;

        $rows = array_map(fn (array $r) => [
            'member_id' => ! empty($r['member_id']) ? (int) $r['member_id'] : null,
            'suggested_id' => ! empty($r['suggested_id']) ? (int) $r['suggested_id'] : null,
        ], array_values($data['rows']));

        if (collect($rows)->whereNotNull('member_id')->isEmpty()) {
            return back()->withErrors(['rows' => 'Chưa chọn đoàn viên nào để thêm. Hãy chọn đoàn viên cho ít nhất một dòng.'])->withInput();
        }

        $added = $this->import->confirm($activity, $rows, $data['status'], $data['role_note'] ?? null, $log);

        return redirect()->route('activities.show', $activity)->with('success', "Đã thêm {$added} người tham gia từ danh sách.");
    }
}
