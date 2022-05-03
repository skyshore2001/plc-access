# plc-access

PLC读写库及命令行工具。

支持西门子s7协议和modbus协议(modbus-tcp)。

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

