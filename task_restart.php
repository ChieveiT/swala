<?php

foreach(glob(__DIR__ . '/instance/*') as $instance) {
    $pids = parse_ini_file($instance);
    
    system('kill -USR2 ' . $pids['master'], $retVal);
}

