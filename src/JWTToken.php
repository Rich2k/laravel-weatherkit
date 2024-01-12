<?php
namespace Mobiadroit\LaravelWeatherKit;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Storage;
use Mobiadroit\LaravelWeatherKit\Exceptions\KeyDecodingException;
use Mobiadroit\LaravelWeatherKit\Exceptions\KeyFileMissingException;
use Mobiadroit\LaravelWeatherKit\Exceptions\TokenGenerationFailedException;
use Throwable;

/**
 * JWTToken
 *
 * @package Mobiadroit\LaravelWeatherKit
 */
class JWTToken
{
    /**
     * Generated JWT token
     *
     * @var string
     */
    protected string $jwtToken;

    /**
     * @param string $keyValue
     * @param string $keyId
     * @param string $teamId
     * @param string $appBundleId
     * @param int $tokenTTL
     */
    public function __construct(string $keyValue, string $keyId, string $teamId, string $bundleId, int $tokenTTL)
    {
        if (str_starts_with($keyValue, '-----BEGIN PRIVATE KEY-----')) {
            $decodedKey = $this->decodeKeyString($keyValue);
        }
        else {
            if (! Storage::exists($keyValue)) {
                throw new KeyFileMissingException('Cannot find key in path ' . $keyValue);
            }

            $decodedKey = $this->decodeKeyFile($keyValue);

        }

        try {
            $this->token = JWT::encode([
                'iss' => $teamId,
                'sub' => $bundleId,
                'iat' => time(),
                'exp' => time() + $tokenTTL,
            ], $decodedKey, 'ES256' , $keyId, [
                'id' => $teamId . '.' . $bundleId
            ]);
        } catch (Throwable $e) {
            throw new TokenGenerationFailedException('Token failed to generate', 0, $e);
        }
    }

    /**
     * Get the generated JWT token
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getToken();
    }

    /**
     * @param string $p8KeyPath
     * @return resource
     */
    protected function decodeKeyFile(string $p8KeyPath)
    {
        $key = openssl_pkey_get_private(Storage::get($p8KeyPath));
        if (! $key) {
            throw new KeyDecodingException('Key could not be decoded.');
        }

        return $key;
    }

    /**
     * @param string $p8KeyString
     * @return resource
     */
    protected function decodeKeyString(string $p8KeyString)
    {
        $key = openssl_pkey_get_private($p8KeyString);
        if (! $key) {
            throw new KeyDecodingException('Key could not be decoded.');
        }

        return $key;
    }
}
