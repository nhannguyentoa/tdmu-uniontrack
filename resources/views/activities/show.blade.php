@php
    $canManage = auth()->user()->can('update', $activity);
    $canManageParticipants = auth()->user()->can('manageParticipants', $activity);
    $canManageEvidences = auth()->user()->can('manageEvidences', $activity);
@endphp

<x-app-layout :title="$activity->name" :breadcrumbs="['Hoạt động' => route('activities.index'), $activity->name => null]">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-semibold text-slate-800">{{ $activity->name }}</h2>
                <x-status-badge :status="$activity->status" />
                @if($activity->isOverdue())
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">Quá hạn</span>
                @endif
            </div>
            <p class="text-sm text-slate-500">Mã hoạt động: {{ $activity->code }} · {{ $activity->unionGroup?->name }} · {{ $activity->activityType?->name }}</p>
        </div>
        <div class="flex gap-2">
            @can('update', $activity)
                <x-btn href="{{ route('activities.edit', $activity) }}" variant="secondary">Chỉnh sửa</x-btn>
            @endcan
            @can('delete', $activity)
                <x-delete-form :action="route('activities.destroy', $activity)" confirm="Xóa hoạt động này? Toàn bộ người tham gia và minh chứng liên quan cũng sẽ bị xóa." class="rounded-lg border border-red-200" />
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Thông tin tổng quan">
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-400">Người phụ trách</dt><dd class="font-medium text-slate-700">{{ $activity->responsibleUser?->name ?: ($activity->responsible_name ?: '—') }}</dd></div>
                    <div><dt class="text-slate-400">Địa điểm</dt><dd class="font-medium text-slate-700">{{ $activity->location ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Thời gian bắt đầu</dt><dd class="font-medium text-slate-700">{{ $activity->start_time->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-400">Thời gian kết thúc</dt><dd class="font-medium text-slate-700">{{ $activity->end_time->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-400">Số lượng dự kiến</dt><dd class="font-medium text-slate-700">{{ $activity->expected_quantity }}</dd></div>
                    <div><dt class="text-slate-400">Số lượng thực tế</dt><dd class="font-medium text-slate-700">{{ $activity->actual_quantity }}</dd></div>
                    @if($activity->budget)
                        <div><dt class="text-slate-400">Kinh phí</dt><dd class="font-medium text-slate-700">{{ number_format($activity->budget, 0, ',', '.') }} đ</dd></div>
                    @endif
                    <div><dt class="text-slate-400">Người tạo</dt><dd class="font-medium text-slate-700">{{ $activity->creator?->name ?: '—' }}</dd></div>
                </dl>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="mb-1 text-xs font-medium text-slate-400">Tiến độ thực hiện</p>
                    <x-progress-bar :value="$activity->progress" />
                </div>

                @if($activity->counts_for_evaluation)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-1 text-xs font-medium text-slate-400">Điểm thi đua</p>
                        <p class="text-sm text-slate-600">
                            Tối đa <b>{{ rtrim(rtrim($activity->evaluation_max_score, '0'), '.') }}đ</b>
                            @if($activity->status === \App\Models\Activity::STATUS_COMPLETED)
                                — đã đạt <b class="text-green-600">{{ $activity->earnedEvaluationScore() }}đ</b> (tính vào điểm thưởng của {{ $activity->unionGroup?->name }})
                            @else
                                — chỉ ghi nhận điểm khi hoạt động <b>Đã hoàn thành</b>
                            @endif
                        </p>
                    </div>
                @endif

                @if($activity->goal)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-1 text-xs font-medium text-slate-400">Mục tiêu</p>
                        <p class="whitespace-pre-line text-sm text-slate-600">{{ $activity->goal }}</p>
                    </div>
                @endif

                @if($activity->content)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-1 text-xs font-medium text-slate-400">Nội dung chi tiết</p>
                        <p class="whitespace-pre-line text-sm text-slate-600">{{ $activity->content }}</p>
                    </div>
                @endif

                @if($activity->note)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="mb-1 text-xs font-medium text-slate-400">Ghi chú</p>
                        <p class="whitespace-pre-line text-sm text-slate-600">{{ $activity->note }}</p>
                    </div>
                @endif
            </x-card>

            <x-card no-padding>
                <x-slot name="title">Người tham gia ({{ $activity->participants()->count() }})</x-slot>
                <x-slot name="actions">
                    <div class="flex gap-2">
                        <x-btn href="{{ route('activities.participants.export', $activity) }}" variant="ghost">Xuất Excel</x-btn>
                        @if($canManageParticipants)
                            <x-btn href="{{ route('activities.participants.create', $activity) }}" variant="secondary">+ Thêm người tham gia</x-btn>
                        @endif
                    </div>
                </x-slot>

                @if($participants->isEmpty())
                    <div class="p-5"><x-empty-state title="Chưa có người tham gia" /></div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Đoàn viên</th>
                                    <th class="px-5 py-3">Vai trò/Ghi chú</th>
                                    <th class="px-5 py-3">Trạng thái</th>
                                    <th class="px-5 py-3">Thời gian đăng ký</th>
                                    @if($canManageParticipants)
                                        <th class="px-5 py-3 text-right">Thao tác</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($participants as $participant)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3">
                                            <a href="{{ route('members.show', $participant->member) }}" class="font-medium text-blue-600 hover:underline">{{ $participant->member?->full_name }}</a>
                                            <p class="text-xs text-slate-400">{{ $participant->member?->code }}</p>
                                        </td>
                                        <td class="px-5 py-3 text-slate-500">{{ $participant->role_note ?: '—' }}</td>
                                        <td class="px-5 py-3"><x-status-badge :status="$participant->status" /></td>
                                        <td class="px-5 py-3 text-slate-500">{{ $participant->registered_at?->format('d/m/Y H:i') }}</td>
                                        @if($canManageParticipants)
                                            <td class="px-5 py-3 text-right">
                                                <x-delete-form :action="route('activities.participants.destroy', [$activity, $participant])" confirm="Xóa người tham gia này khỏi hoạt động?" />
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 px-5 py-4">{{ $participants->links() }}</div>
                @endif
            </x-card>

            <x-card>
                <x-slot name="title">Hình ảnh / Minh chứng ({{ $evidences->count() }})</x-slot>

                @if($canManageEvidences)
                    <form method="POST" action="{{ route('activities.evidences.store', $activity) }}" enctype="multipart/form-data" class="mb-5 rounded-lg border border-dashed border-slate-300 p-4">
                        @csrf
                        <x-input-label value="Tải lên hình ảnh/tài liệu (jpg, png, webp, pdf — tối đa 10MB/tệp)" />
                        <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                               class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                        <x-input-error :messages="\Illuminate\Support\Arr::flatten($errors->get('files.*'))" class="mt-1" />
                        <input type="text" name="description" placeholder="Mô tả (không bắt buộc)" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <div class="mt-3 text-right">
                            <x-btn type="submit" variant="secondary">Tải lên</x-btn>
                        </div>
                    </form>
                @endif

                @if($evidences->isEmpty())
                    <x-empty-state title="Chưa có hình ảnh/minh chứng nào" />
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($evidences as $evidence)
                            <div class="group relative overflow-hidden rounded-lg border border-slate-200">
                                @if($evidence->isImage())
                                    <a href="{{ Storage::url($evidence->file_path) }}" target="_blank">
                                        <img src="{{ Storage::url($evidence->file_path) }}" class="h-32 w-full object-cover" alt="{{ $evidence->file_name }}">
                                    </a>
                                @else
                                    <a href="{{ Storage::url($evidence->file_path) }}" target="_blank" class="flex h-32 w-full flex-col items-center justify-center gap-1 bg-slate-50 text-slate-400">
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <span class="text-xs font-medium">PDF</span>
                                    </a>
                                @endif
                                <div class="p-2">
                                    <p class="truncate text-xs font-medium text-slate-600" title="{{ $evidence->file_name }}">{{ $evidence->file_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $evidence->humanFileSize() }} · {{ $evidence->uploader?->name }}</p>
                                </div>
                                <div class="absolute right-1 top-1 flex gap-1 opacity-0 transition group-hover:opacity-100">
                                    <a href="{{ route('activities.evidences.download', [$activity, $evidence]) }}" class="rounded-full bg-white/90 p-1.5 text-slate-600 shadow hover:bg-white" title="Tải xuống">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" /></svg>
                                    </a>
                                    @if($canManageEvidences)
                                        <form method="POST" action="{{ route('activities.evidences.destroy', [$activity, $evidence]) }}" onsubmit="return confirm('Xóa minh chứng này?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rounded-full bg-white/90 p-1.5 text-red-500 shadow hover:bg-white" title="Xóa">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Tổ công đoàn phụ trách">
                <p class="font-medium text-slate-700">{{ $activity->unionGroup?->name }}</p>
                <p class="text-sm text-slate-500">{{ $activity->unionGroup?->department }}</p>
                <x-btn href="{{ route('union-groups.show', $activity->unionGroup) }}" variant="ghost" class="mt-3 !px-0">Xem chi tiết tổ →</x-btn>
            </x-card>

            @if($activity->collaboratingGroups->isNotEmpty())
                <x-card title="Đơn vị phối hợp">
                    <ul class="space-y-2 text-sm">
                        @foreach($activity->collaboratingGroups as $group)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ route('union-groups.show', $group) }}" class="font-medium text-blue-600 hover:underline">{{ $group->name }}</a>
                                <span @class([
                                    'inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-blue-100 text-blue-700' => $group->pivot->role === 'phoi_hop',
                                    'bg-slate-100 text-slate-600' => $group->pivot->role === 'tham_gia',
                                ])>{{ \App\Models\Activity::COLLABORATION_ROLES[$group->pivot->role] ?? $group->pivot->role }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            <x-card title="Thống kê tham gia">
                @php
                    $total = $activity->participants()->count();
                    $attended = $activity->participants()->where('status', 'attended')->count();
                    $rate = $total > 0 ? round($attended / $total * 100) : 0;
                @endphp
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Tổng đăng ký</span><span class="font-semibold text-slate-700">{{ $total }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Đã tham gia</span><span class="font-semibold text-slate-700">{{ $attended }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Tỷ lệ tham gia</span><span class="font-semibold text-slate-700">{{ $rate }}%</span></div>
                </div>
            </x-card>

            @if($activity->statusHistories->isNotEmpty())
                <x-card title="Lịch sử cập nhật">
                    <ol class="space-y-4 border-l border-slate-200 pl-4 text-sm">
                        @foreach($activity->statusHistories as $history)
                            <li class="relative">
                                <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                <p class="font-medium text-slate-700">{{ \App\Models\Activity::STATUSES[$history->status] ?? $history->status }} — {{ $history->progress }}%</p>
                                <p class="text-xs text-slate-400">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->changer?->name }}</p>
                                @if($history->note)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $history->note }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
