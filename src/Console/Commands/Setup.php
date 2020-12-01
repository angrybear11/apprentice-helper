<?php

namespace Voronoi\Apprentice\Console\Commands;

use Illuminate\Console\Command;
use Voronoi\Apprentice\Models\Invitation;
use Voronoi\Apprentice\Models\User;
use Voronoi\Apprentice\Libraries\QRCode;
use Illuminate\Support\Str;
use DB;
use App;
use Artisan;
use Exception;

class Setup extends Command
{
    protected $signature = 'apprentice:setup';

    protected $description = 'Sets up a new apprentice user';

    public function handle(QRCode $qrCodeGenerator)
    {
        $url = app()->make('Illuminate\Routing\UrlGenerator')->to(config('apprentice.path'));

        if (!$this->confirmHttps($url)) {
            return;
        }

        if (!$this->askToRunMigrations()) {
            return;
        }

        $this->askToPublishConfigFile();

        $invitation = app(Invitation::class)->generate();

        $this->displayQRCode($url, $invitation, $qrCodeGenerator);
    }

    private function confirmHttps($url)
    {
        if (Str::startsWith(strtolower($url), 'https://')) {
            return true;
        } else {
            $answer = $this->confirm("Are you connecting over a local network?");
            if ($answer) {
                return true;
            } else {
                $this->error("Apprentice only works over https");
                $this->error("Update APP_URL to begin with https://");
                return false;
            }
        }
    }

    private function askToRunMigrations()
    {
        // check if migrations already executed
        try {
            $migrationsExecuted = DB::table('migrations')->where('migration', '2020_06_1_000001_create_apprentice_users_table')->exists();
            if ($migrationsExecuted) {
                return true;
            }
        } catch (Exception $e) {
            $this->warn($e);
            $this->info("");
            $this->info("Something went wrong checking the database. See the exception above.");
            return false;
        }

        $answer = $this->confirm("Execute apprentice migrations?");
        if ($answer) {
            $this->callSilent('vendor:publish', ['--tag' => 'apprentice-migrations']);
            $this->call('migrate', array('--path' => 'vendor/voronoi/apprentice/src/database/migrations', '--force' => true));
            $this->info('Database migrations executed added successfully.');
        } else {
            $this->error("Database migrations need to run before you can setup your first project.");
            $this->info("You can run them manually using");
            $this->info("php artisan vendor:publish --tag=apprentice-migrations");
            $this->info("php artisan migrate");
            return false;
        }
    }

    private function askToPublishConfigFile()
    {
        // Don't ask twice
        $askedBefore = Invitation::count() > 0 || User::count() > 0;
        if ($askedBefore) {
            return true;
        }

        $this->info("You're using the default configuration");
        $answer = $this->confirm("Publish the apprentice config file?");
        if ($answer) {
            $this->callSilent('vendor:publish', ['--tag' => 'apprentice']);
            $this->info('config/apprentice.php added successfully.');
        } else {
            $this->info("If you change your mind later you can always manually publish the config file with");
            $this->info("php artisan vendor:publish --tag=apprentice");
        }
        return true;
    }

    private function displayQRCode($url, $invitation, $qrCodeGenerator)
    {
        $friendlyName = rawurlencode($this->getFriendlyName());
        $message = "$url?t=$invitation->token&n=$friendlyName";

        $qrcode = $qrCodeGenerator->terminal($message);

        $this->info($qrcode);
        $this->info("Scan the QR code in the Apprentice app");
    }

    private function getFriendlyName()
    {
        $env = env('APP_ENV', '');
        if ($env == 'production') {
            $env = '';
        }

        $name = env('APP_NAME', '');
        if (!empty($name) && !empty($env)) {
            return "$name - $env";
        } elseif (!empty($name)) {
            return $name;
        } else {
            return 'My Project';
        }
    }
}
