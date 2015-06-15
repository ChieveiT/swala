<?php
// 配置
$setting = array(
    'worker_num' => 1,    //worker process num
    'max_request' => 3,
    'dispatch_mode' => 1,
);
$serv = new swoole_http_server('127.0.0.1', 9501);
$serv->set($setting);

//全局资源
$resource = NULL;

//测试worker是否能常驻对象资源
$serv->on('request', function($request, $response){
    global $resource;

    if (class_exists('A', false) && $resource) {
        $response->end('Class A is loaded & Object A still exists');
        return;
    }
        
    require __DIR__ . '/A.php';
        
    $resource = new A();
        
    $response->end('request first');
});

$serv->start();

