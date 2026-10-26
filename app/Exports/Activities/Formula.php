<?php

namespace App\Exports\Activities;

/**
 * Đánh dấu một ô là công thức Excel. Mọi chuỗi khác được ghi dạng văn bản thuần,
 * để tên hoạt động do người dùng nhập (vd bắt đầu bằng "=") không bị Excel chạy như công thức.
 */
final class Formula
{
    public function __construct(public readonly string $expression) {}
}
