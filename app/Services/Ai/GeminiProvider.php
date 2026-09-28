<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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

    public function isSimulated(): bool
    {
        return false;
    }

    public function readAttendance(AttendanceRequest $request): AttendanceRead
    {
        $response = $this->call([
            'systemInstruction' => ['parts' => [['text' => Prompts::attendanceSystem()]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inlineData' => ['mimeType' => $request->mime, 'data' => base64_encode($request->bytes)]],
                    ['text' => Prompts::attendanceUser()],
                ],
            ]],
            'generationConfig' => ['temperature' => 0.0, 'responseMimeType' => 'application/json'],
        ]);

        return AttendanceRead::fromArray(
            Prompts::decodeJson($this->textOf($response)),
            $this->name(),
            $this->model,
            $response->json('usageMetadata.promptTokenCount'),
            $response->json('usageMetadata.candidatesTokenCount'),
        );
    }

    public function draftDocument(DocumentRequest $request): TextResult
    {
        $response = $this->call([
            'systemInstruction' => ['parts' => [['text' => $request->system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $request->prompt]]]],
            'generationConfig' => ['temperature' => 0.4],
        ]);

        return new TextResult(
            trim($this->textOf($response)),
            $this->name(),
            $this->model,
            $response->json('usageMetadata.promptTokenCount'),
            $response->json('usageMetadata.candidatesTokenCount'),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(array $payload): Response
    {
        if (! $this->key) {
            throw new AiException('Chưa cấu hình GEMINI_API_KEY nên chưa thể dùng Gemini. Hãy đặt khóa trong file .env hoặc chuyển AI_PROVIDER=mock.');
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->key])
                ->timeout(config('ai.timeout'))
                ->acceptJson()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", $payload);
        } catch (ConnectionException) {
            throw new AiException('Không kết nối được tới Gemini. Vui lòng kiểm tra mạng và thử lại sau.');
        }

        if ($response->status() === 429) {
            throw new AiException('Gemini báo đã hết hạn mức miễn phí hoặc gọi quá nhanh. Vui lòng đợi ít phút rồi thử lại.');
        }

        if ($response->failed()) {
            throw new AiException('Gemini trả lỗi ('.$response->status().'): '.mb_substr((string) $response->json('error.message', 'không rõ nguyên nhân'), 0, 200));
        }

        return $response;
    }

    private function textOf(Response $response): string
    {
        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

        if ($text === '') {
            throw new AiException('Gemini không trả về nội dung (có thể bị chặn bởi bộ lọc an toàn). Vui lòng thử lại.');
        }

        return $text;
    }
}
