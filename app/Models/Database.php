<?php
namespace App\Models;

use mysqli;

class Database
{
    protected mysqli $conn;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];
        $name = $_ENV['DB_NAME'];

        $this->conn = new mysqli($host, $user, $pass);
        if ($this->conn->connect_error) {
            throw new \Exception('DB connection failed: ' . $this->conn->connect_error);
        }
        $this->conn->query("CREATE DATABASE IF NOT EXISTS $name");
        $this->conn->select_db($name);
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }
}
