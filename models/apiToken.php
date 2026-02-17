<?php
class ApiToken
{
    private $conn;
    private $table_name = "api_tokens";

    // Token lifetime in hours
    const TOKEN_LIFETIME_HOURS = 24;

    public $id;
    public $user_id;
    public $token;
    public $expires_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Generate a cryptographically secure token string
     */
    public static function generateTokenString()
    {
        return bin2hex(random_bytes(32)); // 64-char hex string
    }

    /**
     * Persist a new token for the given user_id.
     * Populates $this->token and $this->expires_at.
     */
    public function create($user_id)
    {
        // Revoke any existing active tokens for this user (single-session policy)
        $this->revokeAllForUser($user_id);

        $this->user_id   = $user_id;
        $this->token     = self::generateTokenString();
        $this->expires_at = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_LIFETIME_HOURS . ' hours'));

        $query = "INSERT INTO " . $this->table_name . "
                  (user_id, token, expires_at)
                  VALUES (:user_id, :token, :expires_at)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id",    $this->user_id);
        $stmt->bindParam(":token",      $this->token);
        $stmt->bindParam(":expires_at", $this->expires_at);

        return $stmt->execute();
    }

    /**
     * Validate a bearer token.
     * Returns the user_id on success, or false if invalid / expired / revoked.
     */
    public function validate($tokenString)
    {
        $query = "SELECT id, user_id, expires_at, revoked
                  FROM " . $this->table_name . "
                  WHERE token = :token
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $tokenString);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false; // Token not found
        }

        if ($row['revoked']) {
            return false; // Token has been revoked
        }

        if (strtotime($row['expires_at']) < time()) {
            return false; // Token has expired
        }

        return (int) $row['user_id'];
    }

    /**
     * Revoke all active tokens for a given user.
     */
    public function revokeAllForUser($user_id)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET revoked = TRUE
                  WHERE user_id = :user_id AND revoked = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    /**
     * Revoke a specific token (logout).
     */
    public function revoke($tokenString)
    {
        $query = "UPDATE " . $this->table_name . "
                  SET revoked = TRUE
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $tokenString);
        return $stmt->execute();
    }
}
?>