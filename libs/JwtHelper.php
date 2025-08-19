<?php
class JwtHelper
{
    public static function validateJwt($jwt)
    {
        if (!$jwt) return false;
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;

        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $signatureProvided = $parts[2];

        $base64UrlHeader = $parts[0];
        $base64UrlPayload = $parts[1];
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        // Verifica la firma y la expiración
        if ($base64UrlSignature !== $signatureProvided) return false;
        if (isset($payload['exp']) && $payload['exp'] < time()) return false;

        return true;
    }
}
