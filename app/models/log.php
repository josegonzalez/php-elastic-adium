<?php
class Log extends Model {
    public $_fields  = array('account', 'service', 'version', 'transport');

    public function __construct(&$driver, $options = array()) {
        $options['usePath'] = 'chatlogs/logs';
        parent::__construct($driver, $options);
    }

    public function hydrateLog($attributes) {
        foreach ($this->_fields as $field) {
            if (isset($attributes->$field)) {
                $this->$field = (string) $attributes->$field;
            }
        }
    }
}