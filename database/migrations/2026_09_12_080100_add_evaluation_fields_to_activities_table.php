<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('counts_for_evaluation')->default(false)->after('budget');
            $table->decimal('evaluation_max_score', 5, 2)->nullable()->after('counts_for_evaluation');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['counts_for_evaluation', 'evaluation_max_score']);
        });
    }
};
