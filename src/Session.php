<?php

namespace Voronoi\Apprentice;
use Voronoi\Apprentice\Libraries\Socket\Writer;

class Session
{
    protected $currentUser;

    public function runningViaApprentice()
    {
        return $this->currentUserID() != null;
    }

    public function getOutputMessenger()
    {
        $id = $this->currentUserID();
        if (is_null($id)) {
            return null;
        }
        $writer = app()->makeWith(Writer::class, ['type' => 'command', 'id' => $id]);
        return app()->makeWith(Messenger::class, ['writer' => $writer]);
    }

    public function setUser($user)
    {
        $this->currentUser = $user;
    }

    public function currentUserID()
    {
        if (is_null($this->currentUser)) {
            return null;
        } else {
            return $this->currentUser->id;
        }
    }
}
