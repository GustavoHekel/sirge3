<?php

namespace App\Console;

use DB;
use App\Http\Controllers\Controller;
use App\Models\Scheduler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\CommandScheduler::class        
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {    

        //$schedule->command('scheduler:execute',[$periodo_a_automatizar])->everyMinute();
        /*Scheduler::where('contexto','migracion_beneficiarios')
                  ->where('periodo',201601)
                  ->update(['estado' => 0]);*/

        $periodo_a_automatizar = Scheduler::select(DB::raw('max(periodo)'))->where('estado',1)->first()->max;                
        
        //$schedule->command('scheduler:execute',[$periodo_a_automatizar])->everyMinute();

        //$schedule->call('App\Http\Controllers\DatawarehouseController@ejecutarTodas',[$periodo_a_automatizar])->everyMinute();
       /* ->when(function ($periodo_a_automatizar) {                                                

                    $estado = Scheduler::select('estado')
                                ->where('contexto','migracion_beneficiarios')
                                ->where('periodo',$periodo_a_automatizar)
                                ->first()->estado;                    
                    
                    if($estado == 0){
                        return true;
                    }
                    else{
                        return false;
                    }

        })->sendOutputTo('/home/vnc/log_scheduler.txt');*/
    }
}
