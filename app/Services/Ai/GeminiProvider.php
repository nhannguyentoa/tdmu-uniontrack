<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GeminiProvider implements AiProvider
{
    public function __construct(private readonly ?string $key, private readonly string $model)
    {
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function evaluate(EvaluationContext $context): AiResult
    {
        if (! $this->key) {
            throw new AiException('Chưa cấu hình GEMINI_API_KEY nên chưa thể dùng Gemini.');
        }

        $parts = [];
        foreach ($context->attachments as $attachment) {
            $file = PromptBuilder::encodeAttachment($attachment);
            if ($file) {
                $parts[] = ['inlineData' => ['mimeType' => $file['mime'], 'data' => $file['data']]];
            }
        }
        $parts[] = ['text' => PromptBuilder::user($context)];

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout(config('ai.timeout'))
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                    'systemInstruction' => ['parts' => [['text' => PromptBuilder::system()]]],
                    'contents' => [['role' => 'user', 'parts' => $parts]],
                    'generationConfig' => ['temperature' => 0.2, 'responseMimeType' => 'application/json'],
                ]);
        } catch (ConnectionException $e) {
            throw new AiException('Không kết nối được tới Gemini. Vui lòng thử lại sau.');
        }

        if ($response->status() === 429) {
            throw new AiException('Gemini báo đã hết hạn mức miễn phí hoặc gọi quá nhanh. Vui lòng đợi rồi thử lại.');
        }

        if ($response->failed()) {
            throw new AiException('Gemini trả lỗi ('.$response->status().'): '.mb_substr((string) $response->json('error.message', 'không rõ nguyên nhân'), 0, 200));
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

        if ($text === '') {
            throw new AiException('Gemini không trả về nội dung (có thể bị chặn bởi bộ lọc an toàn).');
        }

        return AiResult::fromArray(
            PromptBuilder::decodeJson($text),
            $context->maxScore,
            array_column($context->activities, 'id'),
            $this->name(),
            $this->model,
            $response->json('usageMetadata.promptTokenCount'),
            $response->json('usageMetadata.candidatesTokenCount'),
        );
    }
}
