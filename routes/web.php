<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityEvidenceController;
use App\Http\Controllers\ActivityParticipantController;
use App\Http\Controllers\ActivityPlanController;
use App\Http\Controllers\ActivityTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationCriterionController;
use App\Http\Controllers\EvaluationScoreController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnionGroupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Tổ công đoàn
    Route::get('union-groups', [UnionGroupController::class, 'index'])->name('union-groups.index');
    Route::get('union-groups/create', [UnionGroupController::class, 'create'])->name('union-groups.create');
    Route::post('union-groups', [UnionGroupController::class, 'store'])->name('union-groups.store');
    Route::get('union-groups/{union_group}', [UnionGroupController::class, 'show'])->name('union-groups.show');
    Route::get('union-groups/{union_group}/edit', [UnionGroupController::class, 'edit'])->name('union-groups.edit');
    Route::put('union-groups/{union_group}', [UnionGroupController::class, 'update'])->name('union-groups.update');
    Route::delete('union-groups/{union_group}', [UnionGroupController::class, 'destroy'])->name('union-groups.destroy');

    // Đoàn viên
    Route::get('members', [MemberController::class, 'index'])->name('members.index');
    Route::get('members/export', [MemberController::class, 'export'])->name('members.export');
    Route::get('members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('members', [MemberController::class, 'store'])->name('members.store');
    Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Loại hoạt động (quản lý bởi Admin)
    Route::get('activity-types', [ActivityTypeController::class, 'index'])->name('activity-types.index');
    Route::post('activity-types', [ActivityTypeController::class, 'store'])->name('activity-types.store');
    Route::put('activity-types/{activity_type}', [ActivityTypeController::class, 'update'])->name('activity-types.update');
    Route::delete('activity-types/{activity_type}', [ActivityTypeController::class, 'destroy'])->name('activity-types.destroy');

    // Hoạt động công đoàn
    Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('activities/export', [ActivityController::class, 'export'])->name('activities.export');
    Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
    Route::get('activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::patch('activities/{activity}/quick-update', [ActivityController::class, 'quickUpdate'])->name('activities.quick-update');
    Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    // Người tham gia hoạt động
    Route::get('activities/{activity}/participants/create', [ActivityParticipantController::class, 'create'])->name('activities.participants.create');
    Route::post('activities/{activity}/participants', [ActivityParticipantController::class, 'store'])->name('activities.participants.store');
    Route::get('activities/{activity}/participants/export', [ActivityParticipantController::class, 'export'])->name('activities.participants.export');
    Route::delete('activities/{activity}/participants/{participant}', [ActivityParticipantController::class, 'destroy'])->name('activities.participants.destroy');

    // Minh chứng hoạt động
    Route::post('activities/{activity}/evidences', [ActivityEvidenceController::class, 'store'])->name('activities.evidences.store');
    Route::get('activities/{activity}/evidences/{evidence}/download', [ActivityEvidenceController::class, 'download'])->name('activities.evidences.download');
    Route::delete('activities/{activity}/evidences/{evidence}', [ActivityEvidenceController::class, 'destroy'])->name('activities.evidences.destroy');

    // Kế hoạch hoạt động năm học
    Route::get('activity-plans', [ActivityPlanController::class, 'index'])->name('activity-plans.index');
    Route::get('activity-plans/create', [ActivityPlanController::class, 'create'])->name('activity-plans.create');
    Route::post('activity-plans', [ActivityPlanController::class, 'store'])->name('activity-plans.store');
    Route::get('activity-plans/{activity_plan}/edit', [ActivityPlanController::class, 'edit'])->name('activity-plans.edit');
    Route::put('activity-plans/{activity_plan}', [ActivityPlanController::class, 'update'])->name('activity-plans.update');
    Route::delete('activity-plans/{activity_plan}', [ActivityPlanController::class, 'destroy'])->name('activity-plans.destroy');

    // Chấm điểm thi đua
    Route::get('evaluation/criteria', [EvaluationCriterionController::class, 'index'])->name('evaluation-criteria.index');
    Route::post('evaluation/criteria', [EvaluationCriterionController::class, 'store'])->name('evaluation-criteria.store');
    Route::put('evaluation/criteria/{evaluation_criterion}', [EvaluationCriterionController::class, 'update'])->name('evaluation-criteria.update');
    Route::delete('evaluation/criteria/{evaluation_criterion}', [EvaluationCriterionController::class, 'destroy'])->name('evaluation-criteria.destroy');

    Route::get('evaluation/report', [EvaluationScoreController::class, 'report'])->name('evaluation.report');
    Route::get('evaluation/report/export', [EvaluationScoreController::class, 'exportExcel'])->name('evaluation.report.export');
    Route::get('evaluation/verify', [EvaluationScoreController::class, 'editVerify'])->name('evaluation.verify.edit');
    Route::put('evaluation/verify', [EvaluationScoreController::class, 'updateVerify'])->name('evaluation.verify.update');
    Route::get('evaluation/{union_group}/self', [EvaluationScoreController::class, 'editSelf'])->name('evaluation.self.edit');
    Route::put('evaluation/{union_group}/self', [EvaluationScoreController::class, 'updateSelf'])->name('evaluation.self.update');

    // Báo cáo & thống kê
    Route::get('reports', [ReportController::class, 'month'])->name('reports.index');
    Route::get('reports/month', [ReportController::class, 'month'])->name('reports.month');
    Route::get('reports/semester', [ReportController::class, 'semester'])->name('reports.semester');
    Route::get('reports/year', [ReportController::class, 'year'])->name('reports.year');
    Route::get('reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Quản lý tài khoản (chỉ Admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

require __DIR__.'/auth.php';
