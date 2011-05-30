<?php
interface iDriver {
    public function connect();
    public function safe($var);
    public function save($record);
}

class ElasticDriver implements iDriver {
    public function __construct($config = array())  {
        $this->config = array_merge(array(
            'host' => 'localhost',
            'port' => '9200'
        ), $config);
    }

    public function connect() {
        Curl::init();
        return true;
    }

    public function safe($var) {
        return $var;
    }

    public function save($record) {
        $this->connect();

        if ($record->isNew()) {
            $record->created = $record->modified = strftime("%Y-%m-%d %H:%M:%S", time());

            $response = Curl::post(
                sprintf("http://%s/%s/", $this->_host(), $this->collection($record)),
                json_encode($record->_updated)
            );
        } else {
            $record->modified = strftime("%Y-%m-%d %H:%M:%S", time());

            $response = Curl::put(
                sprintf("http://%s/%s/%s", $this->_host(), $this->collection($record), $record->{$record->_config['primaryKey']}),
                json_encode($record->_updated)
            );
        }

        $response = json_decode($response->body);
        if ($response == null || $response->ok != 1) {
            die;
            return false;
        }

        $record->_version = $response->_version;

        // If we've just inserted a new record, set the ID of this object
        if ($record->isNew() && is_null($record->{$record->_config['primaryKey']})) {
            $record->afterSave($response->_id);
        } else {
            $record->afterSave();
        }
        return true;
    }

    protected function _host() {
        $host = $this->config['host'];
        if (isset($this->config['port'])) {
            $host .= ':' . $this->config['port'];
        }
        return $host;
    }

    protected function collection($record) {
        if (isset($record->_config['usePath'])) {
            return $record->_config['usePath'];
        }
        return $record->_config['useTable'];
    }

}

class PDODriver implements iDriver{
    protected $db = null;

    public function __construct($config = array())  {
        $this->config = array_merge(array(
            'connection_string' => 'sqlite::memory:',
            'username'          => 'username',
            'password'          => 'password',
            'options'           => array(),
            'error_mode'        => PDO::ERRMODE_EXCEPTION,
            'quote_char'        => null,
        ), $config);
    }

    public function connect() {
        if ($this->db !== null) return true;

        $this->db = new PDO(
            $this->config['connection_string'],
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );
        $this->db->setAttribute(PDO::ATTR_ERRMODE, $this->config['error_mode']);

        if (is_null($this->config['quote_char'])) {
            switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                case 'pgsql':
                case 'sqlsrv':
                case 'dblib':
                case 'mssql':
                case 'sybase':
                    $this->config['quote_char'] = '"';
                    break;
                case 'mysql':
                case 'sqlite':
                case 'sqlite2':
                default:
                    $this->config['quote_char'] = '`';
                    break;
            }
        }
    }

    public function safe($var) {
        return $this->db->quote($var);
    }

    public function save($record) {
        $this->connect();

        $query = array();
        $values = array_values($record->_updated);

        if ($record->isNew()) { // UPDATE
            $query = $this->_buildInsert($record);
        } else {
            // If there are no dirty values, do nothing
            if (count($values) == 0) {
                return true;
            }
            $query = $this->_buildUpdate($record);
            $values[] = $record->{$record->_config['primaryKey']};
        }

        $statement = $this->db->prepare($query);
        $success = $statement->execute($values);

        // If we've just inserted a new record, set the ID of this object
        if ($record->isNew() && is_null($record->{$record->_config['primaryKey']})) {
            $record->afterSave($this->db->lastInsertId());
        } else {
            $record->afterSave();
        }

        return $success;
    }

    protected function _buildInsert($record) {
        $data = $record->_updated;

        $query[] = "INSERT INTO";
        $query[] = $this->_quote($record->_config['useTable']);
        $field_list = array_map(array($this, '_quote'), array_keys($data));
        $query[] = "(" . join(", ", $field_list) . ")";
        $query[] = "VALUES";

        $placeholders = $this->_placeholders(count($data));
        $query[] = "({$placeholders})";
        return join(" ", $query);
    }

    protected function _buildUpdate($record) {
        $data = $record->_updated;

        $query = array();
        $query[] = "UPDATE {$this->_quote($record->_config['useTable'])} SET";

        $field_list = array();
        foreach ($data as $key => $value) {
            $field_list[] = "{$this->_quote($key)} = ?";
        }
        $query[] = join(", ", $field_list);
        $query[] = "WHERE";
        $query[] = $this->_quote($record->_config['primaryKey']);
        $query[] = "= ?";
        return join(" ", $query);
    }

    protected function _quote($identifier) {
        $parts = explode('.', $identifier);
        $parts = array_map(array($this, '_quote_part'), $parts);
        return join('.', $parts);
    }

    protected function _quote_part($part) {
        if ($part === '*') return $part;
        return $this->config['quote_char'] . $part . $this->config['quote_char'];
    }

    protected function _placeholders($number_of_placeholders) {
        return join(", ", array_fill(0, $number_of_placeholders, "?"));
    }

}

class Model {

    public $_driver = null;
    public $_config = array();

    public $_version = null;
    public $_data = array();
    public $_updated = array();

    public function __construct(&$driver, $options = array()) {
        $this->_config = array_merge(array(
            'data' => array(),
            'created' => true,
            'useTable' => null,
            'primaryKey' => 'id',
        ), $options);

        if (!empty($this->_config['data'])) {
            $this->_data = (array) $this->_config['data'];
        }

        if (isset($this->_data[$this->_config['primaryKey']])) {
            $this->_config['created'] = false;
        } else {
            $this->_updated = $this->_data;
        }

        if (!$this->_config['useTable']) {
            $this->_config['useTable'] = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', get_class($this))) . 's';
        }

        $this->_driver = $driver;

        unset($this->_config['data']);
    }

    public function __get($key) {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function __set($key, $value) {
        $this->_data[$key] = $value;
        $this->_updated[$key] = $value;
    }

    public function isNew() {
        return $this->_config['created'];
    }

    public function isUpdated() {
        return !empty($this->_updated);
    }

    public function afterSave($primaryKey = null) {
        if ($primaryKey) {
            $this->{$this->_config['primaryKey']} = $primaryKey;
        }

        $this->_updated = array();
        $this->_config['created'] = false;
    }

    public function save() {
        return $this->_driver->save($this);
    }
}