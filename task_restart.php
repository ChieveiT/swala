<?php

foreach(glob(__DIR__ . '/instances/*') as $instance) {
    $pids = parse_ini_file($instance);
    
    system('kill -USR2 ' . $pids['master'], $retVal);
}

