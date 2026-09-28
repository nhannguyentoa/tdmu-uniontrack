<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_evaluation_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_criterion_id')->constrained('evaluation_criteria')->cascadeOnDelete();
            $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('model', 60);
            $table->decimal('suggested_score', 5, 2)->default(0);
            $table->string('confidence', 10)->default('low');
            $table->string('evidence_status', 15);
            $table->text('reasoning')->nullable();
            $table->json('matched_activity_ids')->nullable();
            $table->json('missing_items')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['evaluation_criterion_id', 'union_group_id', 'provider'], 'ai_sugg_criterion_group_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_evaluation_suggestions');
    }
};
