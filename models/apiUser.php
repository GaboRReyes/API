<?php
class ApiUser
{
    private $conn;
    private $table_name = "api_users";

    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $status;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

   
    public function findByUsername($username)
    {
        $query = "SELECT id, username, email, password_hash, status
                  FROM " . $this->table_name . "
                  WHERE username = :username AND status = 'ACTIVE'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id            = $row['id'];
            $this->username      = $row['username'];
            $this->email         = $row['email'];
            $this->password_hash = $row['password_hash'];
            $this->status        = $row['status'];
            return true;
        }
        return false;
    }


    public function verifyPassword($plainPassword)
    {
        return password_verify($plainPassword, $this->password_hash);
    }
}
?>