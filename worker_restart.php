<?php

foreach(glob(__DIR__ . '/instance/*') as $instance) {
    $pids = parse_ini_file($instance);
    
    system('kill -USR1 ' . $pids['master'], $retVal);
}

