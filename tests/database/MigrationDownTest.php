<?php

namespace Tests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Schema;
use CreateApprenticeUsersTable;
use CreateApprenticeInvitationsTable;

class MigrationDownTest extends TestCase
{
    use RefreshDatabase;

    public function testApprenticeUsersDown()
    {
        $this->assertTrue(Schema::hasTable('apprentice_users'));
        (new CreateApprenticeUsersTable)->down();
        $this->assertFalse(Schema::hasTable('apprentice_users'));
    }

    public function testApprenticeInvitationssDown()
    {
        $this->assertTrue(Schema::hasTable('apprentice_invitations'));
        (new CreateApprenticeInvitationsTable)->down();
        $this->assertFalse(Schema::hasTable('apprentice_invitations'));
    }
}
