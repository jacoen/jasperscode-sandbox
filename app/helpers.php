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

if (! function_exists('generateDigitCode')) {
    function generateDigitCode()
    {
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= rand(0,9);
        }

        return $code;
    }
}
