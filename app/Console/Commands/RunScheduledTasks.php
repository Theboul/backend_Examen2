<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledTasks extends Command
{
    protected $signature = 'schedule:run-tasks';
    protected $description = 'Run all scheduled tasks manually';

    public function handle()
    {
        $this->info('🚀 Running scheduled tasks...');
        Log::info('🚀 SCHEDULED TASKS STARTED');

        // Ejecutar el comando de ausencias
        try {
            $this->call('sis:registrar-ausencias');
            $this->info('✅ sis:registrar-ausencias executed');
        } catch (\Exception $e) {
            $this->error('❌ Error in sis:registrar-ausencias: ' . $e->getMessage());
            Log::error('Error in sis:registrar-ausencias: ' . $e->getMessage());
        }

        // Ejecutar el test scheduler
        try {
            $this->call('test:scheduler');
            $this->info('✅ test:scheduler executed');
        } catch (\Exception $e) {
            $this->error('❌ Error in test:scheduler: ' . $e->getMessage());
            Log::error('Error in test:scheduler: ' . $e->getMessage());
        }

        Log::info('✅ SCHEDULED TASKS COMPLETED');
        $this->info('🎯 All scheduled tasks completed');

        return Command::SUCCESS;
    }
}