@props(['status'])

@php
    $map = [
        'not_started' => ['label' => 'Chưa bắt đầu', 'class' => 'bg-slate-100 text-slate-600'],
        'preparing' => ['label' => 'Đang chuẩn bị', 'class' => 'bg-sky-100 text-sky-700'],
        'in_progress' => ['label' => 'Đang thực hiện', 'class' => 'bg-amber-100 text-amber-700'],
        'completed' => ['label' => 'Đã hoàn thành', 'class' => 'bg-green-100 text-green-700'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-red-100 text-red-700'],
        'active' => ['label' => 'Đang hoạt động', 'class' => 'bg-green-100 text-green-700'],
        'inactive' => ['label' => 'Ngưng hoạt động', 'class' => 'bg-slate-100 text-slate-600'],
        'transferred' => ['label' => 'Đã chuyển', 'class' => 'bg-amber-100 text-amber-700'],
        'registered' => ['label' => 'Đã đăng ký', 'class' => 'bg-sky-100 text-sky-700'],
        'attended' => ['label' => 'Đã tham gia', 'class' => 'bg-green-100 text-green-700'],
        'absent' => ['label' => 'Vắng mặt', 'class' => 'bg-red-100 text-red-700'],
    ];
    $item = $map[$status] ?? ['label' => $status, 'class' => 'bg-slate-100 text-slate-600'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {$item['class']}"]) }}>
    {{ $item['label'] }}
</span>
