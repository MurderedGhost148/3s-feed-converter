<?php
require_once __DIR__ . "/../../config/app-config.php";
require_once __DIR__ . "/../../lib/db/db-connect.php";

/**
 * @var array $config
 */

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) : bool
    {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) : bool
    {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle) : bool
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}

function dir_exists($folder)
{
    $path = realpath($folder);

    return $path !== false and is_dir($path);
}

class DbUtils {
    private DbConnection $dbConnection;

    public function __construct($config = null)
    {
        $this->dbConnection = new DbConnection(
            $config['hostname'], $config['username'], $config['password'], $config['database']
        );
    }

    public function addProfitBaseElement($id, $service, $house, $type, $xml_data)
    {
        $sql = "
            INSERT INTO profitbase_data (id, service, house, category, xml_data) 
                VALUES (?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE category = ?, xml_data = ?";

        $stmt = $this->getConnection()->prepare($sql);

        $params = array($id, $service, $house, $type, $xml_data, $type, $xml_data);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $result = $stmt->execute();

        if(!$result){
            Logger::error("Не удалось добавить элемент: $stmt->error");
        }

        return $result;
    }

    public function getProfitBaseElements($query = array(), $options = array())
    {
        $sql = "SELECT * FROM profitbase_data";
        $data = array();

        if(($count = count($query)) > 0){
            $sql .= " WHERE ";

            foreach($query as $option){
                $count--;

                $sql .= "$option";

                if($count > 0){
                    $sql .= " AND ";
                }
            }
        }

        if(isset($options['limit'])){
            $sql .= " LIMIT " . $options['limit'];
        }

        $result = $this->getConnection()->query($sql);

        if($result){
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }

        return $data;
    }

    public function clearTable($table, $options = array())
    {
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM $table";

        if(($count = count($options)) > 0){
            $sql .= " WHERE ";

            foreach($options as $option){
                $count--;

                $sql .= "$option";

                if($count > 0){
                    $sql .= " AND ";
                }
            }
        }

        $conn = $this->getConnection();
        if(!$conn->query($sql) && $conn->errno != 1091){
            Logger::error("Не удалось удалить данные: $conn->error");

            return false;
        }

        return true;
    }

    public function getOneTask($options = array())
    {
        $sql = "SELECT * FROM profitbase_tasks";

        if(($count = count($options)) > 0){
            $sql .= " WHERE ";

            foreach($options as $option){
                $count--;

                $sql .= "$option";

                if($count > 0){
                    $sql .= " AND ";
                }
            }
        }

        $sql .= " LIMIT 1";

        $result = $this->getConnection()->query($sql);

        $task = null;
        if($result && $result->num_rows > 0){
            if($row = $result->fetch_assoc()){
                $task = $row;
            }
        }

        return $task;
    }

    public function insertTask($type, $house, $command)
    {
        $sql = "INSERT INTO profitbase_tasks (service, house, command) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE command = ?";

        $stmt = $this->getConnection()->prepare($sql);

        $params = array($type, $house, json_encode($command), json_encode($command));
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $result = $stmt->execute();

        if(!$result){
            Logger::error("Не удалось добавить задачу: $stmt->error");
        }

        return $result;
    }

    public function deleteTask($id){
        if(isset($id) && is_numeric($id)){
            return $this->clearTable('profitbase_tasks', ["id = $id"]);
        }

        return false;
    }

    public function getRowsCount($table, $options = array())
    {
        $sql = "SELECT COUNT(*) as count FROM $table";
        $count = 0;

        if(($count = count($options)) > 0){
            $sql .= " WHERE ";

            foreach($options as $option){
                $count--;

                $sql .= "$option";

                if($count > 0){
                    $sql .= " AND ";
                }
            }
        }

        $result = $this->getConnection()->query($sql);

        if($result){
            if($row = $result->fetch_assoc()){
                $count = (int) $row['count'];
            }
        }

        return $count;
    }

    private function getConnection(): mysqli
    {
        try {
            return $this->dbConnection->connect();
        } catch (DatabaseException $ex) {
            Logger::error("Не удалось установить соединение: {$ex->getMessage()}");

            die();
        }
    }
}

class Logger {
    public static function info(string $msg)  : void
    {
        self::log($msg, 'INFO');
    }

    public static function warn(string $msg)  : void
    {
        self::log($msg, 'WARN');
    }

    public static function error(string $msg) : void
    {
        self::log($msg, 'ERROR');
    }

    private static function log(string $msg, string $level) : void
    {
        $date = date('Y-m-d H:i:s');
        $path = __DIR__ . "/../../debug";

        if(!dir_exists($path)) {
            mkdir($path, 0777, true);
        }

        $log = fopen($path . "/app.log", 'a+');
        fwrite($log, "[$date] [$level] $msg". PHP_EOL);
        fclose($log);
    }
}

$dbUtils = new DbUtils($config);