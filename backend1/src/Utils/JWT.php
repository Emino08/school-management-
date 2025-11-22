<?php

namespace App\Utils;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT
{
    public static function encode($payload)
    {
        $issuedAt = time();
        $expire = $issuedAt + (int)$_ENV['JWT_EXPIRY'];

        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expire;

        return FirebaseJWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function decode($token)
    {
        if (empty($_ENV['JWT_SECRET'])) {
            throw new \Exception('JWT_SECRET not configured');
        }
        
        try {
            return FirebaseJWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw $e; // Re-throw specific exception
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw $e; // Re-throw specific exception
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
}
