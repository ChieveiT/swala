#Swoole & Laravel
以swoole_http_server为应用服务器，Laravel为业务框架的web后端方案

##依赖
<table>
	<tr>
		<td>php</td><td>>=5.5.9</td>
	</tr>
	<tr>
		<td>ext-swoole</td><td>>=1.7.7</td>
	</tr>
	<tr>
		<td>laravel/framework</td><td>5.1.*</td>
	</tr>
	<tr>
		<td>swala/installer</td><td>~1.0</td>
	</tr>
</table>

##安装
在laravel5.1项目目录下

    composer require swala/server:~1.0

安装后位于项目目录下的server/

##配置
server/config.php

默认host为127.0.0.1，port为9501

其余配置项对应swoole的可用配置项，[传送门](http://wiki.swoole.com/wiki/page/274.html)

##脚本
在server目录下

* 启动

	php startup.php

* 关闭

	php shutdown.php

* 重启所有Worker

	php worker_restart.php

* 重启所有Task

	php task_restart.php

##说明
* instance/下存放服务器实例的PID记录，ini格式，它在swoole_http_server的start回调时创建，shutdown回调时销毁
* log/下存放swoole_http_server的log
* feature_test/下有三个测试脚本，如果想了解一些swoole_http_server相对于原生php所表现出来的行为特性，运行并阅读这三个脚本或许会有启发，详细说明[特性测试](docs/feature_test.md)
* doc/为文档目录

##缺陷
- 不支持文件上传
- 不支持静态资源响应

##代码许可
[MIT](LICENSE)



> Written with [StackEdit](https://stackedit.io/).
