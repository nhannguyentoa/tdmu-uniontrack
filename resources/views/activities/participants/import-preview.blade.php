@php
    $existingIds = $activity->participants()->pluck('member_id')->all();
    $counts = collect($rows)->countBy('status');
    $badge = [
        'exact' => ['Khớp', 'bg-green-100 text-green-700'],
        'fuzzy' => ['Gần giống - cần kiểm tra', 'bg-amber-100 text-amber-700'],
        'ambiguous' => ['Nhiều người trùng tên', 'bg-orange-100 text-orange-700'],
        'unmatched' => ['Chưa khớp', 'bg-red-100 text-red-700'],
        'duplicate' => ['Đã có/trùng', 'bg-slate-100 text-slate-600'],
    ];
@endphp

<x-app-layout title="Xác nhận danh sách tham gia" :breadcrumbs="['Hoạt động' => route('activities.index'), $activity->name => route('activities.show', $activity), 'Xác nhận danh sách' => null]">
    <x-card :title="'Kết quả đọc danh sách: '.$activity->name" subtitle="Kiểm tra từng dòng trước khi lưu. Chưa có gì được ghi vào hệ thống.">
        @if($simulated)
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ $notice }}</div>
        @elseif($notice)
            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Nhận xét của AI: {{ $notice }}</div>
        @endif

        <div class="mb-4 flex flex-wrap gap-2 text-xs">
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">Đọc được {{ count($rows) }} dòng</span>
            <span class="rounded-full bg-green-100 px-3 py-1 font-medium text-green-700">{{ $counts->get('exact', 0) }} khớp</span>
            <span class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700">{{ $counts->get('fuzzy', 0) }} gần giống</span>
            <span class="rounded-full bg-orange-100 px-3 py-1 font-medium text-orange-700">{{ $counts->get('ambiguous', 0) }} trùng tên</span>
            <span class="rounded-full bg-red-100 px-3 py-1 font-medium text-red-700">{{ $counts->get('unmatched', 0) }} chưa khớp</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">{{ $counts->get('duplicate', 0) }} đã có/trùng</span>
        </div>

        @if(count($rows) === 0)
            <x-empty-state title="Không đọc được tên nào" description="Hãy thử chụp lại ảnh rõ nét, đủ sáng và thẳng góc hơn." />
            <div class="mt-4 flex justify-end">
                <x-btn href="{{ route('activities.participants.import', $activity) }}" variant="secondary">Thử tệp khác</x-btn>
            </div>
        @else
            <form method="POST" action="{{ route('activities.participants.import.confirm', $activity) }}">
                @csrf
                <input type="hidden" name="log_id" value="{{ $log->id }}">
                <x-input-error :messages="$errors->get('rows')" class="mb-3" />

                <div class="mb-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2 w-10">#</th>
                                <th class="px-3 py-2">AI đọc được</th>
                                <th class="px-3 py-2">Tình trạng</th>
                                <th class="px-3 py-2 min-w-72">Đoàn viên sẽ thêm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($rows as $i => $row)
                                @php
                                    $selectedId = old("rows.$i.member_id", $row['member_id']);
                                    $candidateIds = collect($row['candidates']);
                                    [$label, $class] = $badge[$row['status']];
                                @endphp
                                <tr class="align-top">
                                    <td class="px-3 py-2 text-slate-400">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-700">{{ $row['name'] }}</p>
                                        @if($row['code'])<p class="text-xs text-slate-400">Mã: {{ $row['code'] }}</p>@endif
                                        @if($row['confidence'] !== null)<p class="text-xs text-slate-400">Độ tin cậy đọc chữ: {{ round($row['confidence'] * 100) }}%</p>@endif
                                        @if($row['note'])<p class="text-xs text-amber-600">{{ $row['note'] }}</p>@endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $class }}">{{ $label }}</span>
                                        @if($row['reason'])<p class="mt-1 max-w-56 text-xs text-slate-500">{{ $row['reason'] }}</p>@endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="rows[{{ $i }}][suggested_id]" value="{{ $row['member_id'] }}">
                                        <select name="rows[{{ $i }}][member_id]" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">— Bỏ qua dòng này —</option>
                                            @if($candidateIds->isNotEmpty())
                                                <optgroup label="Gợi ý">
                                                    @foreach($candidateIds as $cid)
                                                        @php $m = $roster->firstWhere('id', $cid); @endphp
                                                        @if($m)
                                                            <option value="{{ $m->id }}" @selected((string) $selectedId === (string) $m->id) @disabled(in_array($m->id, $existingIds))>{{ $m->full_name }} ({{ $m->code }}) - {{ $m->unionGroup?->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            <optgroup label="Tất cả đoàn viên">
                                                @foreach($roster as $m)
                                                    @continue($candidateIds->contains($m->id))
                                                    <option value="{{ $m->id }}" @selected((string) $selectedId === (string) $m->id) @disabled(in_array($m->id, $existingIds))>{{ $m->full_name }} ({{ $m->code }}) - {{ $m->unionGroup?->name }}{{ in_array($m->id, $existingIds) ? ' [đã có]' : '' }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="Trạng thái tham gia *" />
                        <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach(['attended', 'registered'] as $key)
                                <option value="{{ $key }}" @selected(old('status', 'attended') === $key)>{{ \App\Models\ActivityParticipant::STATUSES[$key] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Vai trò/Ghi chú" />
                        <x-text-input name="role_note" class="mt-1 block w-full" :value="old('role_note')" placeholder="VD: Thành viên tham dự" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-btn href="{{ route('activities.participants.import', $activity) }}" variant="secondary">Làm lại</x-btn>
                    <x-btn type="submit">Lưu danh sách tham gia</x-btn>
                </div>
            </form>
        @endif
    </x-card>
</x-app-layout>
