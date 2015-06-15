<?php

return array(
    'host' => '127.0.0.1',
    'port' => 9501,

    // http://wiki.swoole.com/wiki/page/274.html
    'max_conn' => 1024,
    'timeout' => 2.5,  //select and epoll_wait timeout. 
    'poll_thread_num' => 4, //reactor thread num
    'writer_num' => 4,     //writer thread num
    'worker_num' => 4,    //worker process num
    'max_request' => 2000,
    'dispatch_mode' => 1,
    
    'log_file' => __DIR__ . '/log/server_log',
    'daemonize' => 1
);
