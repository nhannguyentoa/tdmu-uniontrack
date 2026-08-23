<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $groups = UnionGroup::where('status', 'active')->get();

        foreach ($groups as $group) {
            Member::factory()
                ->count(fake()->numberBetween(8, 15))
                ->create(['union_group_id' => $group->id]);
        }

        // Một vài đoàn viên ở trạng thái ngưng hoạt động / đã chuyển để kiểm thử bộ lọc.
        Member::factory()->count(3)->create([
            'union_group_id' => $groups->first()->id,
            'status' => 'inactive',
        ]);
        Member::factory()->count(2)->create([
            'union_group_id' => $groups->last()->id,
            'status' => 'transferred',
        ]);

        // Gán tài khoản đoàn viên demo vào một đoàn viên có thật để kiểm thử đăng nhập.
        $demoUser = User::where('email', 'member@tdmu.edu.vn')->first();
        $firstMember = Member::where('union_group_id', $groups->first()->id)
            ->where('status', 'active')
            ->first();

        if ($demoUser && $firstMember) {
            $firstMember->update(['user_id' => $demoUser->id]);
        }
    }
}
