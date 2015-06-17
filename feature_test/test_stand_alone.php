<?php
// 配置
$setting = array(
    'worker_num' => 3,    //worker process num
    'max_request' => 3,
    'dispatch_mode' => 1,
);
$serv = new swoole_http_server('127.0.0.1', 9501);
$serv->set($setting);

//全局资源
$resource = 'global';

//测试三条worker进程的全局资源是否存在竞争
$serv->on('WorkerStart', function($serv, $worker_id){
    global $resource;
    $resource = 'worker ' . $worker_id;
});
$serv->on('request', function($request, $response){
    global $resource;
    $response->end($resource);
});

$serv->start();

