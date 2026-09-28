<x-app-layout title="Nhập người tham gia từ ảnh" :breadcrumbs="['Hoạt động' => route('activities.index'), $activity->name => route('activities.show', $activity), 'Nhập từ ảnh danh sách' => null]">
    <x-card :title="'Nhập người tham gia cho: '.$activity->name">
        <div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
            <p class="font-medium">Cách hoạt động</p>
            <ol class="mt-1 list-decimal space-y-1 pl-5">
                <li>Chụp hoặc quét tờ danh sách điểm danh/đăng ký (ảnh JPG, PNG, WebP hoặc PDF).</li>
                <li>AI đọc chữ và liệt kê tên người có trong danh sách.</li>
                <li>Hệ thống tự đối chiếu với danh sách đoàn viên. Bạn kiểm tra, sửa các dòng chưa chắc chắn rồi mới bấm lưu.</li>
            </ol>
            <p class="mt-2 text-xs text-blue-700">Cũng có thể tải tệp TXT/CSV (mỗi dòng một người, cột thứ hai là mã đoàn viên nếu có); tệp văn bản được đọc trực tiếp, không dùng AI.</p>
        </div>

        @if($provider === 'mock')
            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                Đang ở <b>chế độ giả lập</b> (chưa cấu hình AI thật): ảnh/PDF sẽ không được đọc thật mà chỉ hiển thị kết quả minh họa. Đặt <code>AI_PROVIDER=gemini</code> và <code>GEMINI_API_KEY</code> trong file .env để dùng AI thật.
            </div>
        @else
            <div class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                Ảnh/PDF danh sách sẽ được gửi tới dịch vụ AI ({{ ucfirst($provider) }}) để đọc chữ. Chỉ tải lên tài liệu được phép chia sẻ; danh sách đoàn viên trong hệ thống <b>không</b> được gửi đi.
            </div>
        @endif

        <form method="POST" action="{{ route('activities.participants.import.analyze', $activity) }}" enctype="multipart/form-data" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <x-input-label for="file" value="Ảnh/PDF/TXT/CSV danh sách *" />
            <input id="file" type="file" name="file" required accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv"
                   class="mt-1 block w-full rounded-lg border border-slate-300 text-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium">
            <p class="mt-1 text-xs text-slate-400">Tối đa {{ round($maxKb / 1024, 1) }} MB.</p>
            <x-input-error :messages="$errors->get('file')" class="mt-2" />

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activities.show', $activity) }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit" x-bind:disabled="busy"><span x-show="!busy">Đọc danh sách</span><span x-show="busy" x-cloak>Đang đọc, vui lòng chờ...</span></x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
