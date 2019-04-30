<?php
namespace components\db;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class DBConnection {
    private $con;

    private $inTransaction = false;

    /**
     * 
     * @return type
     */
    private function getCon() {
        $this->init();

        return $this->con;
    }

    /**
     * 
     */
    public function beginTransaction() {
        $this->getCon()->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * 
     */
    public function commit() {
        if ($this->isOpen()) {
            $this->con->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * 
     */
    public function rollback() {
        if ($this->inTransaction && $this->isOpen() && $this->inTransaction) {
            $this->con->rollback();
            $this->inTransaction = false;
        }
    }

    /**
     * 
     */
    public function close() {
        if ($this->isOpen()) {
            $this->con = null;
        }
    }

    /**
     * 
     */
    private function init() {
        if (!$this->isOpen()) {
            $this->connectToMySQL();
        }
    }

    /**
     * 
     * @return type
     */
    private function isOpen() {
        return null !== $this->con;
    }

    /**
     * 
     */
    private function connectToMySQL() {
        $this->con = new \PDO(
            sprintf(
                "mysql:host=%s;port=%s;dbname=%s",
                \CW::$app->params['dbServerName'],
                \CW::$app->params['dbPort'],
                \CW::$app->params['dbName']
            ),
            \CW::$app->params['dbUsername'],
            \CW::$app->params['dbPassword']
        );

        $this->con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * 
     * @param type $query
     * @param type $params
     * @return type
     */
    public function query($query, $params = []) {
        $mode = \PDO::FETCH_ASSOC;

        if (empty($params)) {
            $stmt = $this->con->query($query);
            $stmt->setFetchMode($mode);

            return $stmt->fetchAll();
        } else {
            $stmt = $this->con->prepare($query);

            foreach ($params as $name => $value) {
                $stmt->bindParam($name, $value);
            }

            if ($stmt->execute($params)) {
                return $stmt->fetchAll();
            } else {
                return [];
            }
        }
    }

    /**
     * 
     * @param type $query
     * @return type
     */
    public function executeQuery($query) {
        $this->init();

        return $this->con->query($query);
    }

    /**
     * 
     * @param type $query
     * @return type
     */
    public function executeUpdate($query) {
        $this->init();

        return $this->con->exec($query);
    }

    /**
     * 
     * @param type $query
     * @return type
     */
    public function prepare($query) {
        $this->init();

        return $this->con->prepare($query);
    }

    /**
     * 
     * @return type
     */
    public function getLastInsertedId() {
        if ($this->isOpen()) {
            return $this->con->lastInsertId();
        }

        return null;
    }

}
