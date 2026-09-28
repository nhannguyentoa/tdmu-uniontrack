<?php

namespace App\Services\Ai;

use Illuminate\Support\Carbon;

/**
 * Trợ lý hỏi đáp giả lập dựng bằng quy tắc từ khóa (không dùng AI, không gọi mạng): dùng cho demo, kiểm thử
 * và làm mốc so sánh. Cũng gọi đúng các công cụ trong DataTools nên bị giới hạn phạm vi quyền như AI thật.
 */
final class MockAssistant
{
    public function answer(string $question, DataTools $tools): ChatResult
    {
        $q = MemberMatcher::normalize($question);
        $args = $this->periodArgs($q) + $this->groupArgs($q, $tools);
        $calls = [];

        $run = function (string $name, array $callArgs) use ($tools, &$calls): array {
            $calls[] = ['name' => $name, 'args' => $callArgs];

            return $tools->run($name, $callArgs);
        };

        $has = fn (string ...$words) => (bool) array_filter($words, fn (string $w) => str_contains($q, $w));

        $text = match (true) {
            $has('tham gia', 'luot', 'diem danh') => $this->participation($run('participation_stats', $args)),
            $has('diem', 'xep loai', 'thi dua', 'thuong') => $this->evaluation($run('evaluation_summary', array_intersect_key($args, ['academic_year' => 1, 'union_group' => 1]))),
            $has('ke hoach') => $this->plans($run('plan_status', $args)),
            $has('cham', 'tre', 'qua han', 'tien do') => $this->overdue($run('list_activities', $args + ['overdue_only' => true])),
            $has('so sanh', 'tong quan', 'ty le', 'hoan thanh', 'bao nhieu') => $this->overview($run('group_overview', $args)),
            $has('hoat dong') => $this->activities($run('list_activities', $args)),
            default => $this->help(),
        };

        return new ChatResult($text, 'mock', 'demo-v1', $calls);
    }

