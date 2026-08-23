<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
            $table->foreignId('activity_type_id')->constrained('activity_types')->restrictOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_name')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('location')->nullable();
            $table->longText('content')->nullable();
            $table->longText('goal')->nullable();
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->unsignedInteger('actual_quantity')->default(0);
            $table->enum('status', ['not_started', 'preparing', 'in_progress', 'completed', 'cancelled'])->default('not_started');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->decimal('budget', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('union_group_id');
            $table->index('activity_type_id');
            $table->index('status');
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
