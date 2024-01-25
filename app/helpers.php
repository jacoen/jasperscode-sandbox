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

if (! function_exists('maskEmail')) {
    function maskEmail($email)
    {
        $emailparts = explode("@", $email);
        $emailName = substr($emailparts[0], 0, 1);
        $emailName .= str_repeat("*", 8);

        return $emailName."@".$emailparts[1];
    }
}
