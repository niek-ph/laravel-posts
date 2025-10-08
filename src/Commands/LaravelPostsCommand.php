<?php

namespace NiekPH\LaravelPosts\Commands;

use Illuminate\Console\Command;

class LaravelPostsCommand extends Command
{
    public $signature = 'laravel-posts';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
