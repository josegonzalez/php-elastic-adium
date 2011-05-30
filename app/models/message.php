<?php
class Message extends Model {
    public $_fields  = array('sender', 'time', 'type');

    public function __construct(&$driver, $options = array()) {
        $options['usePath'] = 'chatlogs/messages';
        parent::__construct($driver, $options);
    }

    public function hydrateMessage($l, $xml, $type = 'message') {
        $this->log_id = $l->id;
        $this->element = $type;

        $attributes = $xml->attributes();
        foreach ($this->_fields as $field) {
            if (isset($attributes->$field)) {
                $this->$field = (string) $attributes->$field;
            }
        }

        $body = array();
        if ($xml->count()) {
            foreach ($xml->children() as $child) {
                $body[] = $child->asXml();
            }
        } elseif ($type == 'message') {
            $body[] = (string) $xml[0];
        }

        if (!empty($body)) {
            $this->html = implode($body);
            $this->message = strip_tags(implode($body));
        }

        // Created is in UTC
        $this->created = date('Y-m-d H:i:s', strtotime($this->time));
    }

}