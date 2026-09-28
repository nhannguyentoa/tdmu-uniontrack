<?php

namespace App\Services\Ai;

final class AiManager
{
    public const PROVIDERS = ['mock' => 'Giả lập (không tốn phí)', 'gemini' => 'Gemini', 'claude' => 'Claude'];

    public function provider(?string $name = null): AiProvider
    {
        $name ??= config('ai.provider');

        return match ($name) {
            'mock' => new MockProvider(),
            'gemini' => new GeminiProvider(config('ai.gemini.key'), config('ai.gemini.model')),
            'claude' => new ClaudeProvider(config('ai.claude.key'), config('ai.claude.model')),
            default => throw new AiException("Nhà cung cấp AI không hợp lệ: {$name}."),
        };
    }

    public function activeName(): string
    {
        return (string) config('ai.provider');
    }
}
