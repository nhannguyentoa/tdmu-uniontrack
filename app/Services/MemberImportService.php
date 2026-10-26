<?php

namespace App\Services;

use App\Http\Requests\Members\StoreMemberRequest;
use App\Imports\MemberSheetImport;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Nhập nhanh danh sách đoàn viên của một tổ từ tệp Excel/CSV: đọc và kiểm tra từng dòng để xem trước,
 * sau đó mới ghi các dòng hợp lệ vào hệ thống.
 */
class MemberImportService
{
    public const MAX_ROWS = 500;

    /** Tên cột (đã bỏ dấu, viết thường) được chấp nhận cho từng trường. */
    protected const HEADERS = [
        'code' => ['ma doan vien', 'ma dv', 'ma', 'ma so', 'ma so doan vien', 'ma cb', 'ma can bo', 'ma nhan vien'],
        'full_name' => ['ho va ten', 'ho ten', 'ho ten doan vien', 'ten doan vien', 'ten'],
        'dob' => ['ngay sinh', 'ngay thang nam sinh'],
        'gender' => ['gioi tinh', 'phai'],
        'email' => ['email', 'e mail', 'thu dien tu'],
        'phone' => ['so dien thoai', 'dien thoai', 'sdt', 'so dt'],
        'position' => ['chuc vu', 'chuc danh'],
        'department' => ['don vi', 'phong ban', 'khoa', 'don vi cong tac'],
        'joined_union_date' => ['ngay tham gia', 'ngay vao cong doan', 'ngay gia nhap', 'ngay ket nap'],
        'status' => ['trang thai'],
        'note' => ['ghi chu'],
    ];

    protected const GENDERS = ['nam' => 'male', 'male' => 'male', 'nu' => 'female', 'female' => 'female', 'khac' => 'other', 'other' => 'other'];

    protected const STATUSES = [
        'dang hoat dong' => 'active', 'hoat dong' => 'active', 'active' => 'active',
        'ngung hoat dong' => 'inactive', 'ngung sinh hoat' => 'inactive', 'inactive' => 'inactive',
        'da chuyen' => 'transferred', 'chuyen to' => 'transferred', 'transferred' => 'transferred',
    ];

    /**
     * Đọc tệp và phân loại từng dòng: valid (sẽ thêm), duplicate (trùng mã, bỏ qua), error (sai dữ liệu).
     *
     * @return array{rows: list<array<string, mixed>>, error: ?string}
     */
    public function analyze(UploadedFile $file): array
    {
        $sheet = Excel::toArray(new MemberSheetImport, $file)[0] ?? [];

        [$headerIndex, $columns] = $this->locateHeader($sheet);

        if ($headerIndex === null) {
            return ['rows' => [], 'error' => 'Không tìm thấy dòng tiêu đề có cột "Họ và tên". Hãy dùng tệp mẫu hoặc đặt tên cột giống tệp mẫu.'];
        }

        // Dòng chỉ còn số thứ tự (STT) hoặc ô thuộc cột không dùng tới vẫn được coi là dòng trống.
        $dataRows = array_filter(
            array_slice($sheet, $headerIndex + 1, null, true),
            fn (array $r) => collect($columns)->contains(fn ($col) => trim((string) ($r[$col] ?? '')) !== '')
        );

        if (count($dataRows) > self::MAX_ROWS) {
            return ['rows' => [], 'error' => 'Tệp có '.count($dataRows).' dòng, vượt quá giới hạn '.self::MAX_ROWS.' dòng mỗi lần nhập.'];
        }

        $existingCodes = Member::withTrashed()->pluck('code')->map(fn ($c) => mb_strtoupper($c))->flip();
        $seenCodes = [];
        $rows = [];

        foreach ($dataRows as $index => $raw) {
            $row = ['line' => $index + 1, 'status' => 'valid', 'errors' => [], 'data' => $this->extract($raw, $columns)];
            $row['errors'] = $this->validate($row['data']);
            $code = $row['data']['code'];

            if ($row['errors']) {
                $row['status'] = 'error';
            } elseif ($code !== null && (isset($existingCodes[mb_strtoupper($code)]) || isset($seenCodes[mb_strtoupper($code)]))) {
                $row['status'] = 'duplicate';
                $row['errors'] = [isset($existingCodes[mb_strtoupper($code)]) ? 'Mã đoàn viên đã có trong hệ thống.' : 'Mã bị lặp lại trong tệp.'];
            }

            if ($code !== null) {
                $seenCodes[mb_strtoupper($code)] = true;
            }

            $rows[] = $row;
        }

        return ['rows' => $rows, 'error' => null];
    }

