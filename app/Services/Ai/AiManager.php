<?php

namespace App\Services\Ai;

final class AiManager
{
    public function provider(?string $name = null): AiProvider
    {
        $name ??= $this->activeName();

        return match ($name) {
            'mock' => new MockProvider(),
            'gemini' => new GeminiProvider(config('ai.gemini.key'), config('ai.gemini.model')),
            default => throw new AiException("Nhà cung cấp AI không hợp lệ: {$name}. Chỉ hỗ trợ mock hoặc gemini."),
        };
    }

    public function activeName(): string
    {
        return (string) config('ai.provider');
    }
}
