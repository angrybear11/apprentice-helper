<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voronoi\Apprentice\Models\Invitation;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function testGenerateCreatesAnInvitation()
    {
        $countBefore = Invitation::count();

        Invitation::generate();

        $countAfter = Invitation::count();

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    public function testGenerateCreatesAToken()
    {
        $invitation = Invitation::generate();
        $tokenLength = strlen($invitation->token);
        $this->assertTrue($tokenLength == 32);
    }
}
