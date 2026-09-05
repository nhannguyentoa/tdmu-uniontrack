<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_plans', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->unsignedTinyInteger('month');
            $table->string('title');
            $table->foreignId('host_union_group_id')->nullable()->constrained('union_groups')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('note')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'done', 'cancelled'])->default('planned');
            $table->foreignId('activity_id')->nullable()->unique()->constrained('activities')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academic_year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_plans');
    }
};
