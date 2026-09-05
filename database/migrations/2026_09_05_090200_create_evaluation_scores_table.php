<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_criterion_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
            $table->decimal('self_score', 5, 2)->nullable();
            $table->text('self_note')->nullable();
            $table->foreignId('self_scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('self_scored_at')->nullable();
            $table->decimal('verified_score', 5, 2)->nullable();
            $table->text('verified_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_criterion_id', 'union_group_id'], 'eval_scores_criterion_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};
