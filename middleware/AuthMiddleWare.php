<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/apiToken.php';

class AuthMiddleware
{
    /**
     * Extract the Bearer token from the Authorization header.
     * Handles multiple Apache/PHP configurations.
     */
    public static function getBearerToken()
    {
        $headers = null;

        // Method 1: Standard PHP
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        }
        // Method 2: Some Apache configs
        elseif (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        }
        // Method 3: Apache with apache_request_headers()
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('strtolower', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['authorization'])) {
                $headers = trim($requestHeaders['authorization']);
            }
        }
        // Method 4: Apache rewrite drops Authorization header
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        if ($headers && preg_match('/Bearer\s+(.+)$/i', $headers, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Validate the incoming request token.
     * Sends a 401 JSON response and exits if authentication fails.
     *
     * @return int  The authenticated user_id on success.
     */
    public static function authenticate()
    {
        header("Content-Type: application/json");

        $token = self::getBearerToken();

        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'message' => 'Token de acceso requerido. Incluya el header: Authorization: Bearer <token>'
            ]);
            exit;
        }

        $database = new Database();
        $db       = $database->getConnection();
        $apiToken = new ApiToken($db);

        $userId = $apiToken->validate($token);

        if ($userId === false) {
            http_response_code(401);
            echo json_encode([
                'message' => 'Token inválido, expirado o revocado.'
            ]);
            exit;
        }

        return $userId;
    }
}
?>