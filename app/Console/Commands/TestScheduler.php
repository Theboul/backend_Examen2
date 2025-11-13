<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestScheduler extends Command
{
    protected $signature = 'test:scheduler';
    protected $description = 'Test if scheduler works';

    public function handle()
    {
        $this->info('✅ TEST SCHEDULER EXECUTED at: ' . now());
        Log::info('✅ TEST SCHEDULER EXECUTED at: ' . now());
        
        return Command::SUCCESS;
    }
}