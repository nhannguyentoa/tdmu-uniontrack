<?php

namespace Database\Seeders;

use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Quản trị viên hệ thống',
            'email' => 'admin@tdmu.edu.vn',
            'password' => Hash::make('Admin@123'),
            'role' => User::ROLE_ADMIN,
            'phone' => '0901111111',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $groups = UnionGroup::where('status', 'active')->orderBy('id')->get();

        $officerNames = [
            'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Văn Cường', 'Phạm Thị Dung',
            'Hoàng Văn Em', 'Đỗ Thị Phương', 'Vũ Văn Giang',
        ];

        foreach ($groups as $index => $group) {
            $name = $officerNames[$index] ?? fake('vi_VN')->name();

            $officer = User::create([
                'name' => $name,
                'email' => 'officer'.($index + 1).'@tdmu.edu.vn',
                'password' => Hash::make('Officer@123'),
                'role' => User::ROLE_OFFICER,
                'phone' => '090222'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $officer->managedUnionGroups()->attach($group->id);
        }

        // Một cán bộ công đoàn phụ trách 2 tổ để kiểm thử trường hợp quản lý nhiều tổ.
        if ($groups->count() >= 2) {
            $multiOfficer = User::create([
                'name' => 'Trịnh Thị Hoa',
                'email' => 'officer.multi@tdmu.edu.vn',
                'password' => Hash::make('Officer@123'),
                'role' => User::ROLE_OFFICER,
                'phone' => '0903333333',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $multiOfficer->managedUnionGroups()->attach($groups->take(2)->pluck('id'));
        }

        // Tài khoản đoàn viên mẫu để kiểm thử đăng nhập với vai trò Đoàn viên.
        User::create([
            'name' => 'Đoàn viên Demo',
            'email' => 'member@tdmu.edu.vn',
            'password' => Hash::make('Member@123'),
            'role' => User::ROLE_MEMBER,
            'phone' => '0904444444',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
