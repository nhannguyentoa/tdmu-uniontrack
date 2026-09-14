<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->timestamp('evaluation_approved_at')->nullable()->after('evaluation_max_score');
            $table->foreignId('evaluation_approved_by')->nullable()->after('evaluation_approved_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evaluation_approved_by');
            $table->dropColumn('evaluation_approved_at');
        });
    }
};
