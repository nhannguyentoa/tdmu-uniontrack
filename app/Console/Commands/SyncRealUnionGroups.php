<?php

namespace App\Console\Commands;

use App\Models\UnionGroup;
use Database\Seeders\UnionGroupSeeder;
use Illuminate\Console\Command;

class SyncRealUnionGroups extends Command
{
    protected $signature = 'app:sync-real-union-groups';

    protected $description = 'Đổi 16 tổ công đoàn demo thành tên thật của Công đoàn Trường ĐHTDM, giữ nguyên id (không mất đoàn viên/hoạt động đã có)';

    public function handle(): int
    {
        $real = UnionGroupSeeder::realGroups();

        $legacyGroups = UnionGroup::where('code', 'like', 'TCD-%')
            ->orderBy('id')
            ->get()
            ->values();

        $legacyIndex = 0;
        $created = 0;
        $renamed = 0;
        $skipped = 0;

        foreach ($real as $data) {
            if (UnionGroup::where('code', $data['code'])->exists()) {
                $skipped++;

                continue;
            }

            $legacy = $legacyGroups->get($legacyIndex);
            $legacyIndex++;

            if ($legacy) {
                $legacy->update(array_merge($data, ['status' => 'active']));
                $renamed++;
            } else {
                UnionGroup::create(array_merge($data, [
                    'status' => 'active',
                    'description' => 'Tổ công đoàn trực thuộc Công đoàn Cơ sở Trường Đại học Thủ Dầu Một.',
                ]));
                $created++;
            }
        }

        $this->info("Đã đổi tên {$renamed} tổ, tạo mới {$created} tổ, bỏ qua {$skipped} tổ đã đồng bộ trước đó.");

        return self::SUCCESS;
    }
}
