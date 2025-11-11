<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ... (otros jobs que puedas tener) ...

        /**
         * REGISTRAR AUSENCIAS (CU20)
         * Se ejecuta todos los días a las 23:50 (11:50 PM).
         * (Justo antes de medianoche para capturar todas las clases del día)
         */
        $schedule->command('sis:registrar-ausencias')
                 ->dailyAt('23:50')
                 ->timezone('America/La_Paz'); // ¡Importante usar la zona horaria de Bolivia!
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}