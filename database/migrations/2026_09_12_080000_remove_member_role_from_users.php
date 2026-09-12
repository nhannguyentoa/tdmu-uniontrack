<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Không còn tài khoản đăng nhập vai trò "Đoàn viên" — chuyển các tài khoản
        // cũ (nếu có) sang Cán bộ công đoàn để không ai bị mất quyền đăng nhập.
        DB::table('users')->where('role', 'member')->update(['role' => 'officer']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'officer') NOT NULL DEFAULT 'officer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'officer', 'member') NOT NULL DEFAULT 'member'");
    }
};
