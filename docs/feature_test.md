#特性测试
***

<table>
	<tr>
		<td>测试脚本</td>
		<td>测试目的</td>
	</tr>
	<tr>
		<td>test_resource</td><td>检测swoole的全局资源常驻性</td>
	</tr>
	<tr>
		<td>test_stand_alone</td><td>检测worker的全局资源独立性</td>
	</tr>
	<tr>
		<td>test_grab</td><td>检测worker的不可抢占性</td>
	</tr>
</table>

##运行
***
- 启动
	php {$script_name} &

- 关闭
	kill -15 {$pid}


> Written with [StackEdit](https://stackedit.io/).
