<?php

namespace Voronoi\Apprentice\Http\Controllers;

use Voronoi\Apprentice\Storage;
use Illuminate\Routing\ViewController;
use Illuminate\Http\Request;
use Voronoi\Apprentice\Http\Requests\AcceptInvitation as AcceptInvitationRequest;
use Voronoi\Apprentice\Models\User;
use Voronoi\Apprentice\Models\Invitation;
use Carbon\Carbon;
use DB;

class InvitationController extends ViewController
{
    public function accept(AcceptInvitationRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $token = $request->get('token');

            $invitation = Invitation::where('token', $token)->first();

            if (User::where('keyId', $request->get('keyId'))->exists()) {
                if (!is_null($invitation)) {
                    $invitation->delete();
                }
                return response()->json('Already a user', 200);
            }

            if (is_null($invitation)) {
                return response()->json('No invitation found', 403);
            }

            // Check Expiration
            $now = Carbon::now()->timestamp;
            $expiration = (Int) config('apprentice.invitation_expiration', 600);
            if ($now > ($invitation->created_at->timestamp + $expiration)) {
                return response()->json('Invitation has expired', 403);
            }

            DB::transaction(function () use ($request, $invitation) {
                $user = new User;
                $user->keyId        = $request->get('keyId');
                $user->friendlyName = $request->get('friendlyName');
                $user->publicKey    = $request->get('publicKey');
                $user->save();
                $invitation->delete();
            });

            return response()->json('Created Successfully', 201);
        });
    }
}
