<?php

namespace App\Services\Ai;

final class AttendanceRead
{
    /**
     * @param  list<array{name: string, code: ?string, confidence: ?float, note: ?string}>  $rows
     */
    public function __construct(
        public readonly array $rows,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
        public readonly ?string $notice = null,
    ) {
    }

    /**
     * Chuẩn hóa mảng do AI trả về (thiếu trường, sai kiểu, dòng trống...) thành danh sách dòng sạch.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $provider, string $model, ?int $in = null, ?int $out = null): self
    {
        $rows = [];

        foreach ((array) ($data['rows'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['full_name'] ?? $row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $code = trim((string) ($row['member_code'] ?? $row['code'] ?? ''));
            $confidence = isset($row['confidence']) && is_numeric($row['confidence'])
                ? max(0.0, min(1.0, (float) $row['confidence']))
                : null;
            $note = trim((string) ($row['note'] ?? ''));

            $rows[] = [
                'name' => mb_substr($name, 0, 120),
                'code' => $code !== '' ? mb_substr($code, 0, 40) : null,
                'confidence' => $confidence,
                'note' => $note !== '' ? mb_substr($note, 0, 200) : null,
            ];
        }

        $notice = trim((string) ($data['document_note'] ?? ''));

        return new self($rows, $provider, $model, $in, $out, $notice !== '' ? mb_substr($notice, 0, 300) : null);
    }
}
