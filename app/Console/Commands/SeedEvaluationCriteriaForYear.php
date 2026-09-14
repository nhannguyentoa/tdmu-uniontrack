<?php

namespace App\Console\Commands;

use App\Models\EvaluationCriterion;
use App\Support\AcademicYear;
use Illuminate\Console\Command;

class SeedEvaluationCriteriaForYear extends Command
{
    protected $signature = 'app:seed-evaluation-criteria {academic_year? : Nam hoc dich, mac dinh la nam hoc hien tai} {--from= : Nam hoc nguon de sao chep, mac dinh la nam hoc gan nhat da co tieu chi}';

    protected $description = 'Sao chep bo tieu chi thi dua tu mot nam hoc sang nam hoc khac lam ban nhap co san. An toan chay lai nhieu lan (bo qua neu nam dich da co tieu chi).';

    public function handle(): int
    {
        $targetYear = $this->argument('academic_year') ?: AcademicYear::forDate(now());

        // Chi kiem tra tieu chi "that" (khac nhom thuong) - tieu chi Diem thuong co the da duoc
        // tu dong tao rieng le boi EvaluationService::bonusCriterion() ngay khi ai do mo trang
        // cham diem cho nam hoc nay, du chua he co tieu chi nao khac. Neu chi kiem tra "co ban
        // ghi nao thuoc nam hoc nay khong" thi se bi bo qua oan, khong bao gio sao chep duoc nua.
        $hasRealCriteria = EvaluationCriterion::where('academic_year', $targetYear)
            ->where('group_label', '<>', EvaluationCriterion::GROUP_BONUS)
            ->exists();

        if ($hasRealCriteria) {
            $this->info("Nam hoc {$targetYear} da co tieu chi, bo qua.");

            return self::SUCCESS;
        }

        $sourceYear = $this->option('from')
            ?: EvaluationCriterion::where('academic_year', '<>', $targetYear)->max('academic_year');

        if (! $sourceYear) {
            $this->error('Khong tim thay nam hoc nguon nao co tieu chi de sao chep.');

            return self::FAILURE;
        }

        $sourceCriteria = EvaluationCriterion::where('academic_year', $sourceYear)->orderBy('order_no')->get();

        if ($sourceCriteria->isEmpty()) {
            $this->error("Nam hoc nguon {$sourceYear} khong co tieu chi nao.");

            return self::FAILURE;
        }

        foreach ($sourceCriteria as $criterion) {
            // updateOrCreate theo (academic_year, order_no) de "de len" dung vi tri neu truoc do
            // da co san mot ban ghi tam (vi du tieu chi Diem thuong tu dong tao o order_no nay).
            EvaluationCriterion::updateOrCreate(
                ['academic_year' => $targetYear, 'order_no' => $criterion->order_no],
                [
                    'group_label' => $criterion->group_label,
                    'content' => $criterion->content,
                    'max_score' => $criterion->max_score,
                    'department_id' => $criterion->department_id,
                ]
            );
        }

        $this->info("Da sao chep {$sourceCriteria->count()} tieu chi tu nam hoc {$sourceYear} sang {$targetYear} (ban nhap, can vao trang Quan ly tieu chi de chinh lai noi dung/ngay thang cho phu hop ke hoach nam moi).");

        return self::SUCCESS;
    }
}
