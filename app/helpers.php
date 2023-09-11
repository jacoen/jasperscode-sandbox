<?php

use Carbon\Carbon;

function lastUpdated(string $time)
{
    $time = Carbon::parse($time);

    if ($time->gt(now()->subDay())) {
        return $time->diffForHumans();
    }

    return $time->format('d M Y').' at '.$timestamp = $time->format('H:m');
}
