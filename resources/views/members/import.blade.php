<x-app-layout title="Nhập đoàn viên từ Excel" :breadcrumbs="['Đoàn viên' => route('members.index'), 'Nhập từ Excel' => null]">
    <x-card title="Nhập nhanh đoàn viên từ tệp Excel">
        <div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <p class="font-medium">Cách hoạt động</p>
            <ol class="mt-1 list-decimal space-y-1 pl-5">
                <li>Chọn tổ công đoàn sẽ nhận các đoàn viên trong tệp.</li>
                <li>Tải lên tệp Excel danh sách đoàn viên. Chỉ cột <b>Họ và tên</b> là bắt buộc; các cột khác như Mã đoàn viên, Ngày sinh, Giới tính, Email, Số điện thoại, Chức vụ, Đơn vị, Ngày tham gia, Trạng thái có thể để trống.</li>
                <li>Hệ thống hiển thị trước từng dòng: dòng hợp lệ, dòng trùng mã (bỏ qua) và dòng lỗi kèm lý do. Bạn kiểm tra rồi mới bấm lưu.</li>
            </ol>
            <p class="mt-2 text-xs text-blue-700">
                Dòng để trống mã sẽ được hệ thống tự cấp mã (DV-xxxxx). Tệp xuất từ nút "Xuất Excel" của trang Danh sách đoàn viên cũng nhập lại được; cột Tổ công đoàn trong tệp được bỏ qua.
            </p>
            <a href="{{ route('members.import.template') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline">⬇ Tải tệp mẫu (.xlsx)</a>
        </div>

        @if($unionGroups->isEmpty())
            <x-empty-state title="Bạn chưa được phân công phụ trách tổ nào" description="Liên hệ Quản trị viên để được gán tổ công đoàn trước khi nhập đoàn viên." />
        @else
            <form method="POST" action="{{ route('members.import.analyze') }}" enctype="multipart/form-data" x-data="{ busy: false }" @submit="busy = true" class="space-y-5">
                @csrf
                <div>
                    <x-input-label for="union_group_id" value="Tổ công đoàn *" />
                    <select id="union_group_id" name="union_group_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @if($unionGroups->count() > 1)
                            <option value="">-- Chọn tổ --</option>
                        @endif
                        @foreach($unionGroups as $group)
                            <option value="{{ $group->id }}" @selected(old('union_group_id', request('union_group_id')) == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('union_group_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="file" value="Tệp danh sách đoàn viên *" />
                    <input id="file" type="file" name="file" required accept=".xlsx,.xls,.csv"
                           class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium">
                    <p class="mt-1 text-xs text-slate-400">Định dạng .xlsx, .xls hoặc .csv; tối đa 5 MB và {{ $maxRows }} dòng mỗi lần. Hệ thống đọc trang tính đầu tiên.</p>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <x-btn href="{{ route('members.index') }}" variant="secondary">Hủy</x-btn>
                    <x-btn type="submit" x-bind:disabled="busy"><span x-show="!busy">Đọc tệp và xem trước</span><span x-show="busy" x-cloak>Đang đọc tệp...</span></x-btn>
                </div>
            </form>
        @endif
    </x-card>
</x-app-layout>
