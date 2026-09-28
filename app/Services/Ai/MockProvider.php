<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/**
 * Nhà cung cấp giả lập, xác định (không gọi mạng, không tốn phí): dùng để phát triển, kiểm thử và demo khi chưa
 * có khóa API. KHÔNG đọc ảnh thật: với ảnh/PDF nó dựng kết quả minh họa từ tên đoàn viên mẫu (có cố ý gây vài lỗi
 * chính tả) để cho thấy bước đối khớp; văn bản soạn theo mẫu quy tắc (DocumentTemplates).
 */
final class MockProvider implements AiProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function model(): string
    {
        return 'demo-v1';
    }

    public function isSimulated(): bool
    {
        return true;
    }

    public function readAttendance(AttendanceRequest $request): AttendanceRead
    {
        $rows = [];

        foreach (array_slice($request->sampleNames, 0, 8) as $i => $name) {
            $row = ['name' => $name, 'code' => null, 'confidence' => 0.96, 'note' => null];

            if ($i === 2) {
                $row['name'] = Str::ascii($name);
                $row['confidence'] = 0.82;
                $row['note'] = 'Mất dấu tiếng Việt (minh họa)';
            } elseif ($i === 4) {
                $row['name'] = $this->misread($name);
                $row['confidence'] = 0.7;
                $row['note'] = 'Chữ viết khó đọc (minh họa)';
            }

            $rows[] = $row;
        }

        $rows[] = ['name' => 'Nguyễn Văn Khách Mời', 'code' => null, 'confidence' => 0.9, 'note' => 'Người ngoài danh sách đoàn viên (minh họa)'];

        return new AttendanceRead(
            $rows,
            $this->name(),
            $this->model(),
            notice: 'Chế độ giả lập: ảnh/PDF không được đọc thật. Kết quả bên dưới chỉ là ví dụ minh họa để trình bày quy trình.',
        );
    }

    public function draftDocument(DocumentRequest $request): TextResult
    {
        return new TextResult(DocumentTemplates::render($request->type, $request->facts), $this->name(), $this->model());
    }

    public function chat(string $question, array $history, DataTools $tools): ChatResult
    {
        return (new MockAssistant())->answer($question, $tools);
    }

    /** Thay một chữ cái ở giữa tên (sau khi bỏ dấu) để mô phỏng lỗi đọc chữ của OCR. */
    private function misread(string $name): string
    {
        $ascii = Str::ascii($name);
        $pos = intdiv(strlen($ascii), 2);

        if (ctype_alpha($ascii[$pos] ?? '')) {
            $ascii[$pos] = strtolower($ascii[$pos]) === 'o' ? 'a' : 'o';
        }

        return $ascii;
    }
}
