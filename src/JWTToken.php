<?php
namespace Rich2k\LaravelWeatherKit;

use Firebase\JWT\JWT;
use Rich2k\LaravelWeatherKit\Exceptions\KeyDecodingException;
use Rich2k\LaravelWeatherKit\Exceptions\KeyFileMissingException;
use Rich2k\LaravelWeatherKit\Exceptions\TokenGenerationFailedException;
use Throwable;

/**
 * JWTToken
 *
 * @package Rich2k\LaravelWeatherKit
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
     * @param string $p8KeyPath
     * @param string $keyId
     * @param string $teamId
     * @param string $appBundleId
     * @param int $tokenTTL
     */
    public function __construct(string $p8KeyPath, string $keyId, string $teamId, string $bundleId, int $tokenTTL)
    {
        if (! file_exists($p8KeyPath)) {
            throw new KeyFileMissingException('Cannot find key in path ' . $p8KeyPath);
        }

        $decodedKey = $this->decodeKey($p8KeyPath);

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
    protected function decodeKey(string $p8KeyPath)
    {
        $key = openssl_pkey_get_private(file_get_contents($p8KeyPath));
        if (! $key) {
            throw new KeyDecodingException('Key could not be decoded.');
        }

        return $key;
    }
}
