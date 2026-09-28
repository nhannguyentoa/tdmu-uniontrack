<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Các bước có kiểm tra tồn tại để có thể chạy lại an toàn nếu lần chạy trước bị gián đoạn giữa chừng.
        if (! Schema::hasColumn('activity_plans', 'activity_type_id')) {
            Schema::table('activity_plans', function (Blueprint $table) {
                $table->foreignId('activity_type_id')->nullable()->after('department_id')
                    ->constrained('activity_types')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('activity_plans', 'counts_for_evaluation')) {
            Schema::table('activity_plans', function (Blueprint $table) {
                $table->boolean('counts_for_evaluation')->default(false)->after('note');
                $table->decimal('evaluation_max_score', 5, 2)->nullable()->after('counts_for_evaluation');
            });
        }

        if (! Schema::hasTable('activity_plan_union_groups')) {
            Schema::create('activity_plan_union_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_plan_id')->constrained('activity_plans')->cascadeOnDelete();
                $table->foreignId('union_group_id')->constrained('union_groups')->cascadeOnDelete();
                $table->string('role', 15)->default('phoi_hop');
                $table->timestamps();

                $table->unique(['activity_plan_id', 'union_group_id'], 'apug_plan_group_unique');
            });
        }

        // Trạng thái kế hoạch nay được tính tự động (Dự kiến / Quá hạn / theo hoạt động đã chuyển);
        // cột status chỉ còn phân biệt 'planned' và 'cancelled' (đánh dấu hủy thủ công).
        DB::table('activity_plans')->whereIn('status', ['in_progress', 'done'])->update(['status' => 'planned']);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_plan_union_groups');

        Schema::table('activity_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_type_id');
            $table->dropColumn(['counts_for_evaluation', 'evaluation_max_score']);
        });
    }
};
