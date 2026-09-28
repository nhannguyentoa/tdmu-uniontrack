<?php

namespace App\Services\Ai;

final class ChatResult
{
    /**
     * @param  list<array{name: string, args: array<string, mixed>}>  $toolCalls  các công cụ đã gọi (để hiển thị minh bạch)
     */
    public function __construct(
        public readonly string $answer,
        public readonly string $provider,
        public readonly string $model,
        public readonly array $toolCalls = [],
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
    ) {
    }
}
