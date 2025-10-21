<?php
class JwtHelper
{
    /**
     * Valida un JWT y retorna resultado detallado
     * @param string $jwt Token JWT
     * @return array Resultado con status y detalles
     */
    public static function validateJwtDetailed($jwt)
    {
        if (!$jwt) {
            return [
                'valid' => false,
                'error' => 'TOKEN_MISSING',
                'message' => 'Token no proporcionado'
            ];
        }

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [
                'valid' => false,
                'error' => 'TOKEN_MALFORMED',
                'message' => 'Formato de token inválido'
            ];
        }

        try {
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            $signatureProvided = $parts[2];

            $base64UrlHeader = $parts[0];
            $base64UrlPayload = $parts[1];
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
            $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            // Verificar firma
            if ($base64UrlSignature !== $signatureProvided) {
                return [
                    'valid' => false,
                    'error' => 'TOKEN_INVALID_SIGNATURE',
                    'message' => 'Firma del token inválida'
                ];
            }

            // Verificar expiración
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return [
                    'valid' => false,
                    'error' => 'TOKEN_EXPIRED',
                    'message' => 'Token expirado',
                    'expired_at' => date('Y-m-d H:i:s', $payload['exp']),
                    'current_time' => date('Y-m-d H:i:s')
                ];
            }

            return [
                'valid' => true,
                'payload' => $payload
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'TOKEN_DECODE_ERROR',
                'message' => 'Error al decodificar token: ' . $e->getMessage()
            ];
        }
    }

    public static function validateJwt($jwt)
    {
        $result = self::validateJwtDetailed($jwt);
        return $result['valid'];
    }

    public static function decodeJwt($jwt)
    {
        if (!$jwt) return null;
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return $payload;
    }
}
