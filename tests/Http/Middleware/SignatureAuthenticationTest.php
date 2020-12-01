<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Http\Middleware\SignatureAuthentication;
use Voronoi\Apprentice\Session;
use Illuminate\Http\Request;
use Voronoi\Apprentice\Libraries\HTTPSignature\HTTPSignature;
use Voronoi\Apprentice\Libraries\HTTPSignature\Exception as HTTPSignatureException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Testing\TestResponse;
use Voronoi\Apprentice\Models\User;
use Symfony\Component\HttpFoundation\ServerBag;
use Mockery;
use Exception;

class SignatureAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function testRequiresSignatureHeader()
    {
        $request = new Request;

        $response = $this->execute($request);

        $response->assertStatus(422)
          ->assertSee("Missing signature header");
    }

    public function testUserNotFound()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request);

        $response->assertStatus(403)
          ->assertSee("User not found");
    }

    public function testVerifySignatureCustomError()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');

        $user = new User;
        $user->keyId = 1;
        $user->publicKey = 'public-key';
        $user->friendlyName = 'unit test';
        $user->save();

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        $httpSignatureMock->shouldReceive('verify')
          ->with('public-key', $request)
          ->andThrow(new HTTPSignatureException("Incorrect format for signature key", 422));
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request);

        $response->assertStatus(422)
          ->assertSee("Incorrect format for signature key");
    }

    public function testVerifySignatureUnknownError()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');

        $user = new User;
        $user->keyId = 1;
        $user->publicKey = 'public-key';
        $user->friendlyName = 'unit test';
        $user->save();

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        $httpSignatureMock->shouldReceive('verify')
          ->with('public-key', $request)
          ->andThrow(new Exception("Something else happened", 422));
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request);

        $response->assertStatus(500)
          ->assertSee("Unknown error");
    }

    public function testInvalidSignature()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');

        $user = new User;
        $user->keyId = 1;
        $user->publicKey = 'public-key';
        $user->friendlyName = 'unit test';
        $user->save();

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        $httpSignatureMock->shouldReceive('verify')
          ->with('public-key', $request)
          ->andReturn(false);
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request);

        $response->assertStatus(403)
          ->assertSee("Signature does not match");
    }

    public function testValidSignatureSavesUserAndContinues()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');

        $user = new User;
        $user->keyId = 1;
        $user->publicKey = 'public-key';
        $user->friendlyName = 'unit test';
        $user->save();

        $sessionMock = $this->mock(Session::class);
        $sessionMock->shouldReceive('setUser')->with(Mockery::on(function ($user) {
            return $user->keyId == 1 && $user->publicKey == 'public-key';
        }))->once();

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        $httpSignatureMock->shouldReceive('verify')
          ->with('public-key', $request)
          ->andReturn(true);
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request, function ($r) {
            return response()->json('next middleware');
        }, $sessionMock);

        $response->assertStatus(200)
          ->assertSee("next middleware", 'Expected the middleware to call the next request');
    }

    public function testAcceptInvitationUsesPublicKeyFromRequest()
    {
        $request = new Request;
        $request->replace(['publicKey' => "actual-key-used"]);
        $request->headers->set('Signature', '123');
        $request->server = new ServerBag(['REQUEST_URI' => '/apprentice/accept-invitation']);

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        $httpSignatureMock->shouldReceive('verify')
          ->with('actual-key-used', $request)
          ->andReturn(true)
          ->once();
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $this->execute($request);
    }

    public function testAcceptInvitationUsesPublicKeyRequiresPublicKey()
    {
        $request = new Request;
        $request->headers->set('Signature', '123');
        $request->server = new ServerBag(['REQUEST_URI' => '/apprentice/accept-invitation']);

        $httpSignatureMock = $this->mock(HTTPSignature::class);
        $httpSignatureMock->shouldReceive('getKeyId')->andReturn(1);
        app()->offsetSet(HTTPSignature::class, $httpSignatureMock);

        $response = $this->execute($request);

        $response->assertStatus(422)
          ->assertSee("Missing parameter publicKey");
    }

    private function execute($request, $next = null, $session = null)
    {
        try {
            $middleware = new SignatureAuthentication($session ?? new Session);
            $next = $next ?? function ($request) {
            };
            $response = $middleware->handle($request, $next);
            return new TestResponse($response);
        } catch (HttpResponseException $e) {
            return new TestResponse($e->getResponse());
        }
    }
}
