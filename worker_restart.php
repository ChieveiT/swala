<?php

foreach(glob(__DIR__ . '/instances/*') as $instance) {
    $pids = parse_ini_file($instance);
    
    system('kill -USR1 ' . $pids['master'], $retVal);
}

