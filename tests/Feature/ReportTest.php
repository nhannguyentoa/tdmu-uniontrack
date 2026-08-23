<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_counts_only_activities_in_that_month(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();

        Activity::factory()->count(2)->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-03-10 08:00',
            'end_time' => '2026-03-10 10:00',
            'status' => 'completed',
        ]);
        Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-04-10 08:00',
            'end_time' => '2026-04-10 10:00',
        ]);

        $response = $this->actingAs($admin)->get('/reports/month?year=2026&month=3');

        $response->assertOk();
        $response->assertViewHas('report', fn ($report) => $report['total_activities'] === 2
            && $report['completed_activities'] === 2
            && $report['completion_rate'] === 100.0);
    }

    public function test_yearly_report_includes_group_comparison(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();
        Activity::factory()->completed()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-05-01 08:00',
        ]);

        $response = $this->actingAs($admin)->get('/reports/year?year=2026');

        $response->assertOk();
        $response->assertViewHas('report', fn ($report) => $report['group_comparison']->isNotEmpty());
    }

    public function test_report_excel_export_downloads(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/export/excel?type=month&year=2026&month=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_report_pdf_export_downloads(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/export/pdf?type=month&year=2026&month=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }
}
