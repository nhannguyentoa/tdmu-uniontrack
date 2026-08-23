<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('role_note')->nullable();
            $table->enum('status', ['registered', 'attended', 'absent', 'cancelled'])->default('registered');
            $table->dateTime('registered_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'member_id']);
            $table->index('activity_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_participants');
    }
};
