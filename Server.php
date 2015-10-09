<?php

use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Server
{
    private $swoole_http_server;
    private $laravel_kernel;

    //创建swoole http服务器
    function __construct($host, $port, $setting)
    {       
        $this->swoole_http_server = new swoole_http_server($host, $port);
        
        //swoole配置项
        $this->swoole_http_server->set($setting);
    }
    
    public function start()
    {
        //注册start回调
        $this->swoole_http_server->on('start', array($this, 'onServerStart'));
        //注册shutdown回调
        $this->swoole_http_server->on('shutdown', array($this, 'onServerShutdown'));
        //注册workerStart回调
        $this->swoole_http_server->on('WorkerStart', array($this, 'onWorkerStart'));
        //注册request回调
        $this->swoole_http_server->on('request', array($this, 'onRequest'));
    
        //启动swoole
        $this->swoole_http_server->start();
    }
    
    public function onServerStart($serv)
    {
        //服务器启动后记录主进程与manager进程的PID，用于写shutdown脚本
        file_put_contents(
            __DIR__ . '/instance/' . $serv->master_pid, 
            "master={$serv->master_pid}\nmanager={$serv->manager_pid}"
        );
    }
    
    public function onServerShutdown($serv)
    {
        //删除记录有服务器PID的文件
        unlink(__DIR__ . '/instance/' . $serv->master_pid);
    }
    
    public function onWorkerStart($serv, $worker_id)
    {
        //创建laravel内核（把该逻辑放在此处，确保所有worker创建前父进程副本与laravel
        //无关，令laravel具备热部署特性）
        require __DIR__ . '/../bootstrap/autoload.php';
        $app = require __DIR__.'/../bootstrap/app.php';
        $this->laravel_kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        
        //开启“方法欺骗”特性，与laravel5.1 LTS保持一致
        Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }
    
    public function onRequest($request, $response)
    {
        //[备注]由于laravel_kernel接受illuminate_request并返回illuminate_response，所
        //      以该方法针对swoole的请求和响应进行兼容处理
    
        //兼容Swoole的请求对象
        $get = isset($request->get) ? $request->get : array();
        $post = isset($request->post) ? $request->post : array();
        $cookie = isset($request->cookie) ? $request->cookie : array();
        $server = isset($request->server) ? $request->server : array();
        
        //在swoole环境下$_SERVER的所有key都为小写，需要把它们转化为大写
        foreach ($server as $key => $value) {
            $server[strtoupper($key)] = $value;
            unset($server[$key]);
        }
    
        //创建illuminate_request
        $illuminate_request = IlluminateRequest::createFromBase(
            new SymfonyRequest($get, $post, array(), $cookie, array()/*$_FILES*/, $server)
        );
        
        //把illuminate_request传入laravel_kernel后，取回illuminate_response
        $illuminate_response = $this->laravel_kernel->handle($illuminate_request);
        
        //兼容Swoole的响应对象
        // status
        $response->status($illuminate_response->getStatusCode());
        // headers
        foreach ($illuminate_response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }
        // cookies
        foreach ($illuminate_response->headers->getCookies() as $cookie) {
            $response->cookie(
                $cookie->getName(), 
                $cookie->getValue(), 
                $cookie->getExpiresTime(), 
                $cookie->getPath(), 
                $cookie->getDomain(), 
                $cookie->isSecure(), 
                $cookie->isHttpOnly()
            );
        }
        // content
        $content = $illuminate_response->getContent();
        // send content & close
        $response->end($content);
        
        //结束请求生命周期（依次调用内核中所有middleware的teminate）
        $this->laravel_kernel->terminate($illuminate_request, $illuminate_response);
    }
}
