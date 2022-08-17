# plc-access

PLC读写库及命令行工具。

支持西门子s7协议和modbus协议(modbus-tcp)。

关联项目：

- [s7plc](https://github.com/skyshore2001/s7plc/): 超简单的PHP语言的西门子S7系列PLC读写模块
- [plcserver](https://github.com/skyshore2001/plcserver/): PLC访问中间件，通过web接口来对PLC进行读、写、**监控值变化并回调**。

## 命令行工具plc-access.php

读S7 PLC:

	php plc-access.php -h 192.168.1.101 DB1.1:int8

写S7 PLC: （写后会自动读一次）

	php plc-access.php -h 192.168.1.101 DB1.1:uint8=200

读、写，使用16进制：

	php plc-access.php DB21.1:uint8=ff  DB21.1.0:bit DB21.1.7:bit  -x

s7协议地址格式为：

- DB{dbNumber}.{startAddr}:{type}
- DB{dbNumber}.{startAddr}.{bitOffset}:bit
- array format:
  - DB{dbNumber}.{startAddr}:{type}[amount]
  - DB{dbNumber}.{startAddr}.{bitOffset}:bit[amount]

**s7地址格式对照**

- DB21.DBB4 (byte): DB21.4:int8 (-127~127) or DB21.4:uint8 (0~256)
- DB21.DBW4 (word): DB21.4:int16 (-32767~32768) or DB21.4:uint16 (0~65536)
- DB21.DBD4 (dword): DB21.4:int32 or DB21.4:uint32 or DB21.4:float
- DB21.DBX4.0 (bit): DB21.4.0:bit

modbus-tcp协议读、写：（加-t modbus参数）

	php plc-access.php -t modbus S1.0:word[2]=20000,40000
	php plc-access.php -t modbus S1.0:word[2]

modbus协议地址格式为：

- S{slaveId}.{startAddr}:{type}
- 注意：startAddr为0开始，以字(word)为单位(对比s7协议是字节为单位)

参数选项：

-h : plc host. default=127.0.0.1:102
-p : proto. Enum(s7(default), modbus)
-x : 写时以16进制设置，读后显示16进制数据。

支持的类型如下：

- int8
- uint8/byte
- int16/int
- uint16/word
- int32/dint
- uint32/dword
- bit/bool
- float
- char

数组读写：

	php plc-access.php -h 192.168.1.101 DB1.1:byte[2]=125,225

字符读写：

	php plc-access.php DB21.0:char[4]=A,B,,C
	php plc-access.php DB21.0:char[4]
	"AB\u0000C"

	php plc-access.php DB21.0:char[2]=A,B DB21.0:uint8[2]
	"AB", [65,66]

	php plc-access.php DB21.0:uint32 -x
	"x41420043"

## PHP编程示例

[读写S7Plc示例](https://github.com/skyshore2001/s7plc/)如下：

方式一：单次读写（每次调用发起一次TCP短连接）

```php
require("common.php");
require("S7Plc.php");
try {
	S7Plc::writePlc("192.168.1.101", [["DB21.0:int32", 70000], ["DB21.4:float", 3.14], ["DB21.12.0:bit", 1]]);

	$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int32", "DB21.4:float", "DB21.12.0:bit"]);
	var_dump($res);
	// on success $res=[ 70000, 3.14, 1 ]
}
catch (S7PlcException $ex) {
	echo('error: ' . $ex->getMessage());
}
```

方式二：连续读写（维持一个TCP长连接）

```php
try {
	$plc = new S7Plc("192.168.1.101"); // default tcp port 102: "192.168.1.101:102"
	$plc->write([["DB21.0:int32", 70000], ["DB21.4:float", 3.14], ["DB21.12.0:bit", 1]]);
	$res = $plc->read(["DB21.0:int32", "DB21.4:float", "DB21.12.0:bit"]);
	// on success $res=[ 30000, 3.14, 1 ]
}
catch (S7PlcException $ex) {
	echo('error: ' . $ex->getMessage());
}
```
**Read/write array**

	$plc->write(["DB21.0:int8[4]", "DB21.4:float[2]"], [ [1,2,3,4], [3.3, 4.4] ]);
	$res = $plc->read(["DB21.0:int8[4]", "DB21.4:float[2]"]);
	// $res example: [ [1,2,3,4], [3.3, 4.4] ]

OR

	S7Plc::writePlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float[2]"], [ [1,2,3,4], [3.3, 4.4] ]);
	$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float[2]"]);

It's ok to contain both array and elements:

	S7Plc::writePlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"], [ [1,2,3,4], 3.3, 4.4 ]);
	$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"]);
	// $res example: [ [1,2,3,4], 3.3, 4.4 ]

如果是Modbus协议，换成包含ModbusClient.php文件和使用ModbusClient类即可，接口相似。

