<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Console\Commands\Setup;
use Mockery;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Models\Invitation;
use DB;
use Exception;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function testMigrationDBException()
    {
        DB::shouldReceive('table')
            ->andThrow(new Exception("An error"));
            
        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Are you connecting over a local network?', 'yes')
            ->expectsOutput('Something went wrong checking the database. See the exception above.');
    }

    public function testInvalidBaseURL()
    {
        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Are you connecting over a local network?', 'no')
             ->expectsOutput('Apprentice only works over https');
    }
    
    public function testExecuteMigrations()
    {
        DB::table('migrations')->truncate();
                
        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Are you connecting over a local network?', 'yes')
            ->expectsConfirmation('Execute apprentice migrations?', 'yes')
            ->expectsOutput('Database migrations executed added successfully.')
            ->assertExitCode(0);
    }

    public function testNoToRunMigrations()
    {
        DB::table('migrations')->truncate();

        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Are you connecting over a local network?', 'yes')
            ->expectsConfirmation('Execute apprentice migrations?', 'no')
            ->expectsOutput('Database migrations need to run before you can setup your first project.')
            ->assertExitCode(0);
    }
    
    public function testAlreadyPublishedConfigFile()
    {
        $invitation = new Invitation;
        $invitation->token = '123';
        $invitation->save();
        
        $this->mockURL();
        $this->mockInvitationGenerator('123');
        $this->mockQRCode('123', 'My%20Project', 'qr-code-placeholder');

        $this->artisan('apprentice:setup')
          ->expectsOutput('qr-code-placeholder');
    }
    
    public function testPublishConfig()
    {
        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Are you connecting over a local network?', 'yes')
            ->expectsConfirmation('Publish the apprentice config file?', 'yes')
            ->expectsOutput('config/apprentice.php added successfully.');
    }

    public function testCreatesQRCode()
    {
        $this->mockURL();
        $this->mockInvitationGenerator('123');
        $this->mockQRCode('123', 'My%20Project', 'qr-code-placeholder');

        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Publish the apprentice config file?', 'no')
          ->expectsOutput('qr-code-placeholder');
    }

    public function testUsesAppNameForQRCode()
    {
        putenv("APP_NAME=project_name");

        $this->mockURL();
        $this->mockInvitationGenerator('123');
        $this->mockQRCode('123', 'project_name');

        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Publish the apprentice config file?', 'no');
    }

    public function testUsesAppEnvInQRCode()
    {
        putenv("APP_NAME=project_name");
        putenv("APP_ENV=dev");

        $this->mockURL();
        $this->mockInvitationGenerator('123');
        $this->mockQRCode('123', 'project_name%20-%20dev');

        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Publish the apprentice config file?', 'no');
    }

    public function testDoesNotUseProductionEnvInQRCode()
    {
        putenv("APP_NAME=project_name");
        putenv("APP_ENV=production");

        $this->mockURL();
        $this->mockInvitationGenerator('123');
        $this->mockQRCode('123', 'project_name');

        $this->artisan('apprentice:setup')
            ->expectsConfirmation('Publish the apprentice config file?', 'no');
    }
    
    private function mockURL()
    {
        $url = Mockery::mock(UrlGenerator::class);
        $url->shouldReceive("to")->andReturn('https://localhost/apprentice');
        app()->instance('Illuminate\Routing\UrlGenerator', $url);
    }

    private function mockInvitationGenerator($token = '123')
    {
        $fakeInvitation = new Invitation;
        $fakeInvitation->token = $token;
        $staticInvitation = Mockery::mock('Voronoi\Apprentice\Models\Invitation');
        $staticInvitation->shouldReceive('generate')->andReturn($fakeInvitation);
        app()->instance('Voronoi\Apprentice\Models\Invitation', $staticInvitation);
    }

    private function mockQRCode($token = '123', $name = 'My%20Project', $qrcode = 'qr-code-placeholder')
    {
        $qrCode = Mockery::mock('Voronoi\Apprentice\Libraries\QRCode');
        $qrCode->shouldReceive('terminal')
        ->with("https://localhost/apprentice?t=$token&n=$name")
        ->andReturn($qrcode);
        app()->instance('Voronoi\Apprentice\Libraries\QRCode', $qrCode);
    }

    // public function testInstallKey()
    // {
    //     $this->artisan('apprentice:setup')
    //          ->expectsQuestion('Install this SSH key?', 'y')
    //          ->expectsOutput('Public key installed')
    //          ->assertExitCode(0);
    // }
}
