<?php

namespace App\Middleware;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $secretKey = 'ITcampus-SaintMichel-Annecy-2026-CleSecreteBTS-CIEL'; // Remplace par ta vraie clé secrète
    private static $algorithm = 'HS256'; // Algorithme de hachage

    public static function generateToken($data, $expiry = 3600) // Génère le JWT
    {
        $issuedAt = time();
        $expiration = $issuedAt + $expiry; // Date d'expiration du token
        $payload = array(
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => $data
        );
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }

    public static function validateToken($token) // Valide et décode le JWT
    {
        try {
            return JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (Exception $e) {
            return null; // Retourne null si le token est invalide
        }
    }
}