    /**
     * Ghi các dòng đã chọn vào tổ; dòng có mã vừa bị người khác dùng mất thì bỏ qua.
     *
     * @param  list<array<string, mixed>>  $rows  phần "data" của các dòng hợp lệ
     * @return array{added: int, skipped: int}
     */
    public function import(array $rows, int $unionGroupId): array
    {
        return DB::transaction(function () use ($rows, $unionGroupId) {
            $taken = Member::withTrashed()->lockForUpdate()->pluck('code')->map(fn ($c) => mb_strtoupper($c))->flip()->all();
            $next = $this->nextCodeNumber(array_keys($taken));
            $added = 0;
            $skipped = 0;

            foreach ($rows as $data) {
                $code = $data['code'];

                if ($code === null) {
                    do {
                        $code = 'DV-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
                    } while (isset($taken[$code]));
                } elseif (isset($taken[mb_strtoupper($code)])) {
                    $skipped++;

                    continue;
                }

                Member::create(['code' => $code, 'union_group_id' => $unionGroupId] + $data);
                $taken[mb_strtoupper($code)] = true;
                $added++;
            }

            return ['added' => $added, 'skipped' => $skipped];
        });
    }

    /** @return array{0: ?int, 1: array<string, int>} */
    protected function locateHeader(array $sheet): array
    {
        foreach (array_slice($sheet, 0, 15, true) as $index => $cells) {
            $columns = [];

            foreach ($cells as $col => $value) {
                $name = self::normalize((string) $value);

                foreach (self::HEADERS as $field => $aliases) {
                    if (! isset($columns[$field]) && in_array($name, $aliases, true)) {
                        $columns[$field] = $col;
                    }
                }
            }

            if (isset($columns['full_name'])) {
                return [$index, $columns];
            }
        }

        return [null, []];
    }

    /** @return array<string, mixed> */
    protected function extract(array $raw, array $columns): array
    {
        $get = fn (string $field) => isset($columns[$field]) ? $raw[$columns[$field]] ?? null : null;
        $text = fn (string $field) => ($v = trim((string) $get($field))) === '' ? null : $v;

        $phone = $text('phone');
        if ($phone !== null && preg_match('/^[1-9]\d{8}$/', $phone)) {
            $phone = '0'.$phone; // Excel làm mất số 0 ở đầu khi ô được lưu dạng số
        }

        $gender = $text('gender');
        $status = $text('status');

        return [
            'code' => $text('code'),
            'full_name' => $text('full_name') !== null ? preg_replace('/\s+/u', ' ', $text('full_name')) : null,
            'dob' => $this->parseDate($get('dob')),
            'gender' => $gender === null ? null : (self::GENDERS[self::normalize($gender)] ?? $gender),
            'email' => $text('email'),
            'phone' => $phone,
            'position' => $text('position'),
            'department' => $text('department'),
            'joined_union_date' => $this->parseDate($get('joined_union_date')),
            'status' => $status === null ? 'active' : (self::STATUSES[self::normalize($status)] ?? $status),
            'note' => $text('note'),
        ];
    }

    /** @return list<string> */
    protected function validate(array $data): array
    {
        $request = new StoreMemberRequest;
        $rules = collect($request->rules())->only(array_keys($data))->all();
        $rules['code'] = ['nullable', 'string', 'max:30'];

        $messages = $request->messages() + [
            'dob.date' => 'Ngày sinh không đúng định dạng (dd/mm/yyyy).',
            'joined_union_date.date' => 'Ngày tham gia không đúng định dạng (dd/mm/yyyy).',
            'status.in' => 'Trạng thái chỉ nhận: Đang hoạt động, Ngưng hoạt động, Đã chuyển.',
            'gender.in' => 'Giới tính chỉ nhận: Nam, Nữ, Khác.',
            'code.max' => 'Mã đoàn viên tối đa 30 ký tự.',
        ];

        return Validator::make($data, $rules, $messages)->errors()->all();
    }

    /** Ngày trong Excel có thể là số seri hoặc chuỗi dd/mm/yyyy; trả về Y-m-d, hoặc giữ nguyên chuỗi lạ để báo lỗi. */
    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            $problems = \DateTime::getLastErrors();

            if ($date && ! ($problems && ($problems['warning_count'] || $problems['error_count']))) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    /** @param  list<string>  $codes */
    protected function nextCodeNumber(array $codes): int
    {
        $max = 0;

        foreach ($codes as $code) {
            if (preg_match('/^DV-(\d+)$/', $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max + 1;
    }

    public static function normalize(string $value): string
    {
        $ascii = mb_strtolower(Str::ascii($value));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? $ascii);
    }
}
