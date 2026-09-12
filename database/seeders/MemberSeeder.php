<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\UnionGroup;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $groups = UnionGroup::where('status', 'active')->get();

        foreach ($groups as $group) {
            Member::factory()
                ->count(fake()->numberBetween(5, 8))
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
    }
}
