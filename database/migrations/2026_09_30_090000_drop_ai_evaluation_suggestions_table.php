<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tính năng trợ lý AI thẩm định đã được gỡ bỏ; xóa bảng lưu gợi ý còn sót lại trên các môi trường đã từng chạy migration cũ.
     */
    public function up(): void
    {
        Schema::dropIfExists('ai_evaluation_suggestions');
    }

    public function down(): void
    {
        // Không khôi phục: tính năng đã bị gỡ bỏ.
    }
};
