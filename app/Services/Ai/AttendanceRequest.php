<?php

namespace App\Services\Ai;

final class AttendanceRequest
{
    /**
     * @param  list<string>  $sampleNames  tên đoàn viên mẫu, CHỈ nhà cung cấp giả lập dùng để dựng kết quả minh họa
     *                                     (không bao giờ gửi cho dịch vụ AI thật)
     */
    public function __construct(
        public readonly string $bytes,
        public readonly string $mime,
        public readonly string $filename,
        public readonly array $sampleNames = [],
    ) {
    }
}
