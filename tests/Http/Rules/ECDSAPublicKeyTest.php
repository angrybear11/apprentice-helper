<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Http\Rules\ECDSAPublicKey;

class ECDSAPublicKeyTest extends TestCase
{
    public function testFailsWhenMissingPEMBegin()
    {
        $publicKey = "MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE5pX+Yw/8l5E+bvh3BxQxZBVtrNP+
jGOG++N1ukUFL60RTzFbV+mN/LP+jeZe8rE/cNOwQi8tFzulBEPqw94NOg==
-----END PUBLIC KEY-----";

        $rule = new ECDSAPublicKey;
        $result = $rule->passes('any', $publicKey);
        $this->assertFalse($result);
    }

    public function testFailsWhenMissingPEMEnd()
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE5pX+Yw/8l5E+bvh3BxQxZBVtrNP+
jGOG++N1ukUFL60RTzFbV+mN/LP+jeZe8rE/cNOwQi8tFzulBEPqw94NOg==";

        $rule = new ECDSAPublicKey;
        $result = $rule->passes('any', $publicKey);
        $this->assertFalse($result);
    }

    public function testFailsForInvalidBase64Value()
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----
$
-----END PUBLIC KEY-----";

        $rule = new ECDSAPublicKey;
        $result = $rule->passes('any', $publicKey);
        $this->assertFalse($result);
    }

    public function testFailsForNoneECDSAKey()
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----
dGVzdA==
-----END PUBLIC KEY-----";

        $rule = new ECDSAPublicKey;
        $result = $rule->passes('any', $publicKey);
        $this->assertFalse($result);
    }

    public function testPassesForValidECDSAKey()
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE5pX+Yw/8l5E+bvh3BxQxZBVtrNP+
jGOG++N1ukUFL60RTzFbV+mN/LP+jeZe8rE/cNOwQi8tFzulBEPqw94NOg==
-----END PUBLIC KEY-----";

        $rule = new ECDSAPublicKey;
        $result = $rule->passes('any', $publicKey);
        $this->assertTrue($result);
    }

    public function testMessageExists()
    {
        $rule = new ECDSAPublicKey;
        $this->assertTrue(!empty($rule->message()));
    }
}
