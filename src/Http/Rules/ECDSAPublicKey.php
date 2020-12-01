<?php

namespace Voronoi\Apprentice\Http\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class ECDSAPublicKey implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $trimmed = trim($value);
        if (!Str::startsWith(trim($trimmed), "-----BEGIN PUBLIC KEY-----")) {
            return false;
        }

        if (!Str::endsWith(trim($trimmed), "-----END PUBLIC KEY-----")) {
            return false;
        }

        $base64Value = implode("", array_slice(explode("\n", $trimmed), 1, -1));

        // check base64 encoded
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64Value)) {
            return false;
        }

        $key_data = base64_decode($base64Value);
        $ecdsaHeader = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
        if ($ecdsaHeader != mb_substr($key_data, 0, 24)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid ECDSA PEM formatted key.';
    }
}
