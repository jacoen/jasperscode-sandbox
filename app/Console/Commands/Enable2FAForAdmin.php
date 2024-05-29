<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class Enable2FAForAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '2fa:enableForAdmins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enables 2fa for all admins that have not enabled 2fa yet.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;

        $users = User::where('two_factor_enabled', false)->role(['Super Admin', 'Admin'])->get();
        foreach ($users as $user) {
            $user->two_factor_enabled = true;
            $user->save();

            $count++;
        }

        $this->info('Two factor authentication has been enabled for '.$count.' accounts');
    }
}
