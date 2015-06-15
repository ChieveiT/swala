<?php
// 配置
$setting = array(
    'worker_num' => 1,    //worker process num
    'max_request' => 100,
    'dispatch_mode' => 1,
    
    'timeout' => 10,
);
$serv = new swoole_http_server('127.0.0.1', 9501);
$serv->set($setting);

//全局计数器
$count = 0;

//测试worker是否能常驻对象资源
$serv->on('request', function($request, $response){
    global $count;
    $count++;
    
    sleep(5);
    
    $time = date('H:i:s');
    $response->end("request: {$count}<br>time: {$time}");
});

$serv->start();

