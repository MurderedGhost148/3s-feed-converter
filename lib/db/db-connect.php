<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/model/exception/database-exception.php';

class DbConnection {
    private string $hostname;
    private string $username;
    private string $password;
    private string $database;

    private ?mysqli $conn = null;

    public function __construct(
        string $hostname,
        string $username,
        string $password,
        string $database
    ) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * @throws DatabaseException
     */
    public function connect(): mysqli
    {
        if(empty($this->conn)) {
            $conn = new mysqli($this->hostname, $this->username, $this->password, $this->database);
            $conn->set_charset("utf8");

            if ($conn->connect_error) {
                throw new DatabaseException(
                    "Не удалось установить соединение с базой данных по причине: $conn->connect_error"
                );
            }

            $this->conn = $conn;
        }

        return $this->conn;
    }
}