<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Throwable;

final class ClaudeProvider implements AiProvider
{
    /**
     * @param  object|null  $client  đối tượng có `messages->create(...)`; mặc định là Anthropic\Client (cho phép thay khi kiểm thử)
     */
    public function __construct(private readonly ?string $key, private readonly string $model, private readonly ?object $client = null)
    {
    }

    public function name(): string
    {
        return 'claude';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function evaluate(EvaluationContext $context): AiResult
    {
        if (! $this->client && ! $this->key) {
            throw new AiException('Chưa cấu hình ANTHROPIC_API_KEY nên chưa thể dùng Claude.');
        }

        $content = [];
        foreach ($context->attachments as $attachment) {
            $file = PromptBuilder::encodeAttachment($attachment);
            if (! $file) {
                continue;
            }

            $type = $file['mime'] === 'application/pdf' ? 'document' : 'image';
            $content[] = ['type' => $type, 'source' => ['type' => 'base64', 'mediaType' => $file['mime'], 'data' => $file['data']]];
        }
        $content[] = ['type' => 'text', 'text' => PromptBuilder::user($context)];

        try {
            $client = $this->client ?? new Client(apiKey: $this->key);
            $message = $client->messages->create(
                model: $this->model,
                maxTokens: 4000,
                system: PromptBuilder::system(),
                messages: [['role' => 'user', 'content' => $content]],
                outputConfig: ['effort' => 'low'],
            );
        } catch (Throwable $e) {
            throw new AiException($this->friendlyError($e));
        }

        if (($message->stopReason ?? null) === 'refusal') {
            throw new AiException('Claude từ chối xử lý yêu cầu này. Vui lòng thử lại hoặc chọn nhà cung cấp khác.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return AiResult::fromArray(
            PromptBuilder::decodeJson($text),
            $context->maxScore,
            array_column($context->activities, 'id'),
            $this->name(),
            $this->model,
            $message->usage->inputTokens ?? null,
            $message->usage->outputTokens ?? null,
        );
    }

    private function friendlyError(Throwable $e): string
    {
        $status = method_exists($e, 'getCode') ? (int) $e->getCode() : 0;
        $message = strtolower($e->getMessage());

        return match (true) {
            $status === 401 || str_contains($message, 'authentication') => 'Khóa Claude API không hợp lệ hoặc đã bị thu hồi.',
            $status === 429 || str_contains($message, 'rate') => 'Claude báo gọi quá nhanh hoặc hết hạn mức. Vui lòng đợi rồi thử lại.',
            str_contains($message, 'credit') || str_contains($message, 'billing') => 'Tài khoản Claude API không còn số dư. Vui lòng nạp thêm tín dụng.',
            default => 'Không gọi được Claude: '.mb_substr($e->getMessage(), 0, 200),
        };
    }
}
