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
}
