<?php

namespace Framework;

/**
 * Description of Database
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Database implements SingletonModule
{

    /** @var string */
    private $config = [];

    /** @var \PDO */
    private $db;

    /** @var int */
    public $insertId;

    /** @var int */
    public $affectedRows;

    public function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }

    /**
     * 
     * use for SELECT queries
     * 
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function get($query, array $bindings = [], $exceptionOnFail = true)
    {
        $statement = $this->prepare($query, $bindings, $exceptionOnFail);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return count($results) === 1 ? $results[0] : $results;
    }

    /**
     * 
     * use for SELECT queries
     * 
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function getMore($query, array $bindings = [], $exceptionOnFail = true)
    {
        $statement = $this->prepare($query, $bindings, $exceptionOnFail);

        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     * 
     * use for INSERT, UPDATE and DELETE queries
     * 
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function update($query, array $bindings = [], $exceptionOnFail = true)
    {
        $statement = $this->prepare($query, $bindings, $exceptionOnFail);

        $this->insertId = $this->db->lastInsertId();

        if (is_object($statement)) {
            $this->affectedRows = $statement->rowCount();
        } else {
            $this->affectedRows = 0;
        }


        return $this->insertId ? (int) $this->insertId : $this->affectedRows;
    }

    /**
     * 
     * @param string $query
     * @param array $bindings
     * @return \PDOStatement
     * @throws DatabaseException
     */
    private function prepare($query, array $bindings = [], $exceptionOnFail = true)
    {
        try {
            $statement = $this->db->prepare($query);
            $statement->execute($bindings);

            return $statement;
        } catch (\PDOException $ex) {
            if ($exceptionOnFail) {
                throw (new DatabaseException())->setMessage($ex->getMessage());
            }
        }
    }

    /**
     * @throws DatabaseException
     */
    private function loadConfig()
    {
        $this->config = Config::getDatabase(new DatabaseException(DatabaseException::CONFIG_NOT_FOUND), true);
    }

    /**
     * 
     * @throws DatabaseException
     */
    private function connect()
    {
        $dsn = "mysql:host={$this->config->host};dbname={$this->config->database}";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ];

        try {
            $this->db = new \PDO($dsn, $this->config->user, $this->config->password, $options);
        } catch (\PDOException $ex) {
            throw (new DatabaseException())->setMessage($ex->getMessage());
        }
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'database';
    }

}
