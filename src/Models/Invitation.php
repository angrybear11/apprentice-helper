<?php

namespace Voronoi\Apprentice\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $table = 'apprentice_invitations';

    public static function generate()
    {
        $invitation = new Invitation;
        $invitation->token = bin2hex(random_bytes(16));
        $invitation->save();
        return $invitation;
    }
}
