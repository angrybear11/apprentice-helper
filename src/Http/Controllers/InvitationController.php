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

            // Get a unique friendly name. If there's a duplicate add a number.
            $name = $request->get('friendlyName');
            $count = 0;
            while (User::where('friendlyName', $name)->exists()) {
                $count += 1;
                $uniqueExtension = " ($count)";
                // Build `friendlyName (num)` string but restrict to
                // 255 characters by shortening friendlyName if necessary
                $maxLength = 255 - strlen($uniqueExtension);
                $baseName = substr($request->get('friendlyName'), 0, $maxLength);
                $name = $baseName . $uniqueExtension;
            }

            DB::transaction(function () use ($request, $invitation, $name) {
                $user = new User;
                $user->keyId        = $request->get('keyId');
                $user->friendlyName = $name;
                $user->publicKey    = $request->get('publicKey');
                $user->save();
                $invitation->delete();
            });

            return response()->json('Created Successfully', 201);
        });
    }
}
