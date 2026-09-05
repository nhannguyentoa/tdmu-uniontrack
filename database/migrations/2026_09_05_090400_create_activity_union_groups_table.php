<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_union_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
            $table->enum('role', ['phoi_hop', 'tham_gia'])->default('phoi_hop');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'union_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_union_groups');
    }
};
