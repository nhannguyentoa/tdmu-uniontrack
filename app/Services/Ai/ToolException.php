<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Lỗi nghiệp vụ của công cụ tra cứu (không có quyền, không tìm thấy tổ...): được trả lại cho AI dưới dạng
 * kết quả có trường "loi" để AI giải thích cho người dùng.
 */
class ToolException extends RuntimeException
{
}
