<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->enum('group_label', ['I', 'II', 'III', 'thuong'])->default('I');
            $table->unsignedSmallInteger('order_no');
            $table->text('content');
            $table->decimal('max_score', 5, 2);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'order_no']);
            $table->index('group_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_criteria');
    }
};
