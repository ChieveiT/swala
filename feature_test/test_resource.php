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

//测试worker的资源管理特性
$serv->on('request', function($request, $response){
    global $resource;

    if (class_exists('A', false) && $resource && !isset($temp_resource)) {
        $response->end('Class A is loaded<br>Object A still exists<br>temp_resource is undefined');
        return;
    }
    
    //类定义
    require __DIR__ . '/A.php';
    
    //暴露在全局作用域的全局符号
    $resource = new A();
    
    //局限在函数作用域内的局部符号
    $temp_resource = 'temp resource';
        
    $response->end('request first');
});

$serv->start();

