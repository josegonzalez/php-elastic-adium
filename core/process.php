<?php
if (!class_exists('Folder')) {
    include('./folder.php');
}
class Processor {

    private $db = null;
    private $path = null;
    private $options = array();

    function __construct($path, $options = array()) {
        $this->options = array_merge(array(
            'whitelist'     => array(),
            'driver'        => 'elastic',
            'elastic_host'  => 'localhost',
            'elastic_port'  => 9200,
            'pdo_string'    => 'mysql:host=%s;dbname=%s',
            'pdo_user'      => 'username',
            'pdo_pass'      => 'password',
            'pdo_db'        => 'database_name',
            'pdo_host'      => 'localhost',
        ), $options);

        $this->path = $path;
        $this->folder = new Folder($path);
        if ($this->options['driver'] == 'elastic') {
          $this->db = new ElasticDriver(array(
              'host' => $this->options['elastic_host'],
              'port' => $this->options['elastic_port'],
          ));
        } else {
          $this->db = new PDODriver(array(
              'connection_string' => sprintf($this->options['pdo_string'], $this->options['pdo_host'], $this->options['pdo_db']),
              'username'          => $this->options['pdo_user'],
              'password'          => $this->options['pdo_pass']
          ));
        }
    }

    // Does all the work necessary to process the logs
    function work() {
        $contents     = $this->folder->read();
        $accountTypes = $this->listAccounts($contents[0]);
        $chatlogs     = $this->getPaths($accountTypes);
        $this->import($chatlogs);
    }

    // List all the types of accounts
    // as well as the names of the accounts within that type
    function listAccounts($folders) {
        $results = array();
        foreach ($folders as $account) {
            $type = strstr($account, '.', true);
            if (!in_array($type, $this->options['whitelist'])) continue;
            $results[$type][] = substr(strstr($account, '.'), 1);
        }
        return $results;
    }

    // Traverse each account and start the import for those accounts
    function getPaths($accountTypes) {
        $paths = array();
        foreach ($accountTypes as $accountType => $accounts) {
            out("Traversing:: " . $accountType);
            foreach ($accounts as $accountName) {
                $this->folder->cd($this->path . DS . $accountType . '.' . $accountName);
                $contents = $this->folder->read();
                $paths = array_merge($paths,
                    $this->dump($contents[0], $accountName, $accountType)
                );
            }
        }
        return $paths;
    }

    function dump($folders, $accountName, $accountType) {
        $paths = array();
        $base = $this->path . DS . $accountType . '.' . $accountName;
        foreach ($folders as $user) {
            $this->folder->cd($base . DS . $user);
            $contents = $this->folder->read();
            foreach ($contents[0] as $chatlog) {
                $this->folder->cd($base . DS . $user . DS . $chatlog);
                $logs = $this->folder->read();
                foreach ($logs[1] as $log) {
                    if (substr($log, -4) == '.xml') {
                        $paths[] = $base . DS . $user . DS . $chatlog . DS . $log;
                    }
                }
            }
        }
        return $paths;
    }

    function import($paths) {
        libxml_use_internal_errors(true);

        $error_count = 0;
        $messages = 0;
        out(sprintf('Processing %d chatlogs', count($paths)));
        out("###############", 2);
        foreach ($paths as $path) {
            $file = new File($path);
            $chatlog = $file->read();

            libxml_clear_errors();

            try {
                $xml = new SimpleXMLElement($file->read());
            } catch (Exception $e) {
                out(sprintf("There were errors processing::%s", $path));
                out($e->getMessage(), 2);
                $error_count++;
                continue;
            }

            $errors = libxml_get_errors();
            if (!empty($errors)) {
                out(sprintf("There were errors processing::%s", $path), 2);
                $error_count++;
                continue;
            }

            $l = new Log($this->db);
            $l->hydrateLog($xml->attributes());
            $l->save();

            foreach ($xml->children() as $child) {
                $text = trim($child->saveXml());
                $m = new Message($this->db);

                if (substr($text, 0, 6) == '<event') {
                    $m->hydrateMessage($l, $child, 'event');
                } elseif (substr($text, 0, 7) == '<status') {
                    $m->hydrateMessage($l, $child, 'status');
                } elseif (substr($text, 0, 8) == '<message') {
                    $m->hydrateMessage($l, $child, 'message');
                } else {
                    continue;
                }
                $m->save();
                $messages++;
            }
            libxml_clear_errors();
        }

        out('');
        out("###############");
        out(sprintf("Processed %d messages with %d errors", $messages, $error_count), 2);
    }

}