@php
    $months = range(1, 12);
    $input = 'mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

<x-app-layout title="Trợ lý soạn văn bản" :breadcrumbs="['Trợ lý soạn văn bản' => null]">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Soạn bản nháp" subtitle="Dựa trên số liệu có sẵn trong hệ thống" class="lg:col-span-1 self-start">
            @if($provider === 'mock')
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <b>Chế độ giả lập:</b> văn bản được dựng theo mẫu có sẵn, chưa dùng AI. Đặt <code>AI_PROVIDER=gemini</code> và <code>GEMINI_API_KEY</code> trong .env để AI soạn.
                </div>
            @else
                <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                    Số liệu tổng hợp (không có tên đoàn viên) sẽ được gửi tới dịch vụ AI ({{ ucfirst($provider) }}) để soạn văn bản.
                </div>
            @endif

            <x-input-error :messages="$errors->get('ai')" class="mb-3" />

            <form method="POST" action="{{ route('ai.documents.generate') }}" class="space-y-4"
                  x-data="{ type: @js($selected['type']), busy: false }" @submit="busy = true">
                @csrf

                <div>
                    <x-input-label for="type" value="Loại văn bản *" />
                    <select id="type" name="type" x-model="type" class="{{ $input }}" required>
                        <option value="">-- Chọn loại văn bản --</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>

                <div x-show="type === 'activity_summary' || type === 'invitation'" x-cloak>
                    <x-input-label for="activity_id" value="Hoạt động *" />
                    <select id="activity_id" name="activity_id" class="{{ $input }}">
                        <option value="">-- Chọn hoạt động --</option>
                        @foreach($activities as $activity)
                            <option value="{{ $activity->id }}" @selected((string) $selected['activity_id'] === (string) $activity->id)>{{ $activity->code }} - {{ $activity->name }} ({{ $activity->unionGroup?->name }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('activity_id')" class="mt-1" />
                </div>

                <div x-show="type === 'monthly_group'" x-cloak class="space-y-4">
                    <div>
                        <x-input-label for="union_group_id" value="Tổ công đoàn *" />
                        <select id="union_group_id" name="union_group_id" class="{{ $input }}">
                            <option value="">-- Chọn tổ --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected((string) $selected['union_group_id'] === (string) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('union_group_id')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="month" value="Tháng" />
                            <select id="month" name="month" class="{{ $input }}">
                                @foreach($months as $m)
                                    <option value="{{ $m }}" @selected((int) $selected['month'] === $m)>Tháng {{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="year" value="Năm" />
                            <input id="year" type="number" name="year" value="{{ $selected['year'] }}" class="{{ $input }}">
                        </div>
                    </div>
                </div>

                <div x-show="type === 'yearly_school'" x-cloak>
                    <x-input-label for="academic_year" value="Năm học" />
                    <select id="academic_year" name="academic_year" class="{{ $input }}">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" @selected($selected['academic_year'] === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="extra" value="Yêu cầu bổ sung (không bắt buộc)" />
                    <textarea id="extra" name="extra" rows="2" maxlength="500" class="{{ $input }}" placeholder="VD: nhấn mạnh tinh thần đoàn kết, viết ngắn gọn hơn">{{ $selected['extra'] }}</textarea>
                </div>

                <x-btn type="submit" class="w-full" x-bind:disabled="busy || !type">
                    <span x-show="!busy">{{ $draft ? 'Soạn lại' : 'Soạn bản nháp' }}</span>
                    <span x-show="busy" x-cloak>Đang soạn, vui lòng chờ...</span>
                </x-btn>
            </form>
        </x-card>

        <x-card title="Bản nháp" subtitle="Bạn có thể sửa trực tiếp trước khi sao chép hoặc tải về" class="lg:col-span-2">
            <div x-data="{ text: @js($draft['text'] ?? ''), copied: false }">
            @if($draft)
                @if($draft['simulated'])
                    <p class="mb-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Bản nháp dựng theo mẫu (chế độ giả lập), chưa dùng AI.</p>
                @else
                    <p class="mb-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-800">Soạn bởi {{ ucfirst($draft['provider']) }} ({{ $draft['model'] }}). Đây là bản nháp, hãy đọc kỹ và đối chiếu số liệu trước khi sử dụng; chỗ ghi "[cần bổ sung]" là thông tin hệ thống chưa có.</p>
                @endif

                <textarea x-model="text" rows="24" class="block w-full rounded-lg border-slate-300 font-serif text-sm leading-relaxed shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>

                <div class="mt-4 flex flex-wrap justify-end gap-3">
                    <x-btn type="button" variant="secondary"
                           @click="navigator.clipboard.writeText(text).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                        <span x-show="!copied">Sao chép</span><span x-show="copied" x-cloak>Đã sao chép</span>
                    </x-btn>
                    <form method="POST" action="{{ route('ai.documents.download') }}">
                        @csrf
                        <input type="hidden" name="title" value="{{ $draft['title'] }}">
                        <input type="hidden" name="content" :value="text">
                        <x-btn type="submit">Tải file Word (.docx)</x-btn>
                    </form>
                </div>
            @else
                <x-empty-state title="Chưa có bản nháp" description="Chọn loại văn bản và thông tin ở bên trái rồi bấm Soạn bản nháp." />
            @endif
            </div>
        </x-card>
    </div>
</x-app-layout>
