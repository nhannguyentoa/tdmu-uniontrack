@php
    $counts = collect($rows)->countBy('status');
    $badge = [
        'valid' => ['Hợp lệ', 'bg-green-100 text-green-700'],
        'duplicate' => ['Trùng mã - bỏ qua', 'bg-slate-100 text-slate-600'],
        'error' => ['Lỗi', 'bg-red-100 text-red-700'],
    ];
    $genderMap = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
    $statusMap = ['active' => 'Đang hoạt động', 'inactive' => 'Ngưng hoạt động', 'transferred' => 'Đã chuyển'];
    $date = fn ($v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? \Carbon\Carbon::parse($v)->format('d/m/Y') : $v;
@endphp

<x-app-layout title="Xem trước danh sách đoàn viên" :breadcrumbs="['Đoàn viên' => route('members.index'), 'Nhập từ Excel' => route('members.import'), 'Xem trước' => null]">
    <x-card :title="'Xem trước: '.$fileName" :subtitle="'Các đoàn viên hợp lệ sẽ được thêm vào '.$group->name.'. Chưa có gì được ghi vào hệ thống.'">
        <div class="mb-4 flex flex-wrap gap-2 text-xs">
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">Đọc được {{ count($rows) }} dòng</span>
            <span class="rounded-full bg-green-100 px-3 py-1 font-medium text-green-700">{{ $counts->get('valid', 0) }} hợp lệ</span>
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">{{ $counts->get('duplicate', 0) }} trùng mã</span>
            <span class="rounded-full bg-red-100 px-3 py-1 font-medium text-red-700">{{ $counts->get('error', 0) }} lỗi</span>
        </div>

        @if(count($rows) === 0)
            <x-empty-state title="Tệp không có dòng dữ liệu nào" description="Kiểm tra lại tệp: dưới dòng tiêu đề cần có ít nhất một đoàn viên." />
            <div class="mt-4 flex justify-end">
                <x-btn href="{{ route('members.import') }}" variant="secondary">Chọn tệp khác</x-btn>
            </div>
        @else
            <form method="POST" action="{{ route('members.import.confirm') }}" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <div class="mb-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2 w-10">Thêm</th>
                                <th class="px-3 py-2">Dòng</th>
                                <th class="px-3 py-2">Mã ĐV</th>
                                <th class="px-3 py-2">Họ và tên</th>
                                <th class="px-3 py-2">Ngày sinh / Giới tính</th>
                                <th class="px-3 py-2">Liên hệ</th>
                                <th class="px-3 py-2">Chức vụ / Đơn vị</th>
                                <th class="px-3 py-2">Trạng thái</th>
                                <th class="px-3 py-2 min-w-56">Kết quả kiểm tra</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($rows as $row)
                                @php
                                    $d = $row['data'];
                                    [$label, $class] = $badge[$row['status']];
                                @endphp
                                <tr class="align-top {{ $row['status'] === 'valid' ? '' : 'bg-slate-50/60 text-slate-500' }}">
                                    <td class="px-3 py-2">
                                        @if($row['status'] === 'valid')
                                            <input type="checkbox" name="lines[]" value="{{ $row['line'] }}" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">{{ $row['line'] }}</td>
                                    <td class="px-3 py-2 font-medium">{!! $d['code'] !== null ? e($d['code']) : '<span class="text-xs italic text-slate-400">tự cấp</span>' !!}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $d['full_name'] ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <p>{{ $date($d['dob']) ?: '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $genderMap[$d['gender']] ?? $d['gender'] }}</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p>{{ $d['phone'] ?: '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $d['email'] }}</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p>{{ $d['position'] ?: '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $d['department'] }}</p>
                                    </td>
                                    <td class="px-3 py-2">{{ $statusMap[$d['status']] ?? $d['status'] }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $class }}">{{ $label }}</span>
                                        @foreach($row['errors'] as $error)
                                            <p class="mt-1 text-xs {{ $row['status'] === 'error' ? 'text-red-600' : 'text-slate-500' }}">{{ $error }}</p>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($counts->get('error', 0))
                    <p class="mb-4 text-sm text-slate-500">Các dòng lỗi sẽ không được thêm. Sửa lại trong tệp Excel rồi tải lên lần nữa nếu muốn thêm các dòng đó.</p>
                @endif

                <div class="flex justify-end gap-3">
                    <x-btn href="{{ route('members.import') }}" variant="secondary">Chọn tệp khác</x-btn>
                    @if($counts->get('valid', 0))
                        <x-btn type="submit" x-bind:disabled="busy">Thêm các đoàn viên đã chọn vào {{ $group->name }}</x-btn>
                    @endif
                </div>
            </form>
        @endif
    </x-card>
</x-app-layout>
