<?php

use Illuminate\Http\Request as IlluminateRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Server
{
    private $swoole_http_server;
    private $laravel_kernel;

    function __construct($host, $port, $setting)
    {
        $this->swoole_http_server = new swoole_http_server($host, $port);
        
        $this->swoole_http_server->set($setting);
    }
    
    public function start()
    {
        $this->swoole_http_server->on('start', array($this, 'onServerStart'));
        $this->swoole_http_server->on('shutdown', array($this, 'onServerShutdown'));
        $this->swoole_http_server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->swoole_http_server->on('request', array($this, 'onRequest'));
    
        $this->swoole_http_server->start();
    }
    
    public function onServerStart($serv)
    {
        // 服务器启动后记录主进程与manager进程的PID，用于写shutdown脚本
        file_put_contents(
            __DIR__ . '/instance/' . $serv->master_pid, 
            "master={$serv->master_pid}\nmanager={$serv->manager_pid}"
        );
    }
    
    public function onServerShutdown($serv)
    {
        // 删除记录有服务器PID的文件
        unlink(__DIR__ . '/instance/' . $serv->master_pid);
    }
    
    public function onWorkerStart($serv, $worker_id)
    {
        // 创建laravel内核（把该逻辑放在此处，确保所有worker创建前父进程副本与laravel
        // 无关，令laravel具备热部署特性）
        require __DIR__ . '/../bootstrap/autoload.php';
        $app = require __DIR__.'/../bootstrap/app.php';
        $this->laravel_kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        
        // 开启“方法欺骗”特性，与laravel5.1 LTS保持一致
        Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }
    
    public function onRequest($request, $response)
    {
        // 由于laravel_kernel接受illuminate_request并返回illuminate_response，所
        // 以该方法针对swoole的请求和响应进行兼容处理
        $illuminate_request = $this->dealWithRequest($request);
        $illuminate_response = $this->laravel_kernel->handle($illuminate_request);
        $this->dealWithResponse($response, $illuminate_response);
        
        // 结束请求生命周期
        $this->laravel_kernel->terminate($illuminate_request, $illuminate_response);
    }
    
    private function dealWithRequest($request)
    {
        $get = isset($request->get) ? $request->get : array();
        $post = isset($request->post) ? $request->post : array();
        $cookie = isset($request->cookie) ? $request->cookie : array();
        $server = isset($request->server) ? $request->server : array();
        $header = isset($request->header) ? $request->header : array();
        
        // #4 swoole 1.7.19 will no longer do urlencode on cookies by denghongcai
        if (strnatcasecmp(SWOOLE_VERSION, '1.7.19') < 0) {
            // #2 laravel结合swoole每次刷新session都会变的问题 by cong8341
            // 注：由于swoole对cookie中的特殊字符（=等）做了urlencode，导致laravel的encrypter
            //     在下次请求时接受到的payload与上一个请求响应时发出的不一致，最终导致每次请求
            //     都解出不一样的laravel_session
            foreach ($cookie as $key => $value) {
                $cookie[$key] = urldecode($value);
            }
        }
        
        // #5 增强与Laravel的兼容，处理特殊请求体 by denghongcai
        // swoole中header并没有包含于$request->server中，需要合并
        foreach ($header as $key => $value) {
            $header['http_'.$key] = $value;
            unset($header[$key]);
        }
        $server = array_merge($server, $header);
        
        // 在swoole环境下$_SERVER的所有key都为小写，需要把它们转化为大写
        foreach ($server as $key => $value) {
            $server[strtoupper($key)] = $value;
            unset($server[$key]);
        }
        
        // #5 增强与Laravel的兼容，处理特殊请求体 by denghongcai
        // 对于Content-Type为非application/x-www-form-urlencoded的请求体需要给
        // SymfonyRequest传入原始的Body
        $content = $request->rawContent();
        $content = empty($content) ? null : $content;
    
        // 创建illuminate_request
        $illuminate_request = IlluminateRequest::createFromBase(
            new SymfonyRequest($get, $post, array(), $cookie, array()/*$_FILES*/, $server, $content)
        );
        
        return $illuminate_request;
    }
    
    private function dealWithResponse($response, $illuminate_response)
    {
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
    }
}
