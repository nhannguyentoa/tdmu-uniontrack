<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('union_group_officer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['union_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('union_group_officer');
    }
};
