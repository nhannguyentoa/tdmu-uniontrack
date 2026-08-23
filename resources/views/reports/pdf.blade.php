<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; }
        .header .school { font-size: 13px; font-weight: bold; }
        .header .title { font-size: 15px; font-weight: bold; text-transform: uppercase; margin-top: 4px; }
        .header .meta { font-size: 11px; margin-top: 4px; color: #475569; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { padding: 6px 10px; border: 1px solid #cbd5e1; }
        .summary td.label { background: #f1f5f9; font-weight: bold; width: 45%; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.list th, table.list td { border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 10px; }
        table.list th { background: #1e40af; color: #fff; text-align: left; }
        table.list tr:nth-child(even) { background: #f8fafc; }
        h3 { font-size: 12px; margin-top: 16px; margin-bottom: 6px; }
        .footer { margin-top: 20px; font-size: 10px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <p class="school">TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT</p>
        <p class="title">Báo cáo hoạt động công đoàn</p>
        <p class="meta">{{ $report['label'] }} — Xuất ngày {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Tổng số hoạt động</td><td>{{ $report['total_activities'] }}</td>
            <td class="label">Đã hoàn thành</td><td>{{ $report['completed_activities'] }}</td>
        </tr>
        <tr>
            <td class="label">Tỷ lệ hoàn thành</td><td>{{ $report['completion_rate'] }}%</td>
            <td class="label">Tổng lượt tham gia</td><td>{{ $report['total_participants'] }}</td>
        </tr>
        @if(isset($report['total_members']))
            <tr>
                <td class="label">Tổng số đoàn viên</td><td>{{ $report['total_members'] }}</td>
                <td class="label"></td><td></td>
            </tr>
        @endif
    </table>

    <h3>Số hoạt động theo tổ công đoàn</h3>
    <table class="list">
        <tr><th>Tổ công đoàn</th><th>Số hoạt động</th></tr>
        @forelse($report['by_group'] as $name => $count)
            <tr><td>{{ $name }}</td><td>{{ $count }}</td></tr>
        @empty
            <tr><td colspan="2">Không có dữ liệu</td></tr>
        @endforelse
    </table>

    <h3>Số hoạt động theo loại</h3>
    <table class="list">
        <tr><th>Loại hoạt động</th><th>Số hoạt động</th></tr>
        @forelse($report['by_type'] as $name => $count)
            <tr><td>{{ $name }}</td><td>{{ $count }}</td></tr>
        @empty
            <tr><td colspan="2">Không có dữ liệu</td></tr>
        @endforelse
    </table>

    <h3>Danh sách hoạt động</h3>
    <table class="list">
        <tr>
            <th>Mã HĐ</th><th>Tên hoạt động</th><th>Tổ công đoàn</th><th>Thời gian</th><th>Trạng thái</th><th>Tiến độ</th>
        </tr>
        @forelse($report['activities'] as $activity)
            <tr>
                <td>{{ $activity->code }}</td>
                <td>{{ $activity->name }}</td>
                <td>{{ $activity->unionGroup?->name }}</td>
                <td>{{ $activity->start_time->format('d/m/Y') }}</td>
                <td>{{ \App\Models\Activity::STATUSES[$activity->status] ?? $activity->status }}</td>
                <td>{{ $activity->progress }}%</td>
            </tr>
        @empty
            <tr><td colspan="6">Không có hoạt động nào trong kỳ báo cáo</td></tr>
        @endforelse
    </table>

    <p class="footer">TDMU UnionTrack — Hệ thống quản lý hoạt động Công đoàn Trường Đại học Thủ Dầu Một</p>
</body>
</html>
