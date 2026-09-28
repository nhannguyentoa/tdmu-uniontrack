<?php

namespace App\Services\Ai;

interface AiProvider
{
    /** Tên ngắn dùng lưu nhật ký: mock, gemini. */
    public function name(): string;

    public function model(): string;

    /** true nếu là nhà cung cấp giả lập (kết quả chỉ mang tính minh họa). */
    public function isSimulated(): bool;

    /**
     * Đọc ảnh/PDF danh sách tham gia và trả về các dòng tên đọc được.
     *
     * @throws AiException khi không gọi được AI hoặc AI trả về dữ liệu không dùng được
     */
    public function readAttendance(AttendanceRequest $request): AttendanceRead;

    /**
     * Soạn bản nháp văn bản hành chính từ số liệu đã tổng hợp.
     *
     * @throws AiException
     */
    public function draftDocument(DocumentRequest $request): TextResult;

    /**
     * Trả lời câu hỏi tiếng Việt về dữ liệu hệ thống bằng cách gọi các công cụ tra cứu chỉ-đọc trong $tools.
     *
     * @param  list<array{role: string, text: string}>  $history  lượt hỏi đáp trước đó (role: user | assistant)
     *
     * @throws AiException
     */
    public function chat(string $question, array $history, DataTools $tools): ChatResult;
}
