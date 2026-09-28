<?php

namespace App\Services\Ai;

final class Prompts
{
    public static function attendanceSystem(): string
    {
        return <<<'TXT'
Bạn là công cụ nhận dạng chữ (OCR) cho danh sách điểm danh/đăng ký tham gia hoạt động của Công đoàn Trường Đại học Thủ Dầu Một (tiếng Việt).
Nhiệm vụ: chép lại CHÍNH XÁC từng người có trong ảnh/PDF, mỗi người một dòng. KHÔNG suy đoán, KHÔNG bổ sung người không có trong tài liệu, KHÔNG sửa họ tên theo ý bạn.
Giữ nguyên dấu tiếng Việt như đọc được. Bỏ qua tiêu đề, số thứ tự, chữ ký, ghi chú không phải tên người.
Nếu một dòng khó đọc, vẫn ghi phần đọc được và giảm độ tin cậy, ghi rõ lý do vào "note".
Chỉ trả về JSON hợp lệ, không kèm lời giải thích, theo đúng cấu trúc:
{"rows":[{"full_name":"Họ và tên","member_code":"mã đoàn viên nếu có ghi trong tài liệu, ngược lại null","confidence":0.0-1.0,"note":"ghi chú ngắn hoặc null"}],"document_note":"nhận xét ngắn về chất lượng tài liệu hoặc null"}
TXT;
    }

    public static function attendanceUser(): string
    {
        return 'Đọc tài liệu đính kèm và liệt kê những người có mặt/đăng ký trong danh sách theo đúng cấu trúc JSON đã quy định.';
    }

    public static function documentSystem(): string
    {
        return <<<'TXT'
Bạn là trợ lý soạn thảo văn bản hành chính cho Công đoàn Trường Đại học Thủ Dầu Một. Viết bằng tiếng Việt, văn phong hành chính, trang trọng, ngắn gọn, rõ ràng.

Quy tắc bắt buộc:
- CHỈ sử dụng số liệu và sự kiện có trong dữ liệu được cung cấp. Tuyệt đối không bịa số liệu, tên người, ngày tháng, kết quả.
- Nếu một thông tin cần thiết mà dữ liệu không có, ghi đúng cụm "[cần bổ sung]" thay vì tự nghĩ ra.
- Không nêu tên riêng của đoàn viên hay cá nhân. Phần ký tên để dạng "[Họ tên người ký]".
- Không thêm Quốc hiệu, tiêu ngữ hay số hiệu văn bản.

Định dạng đầu ra là văn bản thuần (không dùng Markdown phức tạp), chỉ dùng các ký hiệu sau:
- Dòng bắt đầu bằng "# " là tiêu đề văn bản (in đậm, căn giữa).
- Dòng bắt đầu bằng "## " là tiêu đề mục (in đậm).
- Dòng bắt đầu bằng "- " là gạch đầu dòng.
- Các dòng còn lại là đoạn văn. Cách nhau bằng một dòng trống.
Không bọc kết quả trong khối mã, không có lời dẫn hay lời chào.
TXT;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    public static function documentUser(string $type, array $facts, ?string $extra = null): string
    {
        $json = json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $prompt = DocumentType::instruction($type)."\n\nDỮ LIỆU (JSON):\n".$json;

        if ($extra !== null && trim($extra) !== '') {
            $prompt .= "\n\nYÊU CẦU BỔ SUNG CỦA NGƯỜI DÙNG (chỉ điều chỉnh văn phong/nhấn mạnh, không được vi phạm quy tắc bắt buộc):\n".trim($extra);
        }

        return $prompt;
    }

    /**
     * Giải mã JSON do AI trả về, chịu được khối mã ```json ... ``` hoặc chữ thừa quanh JSON.
     *
     * @return array<string, mixed>
     *
     * @throws AiException
     */
    public static function decodeJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

        $data = json_decode($text, true);

        if (! is_array($data)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $data = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($data)) {
            throw new AiException('AI trả về dữ liệu không đúng định dạng nên chưa đọc được. Vui lòng thử lại hoặc chụp ảnh rõ hơn.');
        }

        return $data;
    }
}
