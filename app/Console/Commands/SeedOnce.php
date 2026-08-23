<?php

namespace App\Console\Commands;

use App\Models\UnionGroup;
use Illuminate\Console\Command;

class SeedOnce extends Command
{
    protected $signature = 'app:seed-once';

    protected $description = 'Seed dữ liệu mẫu nếu database đang trống (an toàn khi chạy lại nhiều lần lúc container khởi động)';

    public function handle(): int
    {
        if (UnionGroup::count() > 0) {
            $this->info('Database đã có dữ liệu, bỏ qua seeding.');

            return self::SUCCESS;
        }

        $this->info('Database đang trống, tiến hành seed dữ liệu mẫu...');
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
