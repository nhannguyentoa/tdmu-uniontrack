<?php

namespace App\Services\Ai;

final class TextResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
    ) {
    }
}
