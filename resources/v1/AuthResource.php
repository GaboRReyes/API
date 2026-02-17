<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/apiUser.php';
require_once __DIR__ . '/../../models/apiToken.php';
require_once __DIR__ . '/../../middleware/AuthMiddleWare.php';

class AuthResource
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }


    public function login()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        // Validate input presence
        if (empty($data->username) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Se requieren username y password.']);
            return;
        }

        $username = htmlspecialchars(strip_tags(trim($data->username)));

        // Look up user
        $apiUser = new ApiUser($this->db);
        if (!$apiUser->findByUsername($username)) {
            http_response_code(401);
            echo json_encode(['message' => 'Credenciales inválidas.']);
            return;
        }

        // Verify password
        if (!$apiUser->verifyPassword($data->password)) {
            http_response_code(401);
            echo json_encode(['message' => 'Credenciales inválidas.']);
            return;
        }

        // Issue token
        $apiToken = new ApiToken($this->db);
        if (!$apiToken->create($apiUser->id)) {
            http_response_code(503);
            echo json_encode(['message' => 'No se pudo generar el token de acceso.']);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'access_token' => $apiToken->token,
            'expires_at'   => $apiToken->expires_at,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $apiUser->id,
                'username' => $apiUser->username,
                'email'    => $apiUser->email,
            ]
        ]);
    }


    public function logout()
    {
        header("Content-Type: application/json");

        $token = AuthMiddleware::getBearerToken();

        if (!$token) {
            http_response_code(400);
            echo json_encode(['message' => 'No se proporcionó ningún token.']);
            return;
        }

        $apiToken = new ApiToken($this->db);
        $apiToken->revoke($token);

        http_response_code(200);
        echo json_encode(['message' => 'Sesión cerrada correctamente.']);
    }
}
?>