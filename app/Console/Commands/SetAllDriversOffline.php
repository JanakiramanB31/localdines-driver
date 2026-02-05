<?php

namespace App\Console\Commands;

use App\Models\FoodDeliveryPartner;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SetAllDriversOffline extends Command
{
  protected $signature = 'drivers:set-offline';
  protected $description = 'Set all drivers duty status to offline';

  public function handle()
  {
    $onlineDriversCount = FoodDeliveryPartner::where('duty_status', true)->count();

    if ($onlineDriversCount === 0) {
      $this->info('No online drivers found to update');
      return 0;
    }

    $updatedCount = FoodDeliveryPartner::where('duty_status', true)
      ->update([
        'duty_status' => false,
        'updated_at' => Carbon::now('UTC')
      ]);

    $this->info("{$updatedCount} driver(s) duty status updated to offline");
    return 0;
  }
}
