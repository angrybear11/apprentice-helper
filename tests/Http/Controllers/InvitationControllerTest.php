<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Models\User;
use Voronoi\Apprentice\Http\Middleware\SignatureAuthentication;
use Voronoi\Apprentice\Models\Invitation;
use Carbon\Carbon;
use Mockery;

class InvitationControllerTest extends TestCase
{
    use RefreshDatabase;

    public static $publicKey = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE5pX+Yw/8l5E+bvh3BxQxZBVtrNP+
jGOG++N1ukUFL60RTzFbV+mN/LP+jeZe8rE/cNOwQi8tFzulBEPqw94NOg==
-----END PUBLIC KEY-----";

    public function testAcceptUsesSignatureAuthenticationMiddleware()
    {
        $signatureMock = Mockery::mock(SignatureAuthentication::class);
        $signatureMock->shouldReceive('handle')->once()
          ->andReturnUsing(function ($request, Closure $next) {
              return $next($request);
          });
        app()->instance(SignatureAuthentication::class, $signatureMock);

        $response = $this->postJson('/apprentice/accept-invitation', [
        'keyId'     => '1',
        'token'     => '123',
        'publicKey' => InvitationControllerTest::$publicKey,
      ]);
    }

    public function testExistingUserReportsAlreadyAUser()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $user = new User;
        $user->keyId = '1';
        $user->publicKey = 'a-public-key';
        $user->friendlyName = 'unit test';
        $user->save();

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => '1',
          'token'        => '123',
          'friendlyName' => "unit test",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $response
          ->assertStatus(200)
          ->assertSee('Already a user');
    }

    public function testExistingUserDeletesInvitation()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $user = new User;
        $user->keyId = '1';
        $user->publicKey = InvitationControllerTest::$publicKey;
        $user->friendlyName = 'unit test';
        $user->save();

        $invitation = new Invitation;
        $invitation->token = '123';
        $invitation->save();

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => '1',
          'token'        => '123',
          'friendlyName' => "unit test",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $deletedInvitation = Invitation::find($invitation->id);
        $this->assertNull($deletedInvitation);
    }

    public function testInvitationNotFound()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $invitation = new Invitation;
        $invitation->created_at = Carbon::now()->subDays(1)->subMinutes(1);
        $invitation->token = '123';
        $invitation->save();

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => '1',
          'token'        => '123',
          'friendlyName' => "unit test",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $response
          ->assertStatus(403)
          ->assertSee('Invitation has expired');
    }

    public function testExpiredInvitation()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => '1',
          'token'        => '123',
          'friendlyName' => "unit test",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $response
          ->assertStatus(403)
          ->assertSee('No invitation found');
    }

    public function testConsumeInvitation()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $invitation = new Invitation;
        $invitation->created_at = Carbon::now();
        $invitation->token = '123';
        $invitation->save();

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => 'a-unique-key',
          'token'        => '123',
          'friendlyName' => "unit test",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        // Ensure invitation was deleted
        $deletedInvitation = Invitation::find($invitation->id);
        $this->assertNull($deletedInvitation);

        // Check new user
        $newestUser = User::orderby('id', 'desc')->first();
        $this->assertEquals('a-unique-key', $newestUser->keyId);
        $this->assertEquals(InvitationControllerTest::$publicKey, $newestUser->publicKey);

        $response
          ->assertStatus(201)
          ->assertSee('Created Successfully');
    }

    public function testDuplicateFriendlyNameAddsNumbers()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $invitation = new Invitation;
        $invitation->created_at = Carbon::now();
        $invitation->token = '123';
        $invitation->save();

        $existingUser = new User;
        $existingUser->keyId        = "1";
        $existingUser->friendlyName = "Picasso";
        $existingUser->publicKey    = "1";
        $existingUser->save();

        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => 'a-unique-key',
          'token'        => '123',
          'friendlyName' => "Picasso",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $newestUser = User::orderby('id', 'desc')->first();
        $this->assertEquals('Picasso (1)', $newestUser->friendlyName);

        $response
          ->assertStatus(201)
          ->assertSee('Created Successfully');

        // Check a second user
        $invitation = new Invitation;
        $invitation->created_at = Carbon::now();
        $invitation->token = '1234';
        $invitation->save();

        $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => 'another-unique-key',
          'token'        => '1234',
          'friendlyName' => "Picasso",
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        // Check new user
        $newestUser = User::orderby('id', 'desc')->first();
        $this->assertEquals('Picasso (2)', $newestUser->friendlyName);
    }

    public function testDuplicateFriendlyNameOver255Characters()
    {
        $this->withoutMiddleware(SignatureAuthentication::class);

        $invitation = new Invitation;
        $invitation->created_at = Carbon::now();
        $invitation->token = '123';
        $invitation->save();

        $longFriendlyName = str_repeat("A", 255);

        $existingUser = new User;
        $existingUser->keyId        = "1";
        $existingUser->friendlyName = $longFriendlyName;
        $existingUser->publicKey    = "1";
        $existingUser->save();



        $response = $this->postJson('/apprentice/accept-invitation', [
          'keyId'        => 'a-unique-key',
          'token'        => '123',
          'friendlyName' => $longFriendlyName,
          'publicKey'    => InvitationControllerTest::$publicKey,
        ]);

        $newestUser = User::orderby('id', 'desc')->first();
        $expectedName = str_repeat("A", 251) . " (1)";
        $this->assertEquals($expectedName, $newestUser->friendlyName);

        $response
          ->assertStatus(201)
          ->assertSee('Created Successfully');
    }
}
