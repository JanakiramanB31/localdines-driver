<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * The Artisan commands provided by your application.
   *
   * @var array
  */
  protected $commands = [
    \App\Console\Commands\GenerateSwaggerDocs::class,
    \App\Console\Commands\SetAllDriversOffline::class,
  ];

  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
  */
  protected function schedule(Schedule $schedule)
  {
    // Map of day numbers to delivery column names (0 = Sunday, 1 = Monday, etc.)
    $dayColumns = [
      0 => ['to' => 'd_sunday_to', 'dayoff' => 'd_sunday_dayoff'],
      1 => ['to' => 'd_monday_to', 'dayoff' => 'd_monday_dayoff'],
      2 => ['to' => 'd_tuesday_to', 'dayoff' => 'd_tuesday_dayoff'],
      3 => ['to' => 'd_wednesday_to', 'dayoff' => 'd_wednesday_dayoff'],
      4 => ['to' => 'd_thursday_to', 'dayoff' => 'd_thursday_dayoff'],
      5 => ['to' => 'd_friday_to', 'dayoff' => 'd_friday_dayoff'],
      6 => ['to' => 'd_saturday_to', 'dayoff' => 'd_saturday_dayoff'],
    ];

    // Get current day of week (0 = Sunday, 6 = Saturday)
    $dayOfWeek = date('w');
    $toColumn = $dayColumns[$dayOfWeek]['to'];
    $dayoffColumn = $dayColumns[$dayOfWeek]['dayoff'];

    // Get delivery close time and dayoff status from working times table
    $workingTime = \Illuminate\Support\Facades\DB::table('food_delivery_working_times')
      ->select([$toColumn, $dayoffColumn])
      ->first();

    // Skip scheduling if it's a day off
    if ($workingTime && $workingTime->$dayoffColumn === 'T') {
      return;
    }

    // Use today's delivery close time from database or default to 22:00
    $time = ($workingTime && $workingTime->$toColumn) ? $workingTime->$toColumn : '22:00';

    // Set all drivers offline at the configured delivery close time for today
    $schedule->command('drivers:set-offline')
      ->dailyAt($time)
      ->timezone('UTC');
  }
}
