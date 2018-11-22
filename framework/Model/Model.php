<?php

namespace Framework;

/**
 * Description of Model
 *
 * @author Aleksandar Zivanovic <coapsyfactor@gmail.com>
 */
class Model implements SingletonModule
{

    /** @var array */
    private $_modelsDir;

    /** @var array */
    private static $_models;

    /** @var array */
    private static $_loaded;

    /** @var array */
    private static $_actions;

    /** @var bool */
    private static $_initialized;

    /** @var Database */
    private $database;

    public function __construct(Database $database)
    {

        $this->database = $database;
        $this->_modelsDir = Config::getApplication()['models_path'];

        if (false === is_array(self::$_models)) {
            self::$_models = [];
        }

        if (false === is_array(self::$_loaded)) {
            self::$_loaded = [];
        }
    }

    private function loadModels()
    {
        if (self::$_initialized) {
            return;
        }

        $models = scandir($this->_modelsDir);

        $model = $this;

        foreach ($models as $modelFile) {
            if (substr($modelFile, -4) != '.php') {
                continue;
            }

            require_once "{$this->_modelsDir}/{$modelFile}";
        }

        unset($model);

        self::$_initialized = true;
    }

    /**
     * 
     * @param string $name
     * @param string $tableName
     * @param array $fields
     * @param string $primaryKey
     */
    public function registerModel($name, $tableName, array $fields = [], $primaryKey = 'id')
    {
        $this->loadModels();

        if (false === empty(self::$_models[$name])) {
            return;
        }

        self::$_models[$name] = [
            'table' => $tableName,
            'primary_key' => $primaryKey,
            'fields' => $fields,
        ];
    }

    /**
     * 
     * @param string $model
     * @param string $action
     * @param \Closure $callback
     * @throws ModelException
     */
    public function registerAction($model, $action, \Closure $callback)
    {
        $this->loadModels();

        if (empty(self::$_models[$model])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$model} not found");
        }

        if (empty(self::$_actions[$model])) {
            self::$_actions[$model] = [];
        }

