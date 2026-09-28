<?php

namespace App\Services\Ai;

final class DocumentType
{
    public const ACTIVITY_SUMMARY = 'activity_summary';
    public const MONTHLY_GROUP = 'monthly_group';
    public const YEARLY_SCHOOL = 'yearly_school';
    public const INVITATION = 'invitation';

    public const LABELS = [
        self::ACTIVITY_SUMMARY => 'Báo cáo tổng kết hoạt động',
        self::MONTHLY_GROUP => 'Báo cáo tháng của tổ công đoàn',
        self::YEARLY_SCHOOL => 'Báo cáo năm học toàn trường',
        self::INVITATION => 'Thông báo mời tham gia hoạt động',
    ];

    /** Loại văn bản cần chọn một hoạt động cụ thể. */
    public static function needsActivity(string $type): bool
    {
        return in_array($type, [self::ACTIVITY_SUMMARY, self::INVITATION], true);
    }

    /** Loại văn bản chỉ Quản trị viên được soạn. */
    public static function adminOnly(string $type): bool
    {
        return $type === self::YEARLY_SCHOOL;
    }

    /** Mô tả yêu cầu riêng của từng loại văn bản, ghép vào câu lệnh gửi AI. */
    public static function instruction(string $type): string
    {
        return match ($type) {
            self::ACTIVITY_SUMMARY => "Soạn BÁO CÁO TỔNG KẾT một hoạt động công đoàn gồm các phần: I. Mục đích, ý nghĩa; II. Thời gian, địa điểm, thành phần tham gia; III. Nội dung và kết quả thực hiện (nêu số liệu dự kiến so với thực tế, số người tham gia); IV. Đánh giá chung (ưu điểm, tồn tại nếu số liệu cho thấy); V. Đề xuất, kiến nghị.",
            self::MONTHLY_GROUP => "Soạn BÁO CÁO HOẠT ĐỘNG THÁNG của một tổ công đoàn gồm các phần: I. Kết quả hoạt động trong tháng (liệt kê từng hoạt động kèm trạng thái, tiến độ, số người tham gia); II. Đánh giá chung (tỷ lệ hoàn thành, hoạt động chậm tiến độ hoặc quá hạn); III. Phương hướng hoạt động tháng tới (dựa vào kế hoạch còn lại nếu có).",
            self::YEARLY_SCHOOL => "Soạn BÁO CÁO TỔNG KẾT NĂM HỌC của Công đoàn Trường gồm các phần: I. Tình hình chung (số tổ, đoàn viên, số hoạt động, tỷ lệ hoàn thành); II. Kết quả hoạt động của các tổ (nêu tổ nổi bật và tổ cần cố gắng dựa trên số liệu, kèm xếp loại thi đua nếu có); III. Đánh giá chung; IV. Phương hướng năm học tới.",
            self::INVITATION => "Soạn THÔNG BÁO mời đoàn viên tham gia một hoạt động, gồm: tiêu đề thông báo, mục đích, thời gian, địa điểm, đối tượng và số lượng dự kiến, nội dung chính, yêu cầu (đăng ký, trang phục, mang theo nếu số liệu có nhắc), và lời kêu gọi tham gia.",
            default => 'Soạn văn bản hành chính phù hợp với số liệu được cung cấp.',
        };
    }
}
