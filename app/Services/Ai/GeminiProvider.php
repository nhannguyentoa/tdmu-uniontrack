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

    public function chat(string $question, array $history, DataTools $tools): ChatResult
    {
        $contents = [];
        foreach ($history as $turn) {
            $contents[] = ['role' => $turn['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $turn['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $calls = [];
        $in = 0;
        $out = 0;

        // Vòng lặp function calling: mô hình yêu cầu công cụ -> hệ thống chạy -> trả kết quả -> mô hình trả lời.
        for ($step = 0; $step < 6; $step++) {
            $response = $this->call([
                'systemInstruction' => ['parts' => [['text' => Prompts::assistantSystem($tools)]]],
                'contents' => $contents,
                'tools' => [['functionDeclarations' => DataTools::declarations()]],
                'generationConfig' => ['temperature' => 0.2],
            ]);

            $in += (int) $response->json('usageMetadata.promptTokenCount', 0);
            $out += (int) $response->json('usageMetadata.candidatesTokenCount', 0);

            $parts = $response->json('candidates.0.content.parts', []);
            $functionCalls = array_values(array_filter($parts, fn ($part) => isset($part['functionCall'])));

            if ($functionCalls === []) {
                $answer = trim(collect($parts)->pluck('text')->filter()->implode(''));

                if ($answer === '') {
                    throw new AiException('Gemini không trả về nội dung (có thể bị bộ lọc chặn). Vui lòng hỏi lại theo cách khác.');
                }

                return new ChatResult($answer, $this->name(), $this->model, $calls, $in, $out);
            }

            // Gửi lại đúng lượt gọi hàm của mô hình (args rỗng phải là đối tượng JSON, không phải mảng).
            $contents[] = ['role' => 'model', 'parts' => array_map(function ($part) {
                if (isset($part['functionCall']) && empty($part['functionCall']['args'])) {
                    $part['functionCall']['args'] = new \stdClass();
                }

                return $part;
            }, $parts)];

            $results = [];
            foreach ($functionCalls as $part) {
                $name = (string) $part['functionCall']['name'];
                $args = (array) ($part['functionCall']['args'] ?? []);
                $calls[] = ['name' => $name, 'args' => $args];
                $results[] = ['functionResponse' => ['name' => $name, 'response' => ['result' => $tools->run($name, $args)]]];
            }
            $contents[] = ['role' => 'user', 'parts' => $results];
        }

        throw new AiException('Câu hỏi cần quá nhiều bước tra cứu. Vui lòng hỏi cụ thể hơn.');
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
                ->withOptions(['verify' => config('ai.ca_bundle') ?: true])
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
