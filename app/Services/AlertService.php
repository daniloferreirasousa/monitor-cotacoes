<?php

namespace App\Services;

class AlertService
{
   public function processPendingAlerts(): int
   {
    $triggered = 0;
    PriceAlert::where('is_triggered', false)->with(['asset', 'user'])
        ->chunkById(100, function () use ($triggered) {
            foreach ($alerts as $alert )
        });
   }
}
