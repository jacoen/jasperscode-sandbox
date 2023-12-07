<?php

use Carbon\Carbon;

if (! function_exists('lastUpdated')) {
    function lastUpdated(string $time)
    {
        $time = Carbon::parse($time);

        if ($time->gt(now()->subDay())) {
            return $time->diffForHumans();
        }

        return $time->format('d M Y').' at '.$time->format('H:m');
    }
}
