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
        // Patrón compatible con cPanel compartido (sin Supervisor/Redis):
        // el cron dispara schedule:run cada minuto, y este comando procesa
        // la cola `database` y se detiene solo. Ver PERF-001 y
        // docs/auditoria/infraestructura-actual.md.
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();

        // Huérfanos de storage/app/temp: solo aparecen si un request de
        // ResponderCartaController murió antes de su limpieza normal
        // (cliente desconectado, timeout, OOM). Ver PDF-001/infraestructura-actual.md.
        $schedule->command('temp:limpiar')
            ->daily()
            ->withoutOverlapping();
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
