<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $groups = UnionGroup::where('status', 'active')->get();
        $activityTypes = ActivityType::all();
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $states = ['notStarted', 'inProgress', 'completed', 'completed', 'cancelled'];

        foreach ($groups as $group) {
            $officer = $group->officers()->first();

            foreach (range(1, fake()->numberBetween(3, 5)) as $i) {
                $state = fake()->randomElement($states);

                $activity = Activity::factory()->{$state}()->create([
                    'union_group_id' => $group->id,
                    'activity_type_id' => $activityTypes->random()->id,
                    'responsible_user_id' => $officer?->id,
                    'responsible_name' => $officer?->name,
                    'created_by' => $officer?->id ?? $admin?->id,
                ]);

                $activity->statusHistories()->create([
                    'status' => $activity->status,
                    'progress' => $activity->progress,
                    'note' => 'Tạo mới hoạt động (dữ liệu mẫu).',
                    'changed_by' => $activity->created_by,
                ]);

                $members = $group->members()->where('status', 'active')->inRandomOrder()
                    ->limit(fake()->numberBetween(2, min(5, max(2, $group->members()->count()))))
                    ->get();

                foreach ($members as $member) {
                    $activity->participants()->create([
                        'member_id' => $member->id,
                        'status' => $activity->status === 'completed' ? 'attended' : 'registered',
                        'registered_at' => $activity->start_time,
                    ]);
                }

                if ($activity->status === Activity::STATUS_COMPLETED) {
                    $activity->update(['actual_quantity' => $members->count()]);
                }
            }
        }
    }
}
