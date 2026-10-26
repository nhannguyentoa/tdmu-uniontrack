<?php

namespace App\Http\Controllers;

use App\Exports\Members\MemberGroupSheet;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Services\MemberImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Thêm nhanh đoàn viên vào tổ từ tệp Excel: tải lên, xem trước từng dòng, rồi mới lưu các dòng hợp lệ.
 */
class MemberImportController extends Controller
{
    protected const SESSION_KEY = 'member_import';

    public function __construct(private readonly MemberImportService $import) {}

    public function create(Request $request): View
    {
        $this->authorize('create', Member::class);

        return view('members.import', [
            'unionGroups' => $this->allowedGroups($request),
            'maxRows' => MemberImportService::MAX_ROWS,
        ]);
    }

    public function template(Request $request)
    {
        $this->authorize('create', Member::class);

        return Excel::download(new MemberGroupSheet('Mẫu nhập đoàn viên', 'MẪU NHẬP DANH SÁCH ĐOÀN VIÊN', collect(), [],
            'Điền từ dòng 5. Chỉ cột Họ và tên là bắt buộc; để trống Mã đoàn viên thì hệ thống tự cấp. Ngày ghi dạng dd/mm/yyyy.'), 'mau-nhap-doan-vien.xlsx');
    }

    public function analyze(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Member::class);

        $data = $request->validate([
            'union_group_id' => ['required', Rule::in($this->allowedGroups($request)->pluck('id'))],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'union_group_id.required' => 'Vui lòng chọn tổ công đoàn.',
            'union_group_id.in' => 'Bạn chỉ được nhập đoàn viên vào tổ mình phụ trách.',
            'file.required' => 'Vui lòng chọn tệp Excel danh sách đoàn viên.',
            'file.mimes' => 'Chỉ hỗ trợ tệp Excel (.xlsx, .xls) hoặc .csv.',
            'file.max' => 'Tệp quá lớn (tối đa 5 MB).',
        ]);

        try {
            $result = $this->import->analyze($request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['file' => 'Không đọc được tệp. Hãy kiểm tra tệp có đúng định dạng Excel và không bị đặt mật khẩu.'])->withInput();
        }

        if ($result['error']) {
            return back()->withErrors(['file' => $result['error']])->withInput();
        }

        $group = UnionGroup::findOrFail($data['union_group_id']);
        $request->session()->put(self::SESSION_KEY, ['union_group_id' => $group->id, 'rows' => $result['rows']]);

        return view('members.import-preview', [
            'group' => $group,
            'rows' => $result['rows'],
            'fileName' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('members.import')->with('error', 'Phiên nhập đã hết hạn. Vui lòng tải tệp lên lại.');
        }

        // Quyền phụ trách tổ có thể đã thay đổi kể từ lúc xem trước.
        abort_unless($request->user()->managesUnionGroup($pending['union_group_id']), 403);

        $selected = collect($request->input('lines', []))->map(fn ($l) => (int) $l)->flip();
        $rows = collect($pending['rows'])
            ->where('status', 'valid')
            ->filter(fn ($r) => $selected->has($r['line']))
            ->pluck('data')
            ->values()
            ->all();

        if ($rows === []) {
            return back()->with('error', 'Chưa chọn đoàn viên hợp lệ nào để thêm.');
        }

        $result = $this->import->import($rows, $pending['union_group_id']);
        $request->session()->forget(self::SESSION_KEY);

        $message = "Đã thêm {$result['added']} đoàn viên từ tệp Excel.";
        if ($result['skipped']) {
            $message .= " Bỏ qua {$result['skipped']} dòng vì mã đoàn viên vừa được sử dụng.";
        }

        return redirect()->route('members.index', ['union_group_id' => $pending['union_group_id']])->with('success', $message);
    }

    protected function allowedGroups(Request $request): Collection
    {
        return $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->where('status', 'active')->orderBy('name')->get()
            : UnionGroup::where('status', 'active')->orderBy('name')->get();
    }
}
