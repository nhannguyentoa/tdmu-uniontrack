<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('academic_year', 20);
            $table->string('classification', 20)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('scored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_evaluations');
    }
};
