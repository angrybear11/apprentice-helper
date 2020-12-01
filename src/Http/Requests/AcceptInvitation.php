<?php

namespace Voronoi\Apprentice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Voronoi\Apprentice\Http\Rules\ECDSAPublicKey;

class AcceptInvitation extends FormRequest
{
    public function rules()
    {
        return [
            'keyId'        => "required|string|max:255",
            'token'        => "required|string|max:255",
            'friendlyName' => "required|string|max:255",
            'publicKey'    => ["required", "string", "max:1024", new ECDSAPublicKey],
        ];
    }
}
