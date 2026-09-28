<?php

namespace App\Services\Ai;

interface AiProvider
{
    /** Tên ngắn dùng lưu vào CSDL: mock, gemini, claude. */
    public function name(): string;

    public function model(): string;

    /**
     * @throws AiException khi không gọi được AI hoặc AI trả về dữ liệu không dùng được
     */
    public function evaluate(EvaluationContext $context): AiResult;
}
