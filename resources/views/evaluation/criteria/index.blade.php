<x-app-layout title="Tiêu chí thi đua" :breadcrumbs="['Chấm điểm thi đua' => null, 'Tiêu chí' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                @endforeach
            </select>
        </form>

        <x-btn x-data @click="$dispatch('open-modal', 'create-criterion')">+ Thêm tiêu chí</x-btn>
    </div>

    <x-card no-padding>
        @if($criteria->isEmpty())
            <div class="p-5"><x-empty-state title="Chưa có tiêu chí thi đua cho năm học này" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">STT</th>
                            <th class="px-5 py-3">Nhóm</th>
                            <th class="px-5 py-3">Nội dung tiêu chí</th>
                            <th class="px-5 py-3">Ban thẩm định</th>
                            <th class="px-5 py-3 text-center">Điểm chuẩn</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($criteria as $criterion)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 text-slate-500">{{ $criterion->order_no }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ \App\Models\EvaluationCriterion::GROUP_LABELS[$criterion->group_label] ?? $criterion->group_label }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ \Illuminate\Support\Str::limit($criterion->content, 100) }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $criterion->department?->name ?: '—' }}</td>
                                <td class="px-5 py-3 text-center font-medium text-slate-700">{{ rtrim(rtrim($criterion->max_score, '0'), '.') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1" x-data>
                                        <x-btn variant="ghost" @click="$dispatch('open-modal', 'edit-criterion-{{ $criterion->id }}')">Sửa</x-btn>
                                        <x-delete-form :action="route('evaluation-criteria.destroy', $criterion)" confirm="Xóa tiêu chí này? Chỉ xóa được khi chưa có điểm chấm." />
                                    </div>
                                </td>
                            </tr>

                            <x-modal name="edit-criterion-{{ $criterion->id }}" max-width="lg">
                                <form method="POST" action="{{ route('evaluation-criteria.update', $criterion) }}" class="p-6">
                                    @csrf @method('PUT')
                                    <h3 class="mb-4 text-lg font-semibold text-slate-800">Chỉnh sửa tiêu chí #{{ $criterion->order_no }}</h3>
                                    <div class="space-y-4">
                                        <input type="hidden" name="academic_year" value="{{ $criterion->academic_year }}">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <x-input-label value="Nhóm *" />
                                                <select name="group_label" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                                    @foreach(\App\Models\EvaluationCriterion::GROUP_LABELS as $key => $label)
                                                        <option value="{{ $key }}" @selected($criterion->group_label === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label value="STT *" />
                                                <x-text-input name="order_no" type="number" min="1" class="mt-1 block w-full" value="{{ $criterion->order_no }}" required />
                                            </div>
                                        </div>
                                        <div>
                                            <x-input-label value="Nội dung tiêu chí *" />
                                            <textarea name="content" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>{{ $criterion->content }}</textarea>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <x-input-label value="Điểm chuẩn *" />
                                                <x-text-input name="max_score" type="number" min="0" step="0.5" class="mt-1 block w-full" value="{{ $criterion->max_score }}" required />
                                            </div>
                                            <div>
                                                <x-input-label value="Ban thẩm định" />
                                                <select name="department_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <option value="">-- Không phân công --</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" @selected($criterion->department_id === $department->id)>{{ $department->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex justify-end gap-2">
                                        <x-btn type="button" variant="secondary" @click="$dispatch('close')">Hủy</x-btn>
                                        <x-btn type="submit">Cập nhật</x-btn>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-modal name="create-criterion" max-width="lg">
        <form method="POST" action="{{ route('evaluation-criteria.store') }}" class="p-6">
            @csrf
            <h3 class="mb-4 text-lg font-semibold text-slate-800">Thêm tiêu chí thi đua</h3>
            <div class="space-y-4">
                <div>
                    <x-input-label value="Năm học *" />
                    <x-text-input name="academic_year" class="mt-1 block w-full" value="{{ old('academic_year', $academicYear) }}" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label value="Nhóm *" />
                        <select name="group_label" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            @foreach(\App\Models\EvaluationCriterion::GROUP_LABELS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="STT *" />
                        <x-text-input name="order_no" type="number" min="1" class="mt-1 block w-full" required />
                    </div>
                </div>
                <div>
                    <x-input-label value="Nội dung tiêu chí *" />
                    <textarea name="content" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label value="Điểm chuẩn *" />
                        <x-text-input name="max_score" type="number" min="0" step="0.5" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label value="Ban thẩm định" />
                        <select name="department_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Không phân công --</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" @click="$dispatch('close')">Hủy</x-btn>
                <x-btn type="submit">Lưu</x-btn>
            </div>
        </form>
    </x-modal>
</x-app-layout>
