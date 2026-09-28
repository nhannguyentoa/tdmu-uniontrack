<?php

namespace App\Services\Ai;

/**
 * Mẫu văn bản dựng bằng quy tắc (không dùng AI): là nội dung của nhà cung cấp giả lập và cũng là
 * mốc so sánh (baseline) với văn bản do mô hình ngôn ngữ soạn. Cùng ký hiệu đầu ra với Prompts::documentSystem().
 */
final class DocumentTemplates
{
    private const MISSING = '[cần bổ sung]';

    /**
     * @param  array<string, mixed>  $f
     */
    public static function render(string $type, array $f): string
    {
        return match ($type) {
            DocumentType::ACTIVITY_SUMMARY => self::activitySummary($f),
            DocumentType::MONTHLY_GROUP => self::monthly($f),
            DocumentType::YEARLY_SCHOOL => self::yearly($f),
            DocumentType::INVITATION => self::invitation($f),
            default => throw new AiException('Loại văn bản không hợp lệ.'),
        };
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private static function activitySummary(array $f): string
    {
        $p = $f['nguoi_tham_gia'];
        $lines = [
            '# BÁO CÁO TỔNG KẾT HOẠT ĐỘNG',
            '',
            'Hoạt động: '.mb_strtoupper((string) $f['ten_hoat_dong']).' (mã '.$f['ma_hoat_dong'].')',
            '',
            '## I. Mục đích, ý nghĩa',
            self::v($f['muc_tieu']),
            '',
            '## II. Thời gian, địa điểm, thành phần tham gia',
            '- Đơn vị chủ trì: '.self::v($f['to_chu_tri']).'.',
            '- Thời gian: từ '.self::v($f['bat_dau']).' đến '.self::v($f['ket_thuc']).'.',
            '- Địa điểm: '.self::v($f['dia_diem']).'.',
        ];

        if (! empty($f['to_phoi_hop'])) {
            $lines[] = '- Đơn vị phối hợp/tham gia: '.collect($f['to_phoi_hop'])->map(fn ($g) => $g['ten'].' ('.mb_strtolower((string) $g['vai_tro']).')')->implode('; ').'.';
        }

        $lines = array_merge($lines, [
            '',
            '## III. Nội dung và kết quả thực hiện',
            self::v($f['noi_dung']),
            '',
            '- Trạng thái hiện tại: '.$f['trang_thai'].', tiến độ '.$f['tien_do_phan_tram'].'%.',
            '- Số lượng dự kiến: '.self::v($f['so_luong_du_kien']).'; số lượng thực tế: '.self::v($f['so_luong_thuc_te']).'.',
            '- Người tham gia ghi nhận trên hệ thống: '.$p['tong'].' người (đã tham gia '.$p['da_tham_gia'].', đã đăng ký '.$p['da_dang_ky'].', vắng mặt '.$p['vang_mat'].').',
            '- Số minh chứng đính kèm: '.$f['so_minh_chung'].'.',
        ]);

        if ($f['kinh_phi_vnd'] !== null) {
            $lines[] = '- Kinh phí thực hiện: '.number_format($f['kinh_phi_vnd'], 0, ',', '.').' đồng.';
        }

        if ($f['tinh_diem_thi_dua']) {
            $lines[] = '- Hoạt động được tính điểm thi đua (tối đa '.self::v($f['diem_toi_da']).' điểm), '.($f['da_duoc_duyet_diem'] ? 'đã được Quản trị viên duyệt' : 'chưa được duyệt điểm').'.';
        }

        $expected = (int) ($f['so_luong_du_kien'] ?? 0);
        $actual = (int) ($f['so_luong_thuc_te'] ?? 0);
        $assessment = $expected > 0 && $actual > 0
            ? 'Số lượng thực tế đạt '.round($actual / $expected * 100).'% so với dự kiến.'
            : 'Chưa có đủ số liệu thực tế để so sánh với dự kiến '.self::MISSING.'.';

        return implode("\n", array_merge($lines, [
            '',
            '## IV. Đánh giá chung',
            $assessment,
            '',
            '## V. Đề xuất, kiến nghị',
            self::MISSING,
            '',
            'Người lập báo cáo: [Họ tên người ký]',
        ]));
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private static function monthly(array $f): string
    {
        $t = $f['tong_hop'];
        $lines = [
            '# BÁO CÁO HOẠT ĐỘNG '.mb_strtoupper((string) $f['ky_bao_cao']),
            '',
            'Tổ công đoàn: '.$f['to_cong_doan'].' ('.$f['so_doan_vien_dang_hoat_dong'].' đoàn viên đang hoạt động).',
            '',
            '## I. Kết quả hoạt động trong tháng',
        ];

        if (empty($f['hoat_dong'])) {
            $lines[] = 'Trong tháng chưa ghi nhận hoạt động nào trên hệ thống.';
        }

        foreach ($f['hoat_dong'] as $a) {
            $lines[] = '- '.$a['ten'].' ('.self::v($a['thoi_gian']).'): '.mb_strtolower((string) $a['trang_thai']).', tiến độ '.$a['tien_do_phan_tram'].'%, '.$a['so_nguoi_tham_gia'].' người tham gia.';
        }

        $lines = array_merge($lines, [
            '',
            '## II. Đánh giá chung',
            'Tổng số hoạt động: '.$t['tong_so_hoat_dong'].'; đã hoàn thành: '.$t['da_hoan_thanh'].' (đạt '.$t['ty_le_hoan_thanh_phan_tram'].'%); tổng lượt người tham gia: '.$t['tong_luot_nguoi_tham_gia'].'.',
            $t['so_hoat_dong_qua_han'] > 0
                ? 'Có '.$t['so_hoat_dong_qua_han'].' hoạt động quá hạn cần đẩy nhanh tiến độ.'
                : 'Không có hoạt động quá hạn.',
        ]);

        if (! empty($f['ke_hoach_trong_thang_chua_thuc_hien'])) {
            $lines[] = 'Kế hoạch trong tháng chưa thực hiện: '.implode('; ', $f['ke_hoach_trong_thang_chua_thuc_hien']).'.';
        }

        $lines[] = '';
        $lines[] = '## III. Phương hướng hoạt động tháng tới';

        if (empty($f['ke_hoach_thang_sau'])) {
            $lines[] = 'Chưa có kế hoạch được lập cho tháng tới '.self::MISSING.'.';
        }

        foreach ($f['ke_hoach_thang_sau'] as $title) {
            $lines[] = '- '.$title;
        }

        $lines[] = '';
        $lines[] = 'Người lập báo cáo: [Họ tên người ký]';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private static function yearly(array $f): string
    {
        $t = $f['tong_hop'];
        $lines = [
            '# BÁO CÁO TỔNG KẾT NĂM HỌC '.$f['nam_hoc'],
            '',
            '## I. Tình hình chung',
            '- Số tổ công đoàn: '.$t['so_to_cong_doan'].'; số đoàn viên đang hoạt động: '.$t['so_doan_vien'].'.',
            '- Tổng số hoạt động: '.$t['tong_so_hoat_dong'].', đã hoàn thành '.$t['da_hoan_thanh'].' (đạt '.$t['ty_le_hoan_thanh_phan_tram'].'%).',
        ];

        foreach ($t['hoat_dong_theo_loai'] as $name => $count) {
            $lines[] = '- Loại "'.$name.'": '.$count.' hoạt động.';
        }

        $lines[] = '';
        $lines[] = '## II. Kết quả hoạt động của các tổ';

        $groups = collect($f['cac_to']);
        foreach ($groups as $g) {
            $score = $g['diem_tham_dinh'] !== null ? 'điểm thẩm định '.$g['diem_tham_dinh'] : 'chưa có điểm thẩm định';
            $lines[] = '- '.$g['to'].': '.$g['so_hoat_dong'].' hoạt động ('.$g['da_hoan_thanh'].' hoàn thành), '.$score.', xếp loại: '.mb_strtolower((string) $g['xep_loai']).'.';
        }

        $best = $groups->sortByDesc('da_hoan_thanh')->first();
        $lines[] = '';
        $lines[] = '## III. Đánh giá chung';
        $lines[] = $best && $best['da_hoan_thanh'] > 0
            ? 'Tổ có số hoạt động hoàn thành nhiều nhất: '.$best['to'].' ('.$best['da_hoan_thanh'].' hoạt động).'
            : 'Chưa có hoạt động hoàn thành nào được ghi nhận '.self::MISSING.'.';
        $lines[] = '';
        $lines[] = '## IV. Phương hướng năm học tới';
        $lines[] = self::MISSING;
        $lines[] = '';
        $lines[] = 'Người lập báo cáo: [Họ tên người ký]';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $f
     */
    private static function invitation(array $f): string
    {
        $lines = [
            '# THÔNG BÁO',
            'V/v tham gia hoạt động: '.$f['ten_hoat_dong'],
            '',
            'Công đoàn '.self::v($f['to_chu_tri']).' trân trọng thông báo và kính mời các đoàn viên tham gia hoạt động "'.$f['ten_hoat_dong'].'" với các thông tin như sau:',
            '',
            '- Mục đích: '.self::v($f['muc_tieu']),
            '- Thời gian: từ '.self::v($f['bat_dau']).' đến '.self::v($f['ket_thuc']).'.',
            '- Địa điểm: '.self::v($f['dia_diem']).'.',
            '- Số lượng dự kiến: '.self::v($f['so_luong_du_kien']).' người.',
            '- Nội dung chính: '.self::v($f['noi_dung']),
            '- Đăng ký tham gia: '.self::MISSING.'.',
            '',
            'Rất mong các đoàn viên sắp xếp thời gian tham gia đầy đủ để hoạt động đạt kết quả tốt.',
            '',
            'Người ký: [Họ tên người ký]',
        ];

        return implode("\n", $lines);
    }

    private static function v(mixed $value): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : self::MISSING;
    }
}
