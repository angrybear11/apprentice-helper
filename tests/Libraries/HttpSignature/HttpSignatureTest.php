<?php

namespace Tests;

use Tests\TestCase;
use Voronoi\Apprentice\Libraries\HTTPSignature\HTTPSignature;
use Voronoi\Apprentice\Libraries\HTTPSignature\Exception as HTTPSignatureException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ServerBag;

class HTTPSignatureTest extends TestCase
{

    /*
    |--------------------------------------------------------------------------
    | Formatting
    |--------------------------------------------------------------------------
    |
    | Tests the invalid formats and processing of the raw signature
    |
    |
    |
    */

    public function testEmptySignatureThrowsException()
    {
        try {
            new HTTPSignature("");
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(422, $exception->getCode());
            $this->assertEquals("Signature header cannot be empty", $exception->getMessage());
        }
    }

    public function testInvalidSignatureFormat()
    {
        try {
            $signature = new HTTPSignature("a");
            $signature->getKeyId();
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(422, $exception->getCode());
            $this->assertEquals("Incorrect format for signature key. There should be one equals: a", $exception->getMessage());
        }
    }

    public function testCreatedConvertedToInt()
    {
        $signature = new HTTPSignature("created=123");
        $keys = $signature->getKeys();
        $this->assertTrue(is_int($keys['created']));
        $this->assertEquals($keys['created'], 123);
    }

    public function testExpiredConvertedToDouble()
    {
        $signature = new HTTPSignature("expired=123.4");
        $keys = $signature->getKeys();
        $this->assertTrue(is_double($keys['expired']));
        $this->assertEquals($keys['expired'], 123.4);
    }

    public function testTrimsQuotesFromStringValue()
    {
        $signature = new HTTPSignature("myKey=\"myValue\"");
        $keys = $signature->getKeys();
        $this->assertEquals($keys['myKey'], "myValue");
    }


    /*
    |--------------------------------------------------------------------------
    | getKeyId
    |--------------------------------------------------------------------------
    |
    | Tests the getKeyId method
    |
    |
    |
    */

    public function testMissingKeyIdThrowsException()
    {
        try {
            $signature = new HTTPSignature("created=123");
            $signature->getKeyId();
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(404, $exception->getCode());
            $this->assertEquals("KeyID not found", $exception->getMessage());
        }
    }

    public function testExtractKeyID()
    {
        $signature = new HTTPSignature("keyId=\"123\"");
        $keyId = $signature->getKeyId();
        $this->assertEquals('123', $keyId);
    }


    /*
    |--------------------------------------------------------------------------
    | Verify
    |--------------------------------------------------------------------------
    |
    | Tests verifying the signature
    |
    |
    |
    */

    public function testVerifyRequiredKeys()
    {
        $assertKeyIsRequired = function ($expectedKey, $keyAndValues) {
            // assert key exists
            try {
                $rawSignature = implode(", ", $keyAndValues);
                $signature = new HTTPSignature($rawSignature);
                $signature->verify('', null);
                $this->assertFalse(true, 'Should not execute');
            } catch (HTTPSignatureException $exception) {
                $this->assertEquals(422, $exception->getCode());
                $this->assertEquals("Signature header key $expectedKey is required", $exception->getMessage());
            }

            // assert key value is not empty except for headers
            if ($expectedKey == "headers") {
                return;
            }
            try {
                $rawSignature = implode(", ", $keyAndValues);
                $signature = new HTTPSignature($rawSignature . ", $expectedKey=\"\"");
                $signature->verify('', null);
                $this->assertFalse(true, 'Should not execute');
            } catch (HTTPSignatureException $exception) {
                $this->assertEquals(422, $exception->getCode());
                $this->assertEquals("The key $expectedKey cannot be empty", $exception->getMessage());
            }
        };

        $assertKeyIsRequired('keyId', ['a=b']);
        $assertKeyIsRequired('algorithm', ['keyId=1']);
        $assertKeyIsRequired('created', ['keyId=1', 'algorithm=1']);
        $assertKeyIsRequired('headers', ['keyId=1', 'algorithm=1', 'created=1']);
        $assertKeyIsRequired('signature', ['keyId=1', 'algorithm=1', 'created=1', 'headers=1']);
    }

