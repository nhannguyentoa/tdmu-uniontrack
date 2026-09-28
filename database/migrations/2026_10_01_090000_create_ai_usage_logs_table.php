<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nhật ký mỗi lần dùng trợ lý AI (nhập điểm danh từ ảnh, soạn văn bản): dùng để đo độ chính xác,
     * thời gian xử lý và chi phí token phục vụ báo cáo nghiên cứu.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_usage_logs')) {
            return;
        }

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 30);
            $table->string('provider', 20);
            $table->string('model', 60);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('subject', 60)->nullable();
            $table->boolean('success')->default(true);
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->index(['feature', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