    /**
     * @return array<string, mixed>
     */
    private function periodArgs(string $q): array
    {
        if (preg_match('/nam hoc (\d{4}) ?[- ] ?(\d{4})/', $q, $m)) {
            return ['academic_year' => "{$m[1]}-{$m[2]}"];
        }

        if (preg_match('/thang (\d{1,2})(?: ?\/ ?(\d{4})| nam (\d{4}))?/', $q, $m)) {
            return array_filter(['month' => (int) $m[1], 'year' => isset($m[2]) && $m[2] !== '' ? (int) $m[2] : (isset($m[3]) ? (int) $m[3] : null)]);
        }

        if (str_contains($q, 'thang nay')) {
            return ['month' => now()->month, 'year' => now()->year];
        }

        if (str_contains($q, 'thang truoc') || str_contains($q, 'thang qua')) {
            $prev = Carbon::now()->subMonthNoOverflow();

            return ['month' => $prev->month, 'year' => $prev->year];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function groupArgs(string $q, DataTools $tools): array
    {
        foreach ($tools->allowedGroups() as $group) {
            $short = trim(preg_replace('/^to cong doan\s+/', '', MemberMatcher::normalize($group->name)) ?? '');

            if ($short !== '' && preg_match('/(^|\s)'.preg_quote($short, '/').'($|\s)/', $q)) {
                return ['union_group' => $group->name];
            }
        }

        return [];
    }

    /** @param array<string, mixed> $r */
    private function overdue(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        if ($r['tong_so_khop'] === 0) {
            return "- Không có hoạt động nào chậm tiến độ trong {$r['ky']}.\n- Phạm vi: {$r['pham_vi']}.";
        }

        $lines = ["- {$r['ky']}: có {$r['tong_so_khop']} hoạt động chậm tiến độ (quá hạn, chưa hoàn thành)."];
        foreach (collect($r['hoat_dong'])->groupBy('to') as $group => $items) {
            $lines[] = "- {$group}: {$items->count()} hoạt động, ví dụ ".$items->take(3)->map(fn ($a) => "{$a['ten']} (hạn {$a['ket_thuc']}, tiến độ {$a['tien_do_phan_tram']}%)")->implode('; ').'.';
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $r */
    private function evaluation(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        $rows = collect($r['cac_to'])->sortByDesc('diem_thuong_tu_hoat_dong')->values();
        if ($rows->isEmpty()) {
            return "- Chưa có dữ liệu thi đua cho năm học {$r['nam_hoc']}.";
        }

        $lines = ["- Điểm thi đua năm học {$r['nam_hoc']} (sắp xếp theo điểm thưởng từ hoạt động):"];
        foreach ($rows as $row) {
            $lines[] = "- {$row['to']}: điểm thưởng {$row['diem_thuong_tu_hoat_dong']}; tự chấm ".($row['diem_tu_cham'] ?? 'chưa có').'; thẩm định '.($row['diem_tham_dinh'] ?? 'chưa có').'; xếp loại: '.mb_strtolower((string) $row['xep_loai']).'.';
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $r */
    private function overview(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        $lines = ["- So sánh các tổ, {$r['ky']} (sắp xếp theo tỷ lệ hoàn thành):"];
        foreach (collect($r['cac_to'])->sortByDesc(fn ($g) => $g['ty_le_hoan_thanh_phan_tram'] ?? -1) as $g) {
            $rate = $g['ty_le_hoan_thanh_phan_tram'] !== null ? $g['ty_le_hoan_thanh_phan_tram'].'%' : 'chưa có hoạt động';
            $lines[] = "- {$g['to']}: {$g['so_hoat_dong']} hoạt động, hoàn thành {$g['da_hoan_thanh']} ({$rate}), quá hạn {$g['qua_han']}, {$g['so_doan_vien']} đoàn viên.";
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $r */
    private function participation(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        $lines = ["- Lượt tham gia hoạt động, {$r['ky']}:"];
        foreach (collect($r['cac_to'])->sortByDesc('tong_luot') as $g) {
            $lines[] = "- {$g['to']}: tổng {$g['tong_luot']} lượt (đã tham gia {$g['da_tham_gia']}, đã đăng ký {$g['da_dang_ky']}, vắng {$g['vang_mat']}).";
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $r */
    private function plans(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        $lines = ["- Kế hoạch năm học {$r['nam_hoc']}: tổng {$r['tong_ke_hoach']} kế hoạch."];
        foreach ($r['theo_trang_thai'] as $status => $count) {
            $lines[] = "- Trạng thái {$status}: {$count}.";
        }
        foreach ($r['ke_hoach_qua_han'] as $p) {
            $lines[] = "- Quá hạn: {$p['ten']} ({$p['to']}, tháng {$p['thang']}).";
        }

        return implode("\n", $lines);
    }

    /** @param array<string, mixed> $r */
    private function activities(array $r): string
    {
        if (isset($r['loi'])) {
            return $r['loi'];
        }

        if ($r['tong_so_khop'] === 0) {
            return "- Không có hoạt động nào trong {$r['ky']}.";
        }

        $lines = ["- {$r['ky']}: {$r['tong_so_khop']} hoạt động (hiển thị tối đa ".count($r['hoat_dong']).'):'];
        foreach ($r['hoat_dong'] as $a) {
            $lines[] = "- {$a['ten']} ({$a['to']}), {$a['bat_dau']}, {$a['trang_thai']}, tiến độ {$a['tien_do_phan_tram']}%.";
        }

        return implode("\n", $lines);
    }

    private function help(): string
    {
        return "- Chế độ giả lập chỉ hiểu một số câu hỏi mẫu, ví dụ:\n"
            ."- Tổ nào chậm tiến độ tháng này?\n"
            ."- So sánh điểm thưởng các tổ năm học 2026-2027\n"
            ."- So sánh tỷ lệ hoàn thành hoạt động các tổ\n"
            ."- Thống kê lượt tham gia hoạt động\n"
            ."- Kế hoạch nào đang quá hạn?\n"
            .'- Đặt AI_PROVIDER=gemini để hỏi tự do bằng ngôn ngữ tự nhiên.';
    }
}
