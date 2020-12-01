<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Models\User;
use Voronoi\Apprentice\Http\Middleware\SignatureAuthentication;
use Voronoi\Apprentice\Models\Invitation;
use Voronoi\Apprentice\Session;
use Voronoi\Apprentice\CommandExecutors\SSE as SSECommandExecutor;
use Carbon\Carbon;
use Mockery;

class SSECommandControllerTest extends TestCase
{
    use RefreshDatabase;


    /*
    |--------------------------------------------------------------------------
    | Execute Route
    |--------------------------------------------------------------------------
    |
    | Tests the /apprentice/execute route
    |
    |
    |
    */

    public function testExecuteCommand()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $commandMock = Mockery::mock(SSECommandExecutor::class);
        $commandMock->shouldReceive('execute')->once();
        app()->instance(SSECommandExecutor::class, $commandMock);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(1);
        app()->instance(Session::class, $sessionMock);

        $response = $this->postJson('/apprentice/execute', [
          'command' => 'list',
        ]);

        $response
          ->assertStatus(200)
          ->assertSee('success');
    }

    public function testExecuteNoUser()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(null);
        app()->instance(Session::class, $sessionMock);

        $response = $this->postJson('/apprentice/execute', [
          'command' => 'list',
        ]);

        $response
          ->assertStatus(422)
          ->assertSee('no user');
    }

    public function testExecuteUsesSignatureAuthenticationMiddleware()
    {
        $signatureMock = Mockery::mock(SignatureAuthentication::class);
        $signatureMock->shouldReceive('handle')->once()
          ->andReturnUsing(function ($request, Closure $next) {
              return $next($request);
          });
        app()->instance(SignatureAuthentication::class, $signatureMock);

        $this->postJson('/apprentice/execute', [
          'command' => 'list',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Input Route
    |--------------------------------------------------------------------------
    |
    | Tests the /apprentice/input route
    |
    |
    |
    */

    public function testInput()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $commandExecutor = Mockery::mock(SSECommandExecutor::class);
        $commandExecutor->shouldReceive('commandInput')->with("some data", 1)->once();
        app()->instance(SSECommandExecutor::class, $commandExecutor);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(1);
        app()->instance(Session::class, $sessionMock);

        $response = $this->postJson('/apprentice/input', [
          'data' => 'some data',
        ]);

        $response
          ->assertStatus(200)
          ->assertSee('success');
    }

    public function testInputNoUser()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(null);
        app()->instance(Session::class, $sessionMock);

        $response = $this->postJson('/apprentice/input', [
          'data' => 'some data',
        ]);

        $response
          ->assertStatus(422)
          ->assertSee('no user');
    }

    public function testInputUsesSignatureAuthenticationMiddleware()
    {
        $signatureMock = Mockery::mock(SignatureAuthentication::class);
        $signatureMock->shouldReceive('handle')->once()
          ->andReturnUsing(function ($request, Closure $next) {
              return $next($request);
          });
        app()->instance(SignatureAuthentication::class, $signatureMock);

        $this->postJson('/apprentice/input', [
          'data' => '1',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Output Route
    |--------------------------------------------------------------------------
    |
    | Tests the /apprentice/output route
    |
    |
    |
    */

    public function testOutput()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $commandExecutor = Mockery::mock(SSECommandExecutor::class);
        $commandExecutor->shouldReceive('outputResponse')->with(1)->once();
        app()->instance(SSECommandExecutor::class, $commandExecutor);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(1);
        app()->instance(Session::class, $sessionMock);

        $response = $this->getJson('/apprentice/output', []);

        $response
          ->assertStatus(200);
    }

    public function testOutputNoUser()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $sessionMock = Mockery::mock(Session::class);
        $sessionMock->shouldReceive('currentUserId')->once()->andReturn(null);
        app()->instance(Session::class, $sessionMock);

        $response = $this->getJson('/apprentice/output', []);

        $response
          ->assertStatus(422)
          ->assertSee('no user');
    }

    public function testOutputUsesSignatureAuthenticationMiddleware()
    {
        $signatureMock = Mockery::mock(SignatureAuthentication::class);
        $signatureMock->shouldReceive('handle')->once()
          ->andReturnUsing(function ($request, Closure $next) {
              return $next($request);
          });
        app()->instance(SignatureAuthentication::class, $signatureMock);

        $this->getJson('/apprentice/output');
    }
}