        self::$_actions[$model][$action] = $callback;
    }

    /**
     * 
     * @param string $model
     * @param string $action
     * @return mixed
     * @throws ModelException
     */
    public function execute($model, $action)
    {

        $this->loadModels();

        if (empty(self::$_models[$model])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$model} not found");
        }

        if (empty(self::$_actions[$model][$action])) {
            return;
        }

        $args = func_get_args();
        unset($args[0], $args[1]);

        $function = new \ReflectionFunction(self::$_actions[$model][$action]);

        return $function->invokeArgs($args);
    }

    /**
     * 
     * @param string $name
     * @param string|int|float $value
     * @return \stdClass
     * @throws ModelException
     */
    public function getByPrimary($name, $value)
    {
        $this->loadModels();

        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        /* @var $model array */
        $model = self::$_models[$name];

        /* @var $modelKey string */
        $modelKey = "{$name}-{$model['primary_key']}-{$value}";

        if (empty(self::$_loaded[$modelKey])) {
            $this->_load($model['primary_key'], $value, $model['table'], $modelKey);
        }

        return self::$_loaded[$modelKey];
    }

    public function getMore($name, array $data, $limit = 0, $offset = 0)
    {
        return $this->get($name, $data, $limit, $offset, false);
    }

    public function getAll($name, array $data, $limit = 0, $offset = 0)
    {
        $this->loadModels();

        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        $criterias = [
            'criteria' => [],
            'bindings' => [],
        ];

        foreach ($data as $criteria) {
            $build = $this->buildCriteria($criteria);

            if (false === empty($build['string'])) {
                $criterias['criteria'][] = $build['string'];
                $criterias['bindings'] = array_merge($criterias['bindings'], $build['bindings']);
            }
        }

        /* @var $model array */
        $model = self::$_models[$name];

        /* @var $query string */
        $query = "SELECT * FROM `{$model['table']}`";

        $query .= empty($criterias['criteria']) ? '' : ' WHERE (' . implode(') OR (', $criterias['criteria']) . ')';
        $query .= $limit ? " LIMIT {$offset}, {$limit}" : '';

        return $this->database->get($query, $criterias['bindings']);
    }

    private function buildCriteria(array $data, $glue = ' AND ')
    {
        $results = [];

        foreach ($data as $field => $value) {
            $fieldPrefix = uniqid();
            $results['data'][] = "`{$field}` = :{$fieldPrefix}_{$field}";
            $results['bindings'][":{$fieldPrefix}_{$field}"] = $value;
        }

        $results['string'] = implode($glue, $results['data']);

        return $results;
    }

    /**
     * 
     * @param string $name
     * @param array $data
     * @return array
     * @throws ModelException
     */
    public function get($name, array $data, $limit = 0, $offset = 0, $singleIfOne = true)
    {
        $this->loadModels();

        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        /* @var $model array */
        $model = self::$_models[$name];

        /* @var $query string */
        $query = "SELECT * FROM `{$model['table']}`";

        list($criteria, $bindings) = $this->mapFields($data, ' AND ');

        $query .= empty($criteria) ? : "WHERE {$criteria}";
        $query .= $limit ? " LIMIT {$offset}, {$limit}" : '';

        /* @var $results array */
        $results = $singleIfOne ? $this->database->get($query, $bindings) : $results = $this->database->getMore($query, $bindings);

        foreach ($results as $result) {
            if (is_string($result)) {
                $this->storeInMemory($name, $results);

                break;
            }

            $this->storeInMemory($name, $result);
        }

        return $results;
    }

    /**
     * 
     * @param string $name
     * @param array $data
     * @param bool $exceptionOnFail
     * @return int
     * @throws ModelException
     */
    public function delete($name, array $data, $exceptionOnFail = true)
    {
        $this->loadModels();

        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        $beforeDelete = $this->execute($name, 'before.save', $data);

        if (false === $beforeDelete) {
            return;
        }

        $_data = is_array($beforeDelete) ? $beforeDelete : $data;

        /* @var $model array */
        $model = self::$_models[$name];

        list($query, $bindings) = $this->buildDeleteQuery($model['table'], $_data, $model['primary_key']);

        return $this->database->update($query, $bindings, $exceptionOnFail);
    }

    /**
     * 
     * @param string $name
     * @param array $data
     * @param bool $exceptionOnFail
     * @return result
     * @throws ModelException
     */
    public function save($name, array $data, $exceptionOnFail = true)
    {
        $this->loadModels();

        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        $beforeSave = $this->execute($name, 'before.save', $data);

        if (false === $beforeSave) {
            return;
        }

        $_data = is_array($beforeSave) ? $beforeSave : $data;

        /* @var $model array */
        $model = self::$_models[$name];

        if (empty($_data[$model['primary_key']])) {
            list($query, $values) = $this->buildInsertQuery($model['table'], $_data);
        } else {
            list($query, $values) = $this->buildUpdateQuery($model['table'], $_data, $model['primary_key']);
        }

        $result = $this->database->update($query, $values, $exceptionOnFail);

        $this->execute($name, 'after.save', $_data, $result);

        return $result;
    }

    private function buildInsertQuery($table, array $data)
    {
        list($fields, $values) = $this->mapFields($data);

        return ["INSERT INTO `{$table}` SET {$fields}", $values];
    }

    private function buildUpdateQuery($table, array $data, $primaryKey = 'id')
    {
        if (empty($data[$primaryKey])) {
            throw (new ModelException(ModelException::INVALID_FIELD))->setMessage("Primary key {$primaryKey} for {$table} not found");
        }

        list($fields, $values) = $this->mapFields($data);

        return ["UPDATE `{$table}` SET {$fields} WHERE `{$primaryKey}` = :{$primaryKey}", $values];
    }

    private function buildDeleteQuery($table, array $data, $primaryKey = 'id')
    {
        list($fields, $values) = $this->mapFields($data, ' AND ');

        return ["DELETE FROM `{$table}` WHERE {$fields}", $values];
    }

    /**
     * 
     * @param array $data
     * @return array
     */
    private function mapFields(array $data, $glue = ', ')
    {
        foreach ($data as $field => $value) {
            $fields[] = "`{$field}` = :{$field}";

            $values[":{$field}"] = $value;
        }

        return [implode($glue, $fields), $values];
    }

    /**
     * 
     * @param string $name
     * @param array $data
     * @throws ModelException
     */
    private function storeInMemory($name, array $data)
    {
        if (empty(self::$_models[$name])) {
            throw (new ModelException(ModelException::MODEL_NOT_FOUND))->setMessage("Model {$name} not found");
        }

        /* @var $model array */
        $model = self::$_models[$name];

        if (empty($data[$model['primary_key']])) {
            throw (new ModelException(ModelException::BAD_RESULT))->setMessage("Model {$name} has no primary key");
        }

        /* @var $modelKey string */
        $modelKey = "{$name}-{$model['primary_key']}-{$data[$model['primary_key']]}";

        self::$_loaded[$modelKey] = $data;
    }

    /**
     * 
     * @param string $key
     * @param string|int $value
     * @param string $table
     * @param string $modelKey
     */
    private function _load($key, $value, $table, $modelKey)
    {
        $query = "SELECT * FROM `{$table}` WHERE `{$key}` = :key";
        self::$_loaded[$modelKey] = $this->database->get($query, [':key' => $value]);
    }

    /**
     * 
     * @return string
     */
    public function getSingletonName()
    {
        return 'model';
    }

}
