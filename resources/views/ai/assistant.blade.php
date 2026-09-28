<x-app-layout title="Hỏi đáp dữ liệu" :breadcrumbs="['Hỏi đáp dữ liệu' => null]">
    <div class="mx-auto max-w-3xl"
         x-data="{
            messages: [],
            question: '',
            busy: false,
            error: '',
            async send(text) {
                text = (text ?? this.question).trim();
                if (!text || this.busy) return;
                this.error = '';
                const history = this.messages.slice(-8).map(m => ({ role: m.role, text: m.text }));
                this.messages.push({ role: 'user', text });
                this.question = '';
                this.busy = true;
                this.$nextTick(() => this.scroll());
                try {
                    const res = await fetch(@js(route('ai.assistant.ask')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ question: text, history }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        this.error = data.error || (data.errors ? Object.values(data.errors)[0][0] : 'Có lỗi xảy ra, vui lòng thử lại.');
                    } else {
                        this.messages.push({ role: 'assistant', text: data.answer, tools: data.tools });
                    }
                } catch (e) {
                    this.error = 'Không kết nối được tới máy chủ. Vui lòng thử lại.';
                }
                this.busy = false;
                this.$nextTick(() => this.scroll());
            },
            scroll() { const el = this.$refs.list; el.scrollTop = el.scrollHeight; },
            toolLabel(name) { return ({ list_activities: 'Danh sách hoạt động', group_overview: 'Tổng quan các tổ', evaluation_summary: 'Điểm thi đua', participation_stats: 'Lượt tham gia', plan_status: 'Kế hoạch' })[name] || name; },
         }">
        <x-card title="Hỏi đáp dữ liệu bằng tiếng Việt" subtitle="Trợ lý tra cứu số liệu hoạt động, kế hoạch và thi đua. Chỉ đọc, không thay đổi dữ liệu.">
            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                <b>Phạm vi dữ liệu của bạn:</b> {{ $scope }}
                @if($provider === 'mock')
                    <p class="mt-1 text-amber-700"><b>Chế độ giả lập:</b> chỉ hiểu một số câu hỏi mẫu bên dưới. Đặt <code>AI_PROVIDER=gemini</code> và <code>GEMINI_API_KEY</code> để hỏi tự do.</p>
                @else
                    <p class="mt-1">Câu hỏi và số liệu tổng hợp (không có tên đoàn viên) được gửi tới dịch vụ AI ({{ ucfirst($provider) }}) để trả lời.</p>
                @endif
            </div>

            <div x-ref="list" class="mb-4 h-96 space-y-3 overflow-y-auto rounded-lg border border-slate-200 bg-white p-4">
                <template x-if="messages.length === 0">
                    <div>
                        <p class="mb-2 text-sm text-slate-500">Bạn có thể hỏi, ví dụ:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($examples as $example)
                                <button type="button" @click="send(@js($example))" class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs text-blue-700 hover:bg-blue-100">{{ $example }}</button>
                            @endforeach
                        </div>
                    </div>
                </template>
                <template x-for="(m, i) in messages" :key="i">
                    <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="m.role === 'user' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800'" class="max-w-[85%] rounded-2xl px-4 py-2 text-sm">
                            <p class="whitespace-pre-line" x-text="m.text"></p>
                            <p x-show="m.tools && m.tools.length" class="mt-2 text-xs text-slate-400" x-text="'Đã tra cứu: ' + (m.tools || []).map(toolLabel).join(', ')"></p>
                        </div>
                    </div>
                </template>
                <div x-show="busy" x-cloak class="text-sm text-slate-400">Đang tra cứu dữ liệu...</div>
            </div>

            <p x-show="error" x-cloak class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" x-text="error"></p>

            <form @submit.prevent="send()" class="flex gap-2">
                <input type="text" x-model="question" maxlength="500" placeholder="Nhập câu hỏi, ví dụ: Tổ nào chậm tiến độ tháng này?"
                       class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <x-btn type="submit" x-bind:disabled="busy || !question.trim()">Gửi</x-btn>
            </form>
            <p class="mt-2 text-xs text-slate-400">Kết quả do AI diễn giải từ số liệu hệ thống; hãy đối chiếu với trang Báo cáo khi cần số liệu chính thức.</p>
        </x-card>
    </div>
</x-app-layout>