    public function testVerifyAlgorithmIsHS2019()
    {
        try {
            $rawSignature = 'keyId=1, algorithm="something-unexpected", created=1, headers=[], signature="ABC"';
            $signature = new HTTPSignature($rawSignature);
            $signature->verify('', null);
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(422, $exception->getCode());
            $this->assertEquals("Only the algorithm hs2019 is supported", $exception->getMessage());
        }
    }

    public function testVerifyEmptyHeadersUsesCreated()
    {
        $message = "(created): 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        $result = $signature->verify($publicKey, null);

        $this->assertTrue($result);
    }

    public function testVerifyRequestTargetHeader()
    {
        $message = "(request-target): get /apprentice/test\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="(request-target)", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        $request = new Request;
        $request->server = new ServerBag(['REQUEST_URI' => '/apprentice/test', 'REQUEST_METHOD' => 'GET']);

        $result = $signature->verify($publicKey, $request);

        $this->assertTrue($result);
    }

    public function testVerifyCreatedHeader()
    {
        $message = "(created): 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="(created)", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        $result = $signature->verify($publicKey, null);

        $this->assertTrue($result);
    }

    public function testVerifyHeaderNotFound()
    {
        $message = "missingHeader: 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="missingHeader", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        try {
            $result = $signature->verify($publicKey, new Request);
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(422, $exception->getCode());
            $this->assertEquals("Required header missingHeader is null", $exception->getMessage());
        }
    }

    public function testVerifyOtherHeader()
    {
        $message = "hello: world\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="hello", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        $request = new Request;
        $request->headers->set('hello', 'world');

        $result = $signature->verify($publicKey, $request);

        $this->assertTrue($result);
    }

    public function testVerifyEmptySignature()
    {
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="", signature=" "';
        $signature = new HTTPSignature($rawSignature);

        $result = $signature->verify($publicKey, null);
        $this->assertFalse($result);
    }

    public function testVerifyEmptyPublicKey()
    {
        $message = "(created): 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="(created)", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        $request = new Request;
        $request->headers->set('hello', 'world');

        $result = $signature->verify("", $request);

        $this->assertFalse($result);
    }

    public function testVerifyOpenSSLError()
    {
        $message = "(created): 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = file_get_contents(__DIR__."/test.public.pem");
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="(created)", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        try {
            $result = $signature->verify("-" . $publicKey, null);
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(500, $exception->getCode());
            $this->assertEquals("Signature validation failed", $exception->getMessage());
        }
    }

    public function testVerifyErrorForUnsupportedECDSAKey()
    {
        $message = "(created): 123\n";
        $privateKey = file_get_contents(__DIR__."/test.key.pem");
        $publicKey = "-----BEGIN PUBLIC KEY-----
MMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE5pX+Yw/8l5E+bvh3BxQxZBVtrNP+
jGOG++N1ukUFL60RTzFbV+mN/LP+jeZe8rE/cNOwQi8tFzulBEPqw94NOg==
-----END PUBLIC KEY-----";
        openssl_sign($message, $sign, $privateKey, 'sha512');
        $sign = base64_encode($sign);
        $rawSignature = 'keyId=1, algorithm="hs2019", created=123, headers="(created)", signature="'.$sign.'"';
        $signature = new HTTPSignature($rawSignature);

        try {
            $result = $signature->verify($publicKey, null);
            $this->assertFalse(true, 'Should not execute');
        } catch (HTTPSignatureException $exception) {
            $this->assertEquals(500, $exception->getCode());
            $this->assertEquals("Public key is not a supported ECDSA key", $exception->getMessage());
        }
    }
}
