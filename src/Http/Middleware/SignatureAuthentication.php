<?php

namespace Voronoi\Apprentice\Http\Middleware;

use Illuminate\Support\Str;
use Voronoi\Apprentice\Models\User;
use Voronoi\Apprentice\Session;
use Closure;
use Voronoi\Apprentice\Libraries\HTTPSignature\HTTPSignature;
use Voronoi\Apprentice\Libraries\HTTPSignature\Exception as HTTPSignatureException;
use Exception;

/*
 |--------------------------------------------------------------------------
 | HTTP Signature Authentication Middleware
 |--------------------------------------------------------------------------
 |
 | Authenticates an Apprentice user using the HTTP Signature header.
 |
 | The keyId property identifies a user and matching public key in
 | the apprentice_users table. The connecting client must sign
 | the signature using the private key to validate. See
 | the HTTP Signature spec for more information.
 |
 | https://tools.ietf.org/html/draft-ietf-httpbis-message-signatures-00
 */
class SignatureAuthentication
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $signature = $this->parseSignature($request);
        $publicKey = $this->fetchPublicKey($request, $signature->getKeyId());

        if (!$this->verifySignature($signature, $publicKey, $request)) {
            return response()->json('Signature does not match', 403);
        }

        $user = $this->fetchUser($signature->getKeyId());
        $this->session->setUser($user);

        return $next($request);
    }

    /**
     * Extracts the Signature header
     * @param  \Illuminate\Http\Request  $request
     * @return \Voronoi\Apprentice\Libraries\HTTPSignature\HTTPSignature
     */
    private function parseSignature($request)
    {
        $rawSignature = $request->header('Signature');
        if (is_null($rawSignature)) {
            abort(response()->json('Missing signature header', 422));
        }
        return app()->makeWith(HTTPSignature::class, ['signatureHeader' => $rawSignature]);
    }

    /**
     * Retrieves the public key for a given Key ID
     * @param  \Illuminate\Http\Request  $request
     * @param  String $keyId The key identifier of the signature
     * @return String        PEM formatted public key
     */
    private function fetchPublicKey($request, $keyId)
    {
        $path = "/" . trim($request->path());
        if ($path == config('apprentice.path') . "/accept-invitation") {
            // Validate against the provided public key when accepting invitations
            $publicKey = $request->get('publicKey');
            if (empty($publicKey)) {
                abort(response()->json('Missing parameter publicKey', 422));
            }
            return $publicKey;
        } else {
            $user = $this->fetchUser($keyId);
            if (is_null($user)) {
                abort(response()->json('User not found', 403));
            }

            return $user->publicKey;
        }
    }

    /**
     * Verify the HTTP Signature was created by an Apprentice user
     * @param  \Voronoi\Apprentice\Libraries\HTTPSignature\HTTPSignature $signature
     * @param  String|null $publicKey The public key to use when authenticating. If null, defaults to the key specified by keyId in apprentice_users
     * @param  \Illuminate\Http\Request  $request
     * @return Bool          Returns true if the signature was signed
     */
    private function verifySignature($signature, $publicKey, $request)
    {
        try {
            $result = $signature->verify($publicKey, $request);
        } catch (HTTPSignatureException $error) {
            abort(response()->json($error->getMessage(), $error->getCode()));
        } catch (Exception $error) {
            abort(response()->json('Unknown error', 500));
        }
        return $result;
    }

    /**
     * Fetches the matching user
     * @param  String $keyId The key identifier of the signature
     * @return \Voronoi\Apprentice\Models\User
     */
    private function fetchUser($keyId)
    {
        return User::where('keyId', $keyId)->first();
    }
}
