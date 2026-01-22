<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PurgeOldPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-old-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = \App\Models\Post::onlyTrashed()
        ->where('deleted_at', '<=', now()->subDays(10))
        ->forceDelete();

        $this->info("Purged {$count} old posts.");
    }
}
