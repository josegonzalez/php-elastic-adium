<?php
include './app/config/bootstrap.php';

include './core/file.php';
include './core/folder.php';
include './core/convenience.php';
include './core/process.php';

include './core/orm/curl.php';
include './core/orm/model.php';

include './app/models/log.php';
include './app/models/message.php';

$processor = new Processor($PATH, $config);
$processor->work();