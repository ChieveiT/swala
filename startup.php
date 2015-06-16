<?php

require __DIR__ . '/Server.php';

$setting = require __DIR__ . '/config.php';

$host = array_shift($setting);
$port = array_shift($setting);

$serv = new Server($host, $port, $setting);

$serv->start();
