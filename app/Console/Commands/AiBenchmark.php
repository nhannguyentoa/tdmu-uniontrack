<?php

namespace App\Console\Commands;

use App\Services\Ai\AiException;
use App\Services\Ai\AiManager;
use App\Services\Ai\BenchmarkCases;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AiBenchmark extends Command
{
    protected $signature = 'ai:benchmark
        {--provider=mock : Danh sách nhà cung cấp cách nhau dấu phẩy (mock,gemini,claude)}
        {--limit=0 : Chỉ chạy N ca đầu (0 = tất cả 54 ca)}
        {--delay=0 : Số giây nghỉ giữa các lần gọi (tránh vượt hạn mức miễn phí)}';

    protected $description = 'Đo độ chính xác của trợ lý AI thẩm định thi đua trên bộ ca thử mô phỏng và xuất kết quả CSV.';

    public function handle(AiManager $manager): int
    {
        $cases = BenchmarkCases::all();
        if ((int) $this->option('limit') > 0) {
            $cases = array_slice($cases, 0, (int) $this->option('limit'));
        }

        $dir = storage_path('app/ai-benchmark');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd-His');
        $summary = [];

        foreach (array_filter(array_map('trim', explode(',', (string) $this->option('provider')))) as $name) {
            try {
                $provider = $manager->provider($name);
            } catch (AiException $e) {
                $this->error("[{$name}] {$e->getMessage()}");

                continue;
            }

            $this->info("Chạy {$name} / {$provider->model()} trên ".count($cases).' ca...');
            $rows = [];
            $bar = $this->output->createProgressBar(count($cases));

            foreach ($cases as $case) {
                $row = ['case' => $case['id'], 'scenario' => $case['scenario'], 'expected_status' => $case['expected_status'],
                    'expected_min' => $case['expected_min'], 'expected_max' => $case['expected_max'],
                    'status' => '', 'score' => '', 'status_ok' => 0, 'score_in_band' => 0, 'score_error' => '', 'confidence' => '', 'tokens_in' => '', 'tokens_out' => '', 'error' => ''];

                try {
                    $result = $provider->evaluate($case['context']);
                    $score = $result->suggestedScore;
                    $error = $score < $case['expected_min'] ? $case['expected_min'] - $score : max(0, $score - $case['expected_max']);
                    $row = array_merge($row, [
                        'status' => $result->evidenceStatus, 'score' => $score,
                        'status_ok' => (int) ($result->evidenceStatus === $case['expected_status']),
                        'score_in_band' => (int) ($error == 0), 'score_error' => round($error, 2),
                        'confidence' => $result->confidence, 'tokens_in' => $result->inputTokens, 'tokens_out' => $result->outputTokens,
                    ]);
                } catch (AiException $e) {
                    $row['error'] = $e->getMessage();
                }

                $rows[] = $row;
                $bar->advance();
                if ((int) $this->option('delay') > 0) {
                    sleep((int) $this->option('delay'));
                }
            }
            $bar->finish();
            $this->newLine(2);

            $valid = array_filter($rows, fn ($r) => $r['error'] === '');
            $n = max(count($valid), 1);
            $summary[] = [
                $name.' / '.$provider->model(), count($rows), count($rows) - count($valid),
                round(100 * array_sum(array_column($valid, 'status_ok')) / $n, 1).'%',
                round(100 * array_sum(array_column($valid, 'score_in_band')) / $n, 1).'%',
                round(array_sum(array_column($valid, 'score_error')) / $n, 2),
                array_sum(array_map('intval', array_column($valid, 'tokens_in'))),
                array_sum(array_map('intval', array_column($valid, 'tokens_out'))),
            ];

            $path = "{$dir}/{$stamp}-{$name}.csv";
            $handle = fopen($path, 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_keys($rows[0] ?? ['case']));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            $this->line("  Đã lưu chi tiết: {$path}");
        }

        if ($summary !== []) {
            $this->table(['Nhà cung cấp / model', 'Số ca', 'Lỗi gọi', 'Đúng mức minh chứng', 'Điểm trong khoảng đúng', 'Sai số điểm TB', 'Token vào', 'Token ra'], $summary);
        }

        return self::SUCCESS;
    }
}
