<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdminRecoverCommand extends Command
{
    protected $signature = 'app:admin-recover {--force : Skip the confirmation prompt}';

    protected $description = 'Delete all users and passkeys to re-enable first-run onboarding';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete ALL users and passkeys. Are you sure?')) {
            $this->info('Command cancelled.');

            return Command::SUCCESS;
        }

        DB::table('passkeys')->truncate();
        User::query()->truncate();

        $this->warn('All users and passkeys have been deleted.');
        $this->info('The application has been reset to first-run state. Visit the app to start onboarding.');

        return Command::SUCCESS;
    }
}
