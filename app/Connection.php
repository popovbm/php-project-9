<?php

namespace Hexlet\Code;

use Dotenv\Dotenv;

final class Connection
{
    /**
     * Connection
     * тип @var
     */
    private static ?Connection $conn = null;

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    public function connect()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();

        $databaseUrl = parse_url($_ENV['DATABASE_URL']);
        if (!$databaseUrl) {
            throw new \Exception("Error reading database configuration file");
        }
        $dbHost = $databaseUrl['host'];
        $dbPort = $databaseUrl['port'];
        $dbName = ltrim($databaseUrl['path'], '/');
        $dbUser = $databaseUrl['user'];
        $dbPassword = $databaseUrl['pass'];

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $dbHost,
            $dbPort,
            $dbName,
            $dbUser,
            $dbPassword
        );

        $pdo = new \PDO($conStr);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }

    /**
     * возврат экземпляра объекта Connection
     * тип @return
     */
    public static function get(): Connection
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }

    // protected function __construct()
    // {
    //     $this->conn = $this::get()->connect();
    // }
}
