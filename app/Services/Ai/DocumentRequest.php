<?php

namespace App\Services\Ai;

final class DocumentRequest
{
    /**
     * @param  array<string, mixed>  $facts  số liệu đã tổng hợp (không chứa tên đoàn viên)
     */
    public function __construct(
        public readonly string $type,
        public readonly array $facts,
        public readonly string $system,
        public readonly string $prompt,
    ) {
    }
}
