<?php

namespace Voronoi\Apprentice\Libraries\HTTPSignature;

use Voronoi\Apprentice\Libraries\HTTPSignature\Exception as HTTPSignatureException;
use Exception;

/*
 |--------------------------------------------------------------------------
 | HTTP Signature
 |--------------------------------------------------------------------------
 |
 | Implements https://tools.ietf.org/html/draft-ietf-httpbis-message-signatures-00
 */
class HTTPSignature
{
    protected $signatureHeader;

    /**
     * Create a new HTTP Signature
     * @param String $signatureHeader The raw signature from the header
     */
    public function __construct($signatureHeader)
    {
        if (empty($signatureHeader)) {
            throw new HTTPSignatureException("Signature header cannot be empty", 422);
        }

        $this->signatureHeader = trim($signatureHeader);
    }

    /**
     * Get the Key ID specified by the signature
     * @return String The key ID. This method will throw an exception if the key ID is not found.
     */
    public function getKeyId()
    {
        $signatureKeys = $this->getKeys();
        if (!array_key_exists("keyId", $signatureKeys)) {
            throw new HTTPSignatureException("KeyID not found", 404);
        } else {
            return $signatureKeys["keyId"];
        }
    }

    /**
     * Verify the signature matches against a public key and the request method, URI, and headers
     * @param  String $publicKey ECDSA public key
     * @param  \Illuminate\Http\Request $request The incoming request
     * @return Bool True if the key verifies successfully, false or throws an error otherwise
     */
    public function verify($publicKey, $request)
    {
        $signatureKeys = $this->getKeys();

        $requiredKeys = ["keyId", "algorithm", "created", "headers", "signature"];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $signatureKeys)) {
                throw new HTTPSignatureException("Signature header key $key is required", 422);
            }
            if (empty($signatureKeys[$key]) && $key != "headers") {
                throw new HTTPSignatureException("The key $key cannot be empty", 422);
            }
        }

        if ($signatureKeys['algorithm'] !== 'hs2019') {
            throw new HTTPSignatureException("Only the algorithm hs2019 is supported", 422);
        }

        // Check signature
        $message   = $this->buildMessage($signatureKeys['headers'], $signatureKeys['created'], $request);
        $signature = base64_decode($signatureKeys['signature']);

        return $this->verifyECDSASHA512($message, $signature, $publicKey);
    }

    /**
     * Get all keys in the signature
     * @return Array<Mixed> The keys in the signature
     */
    public function getKeys()
    {
        $parts = explode(', ', $this->signatureHeader);
        $keys = [];
        foreach ($parts as $part) {
            $signatureParts = explode('=', $part, 2);
            if (count($signatureParts) != 2) {
                throw new HTTPSignatureException("Incorrect format for signature key. There should be one equals: $part", 422);
            }
            [$name, $rawValue] = $signatureParts;
            $value = trim($rawValue, '"');
            if ($name === 'created') {
                $value = (int) $rawValue;
            } elseif ($name === 'expired') {
                $value = (Double) $rawValue;
            }
            $keys[$name] = $value;
        }
        return $keys;
    }

    /**
     * Creates the same message used in the original signature per the HTTP Signature specification.
     * @param  Array<String> $headers The headers to use when building the message
     * @param  Int $created Epoch timestamp
     * @param  \Illuminate\Http\Request $request The original request
     * @return String          The HTTP Signature message
     */
    private function buildMessage($headers, $created, $request)
    {
        $result = "";
        if (empty($headers)) {
            $headers = ['(created)'];
        } else {
            $headers = explode(' ', $headers);
        }
        foreach ($headers as $header) {
            switch ($header) {
              case '(request-target)':
                $value = strtolower($request->method()) . " " . $request->getRequestUri();
                $result .= "$header: $value\n";
                break;
              case '(created)':
                $result .= "$header: $created\n";
                break;
              default:
                $value = $request->header($header);
                if (is_null($value)) {
                    throw new HTTPSignatureException("Required header $header is null", 422);
                }
                $result .= "$header: $value\n";
                break;
            }
        }

        return $result;
    }

    /**
     * Verify a ECDSA SHA512 signature
     * @param  String $message   The message to verify
     * @param  String $signature The message signed by the client using the private key
     * @param  String $publicKey Public key
     * @return Bool True if the signature is valid, false or throws an error otherwise.
     */
    private function verifyECDSASHA512($message, $signature, $publicKey)
    {
        if (empty($message) || empty($signature) || empty($publicKey)) {
            return false;
        }

        // Verify the public key PEM header matches ECDSA prime256v1
        $key_data = base64_decode(implode("", array_slice(explode("\n", trim($publicKey)), 1, -1)));
        $ecdsaHeader = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
        if ($ecdsaHeader != mb_substr($key_data, 0, 24)) {
            throw new HTTPSignatureException("Public key is not a supported ECDSA key", 500);
        }

        try {
            $result = openssl_verify($message, $signature, $publicKey, 'sha512');
            if ($result === -1) {
                throw new HTTPSignatureException("Failed to validate signature", 500);
            }
            return $result === 1;
        } catch (Exception $error) {
            throw new HTTPSignatureException("Signature validation failed", 500);
        }
    }
}
