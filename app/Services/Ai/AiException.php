<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Lỗi có thông điệp thân thiện với người dùng (chưa cấu hình khóa, hết hạn mức, AI trả sai định dạng...).
 */
class AiException extends RuntimeException
{
}
