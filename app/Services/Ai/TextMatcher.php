<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/**
 * So khớp từ khóa đơn giản (không dấu, bỏ từ phổ biến) giữa nội dung tiêu chí và tên/mục tiêu hoạt động.
 * Dùng để chọn trước hoạt động ứng viên đính kèm minh chứng cho AI và làm cơ sở cho nhà cung cấp giả lập.
 */
final class TextMatcher
{
    private const STOP_WORDS = [
        'van', 'dong', 'cdv', 'tham', 'gia', 'hoat', 'cong', 'doan', 'chuc', 'cac', 'cua', 'cho', 'hoc', 'tai', 'voi',
        'nhu', 'theo', 'trong', 'ngay', 'vien', 'nguoi', 'lao', 'tuan', 've', 'truong', 'dai', 'thu', 'dau', 'mot',
        'nam', 'khoi', 'thong', 'qua', 'tren', 'duoi', 'mot', 'nhung', 'duoc', 'khong', 'co',
    ];

    /**
     * @return array<int, string>
     */
    public static function tokens(string $text): array
    {
        $ascii = Str::lower(Str::ascii($text));

        return collect(preg_split('/[^a-z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $word) => strlen($word) >= 3 && ! ctype_digit($word) && ! in_array($word, self::STOP_WORDS, true))
            ->unique()->values()->all();
    }

    public static function overlap(string $a, string $b): int
    {
        return count(array_intersect(self::tokens($a), self::tokens($b)));
    }
}
