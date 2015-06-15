<?php

require __DIR__ . '/Server.php';

$setting = require __DIR__ . '/config.php';

$serv = new Server($setting['host'], $setting['port'], $setting);

unset($setting['host']);
unset($setting['port']);

$serv->start();
