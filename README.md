# plc-access

PLC read/write lib and command line tool.

[中文文档](README-zh.md)

Support protocols:

- siemens s7
- modbus-tcp

Reference:

- [s7plc](https://github.com/skyshore2001/s7plc/): A php lib to read/write Siements S7 PLC series like S7-1200/S7-1500 via S7 protocol.
- [plcserver](https://github.com/skyshore2001/plcserver/): PLC access service that supports read/write/**watch and callback** via web service

## command-line tool: plc-access.php

read S7 PLC:

	php plc-access.php -h 192.168.1.101 DB1.1:int8

write S7 PLC: (it reads just after writes)

	php plc-access.php -h 192.168.1.101 DB1.1:uint8=200

read/write via 16-based numbers: (-x)

	php plc-access.php DB21.1:uint8=ff  DB21.1.0:bit DB21.1.7:bit  -x

Siemens s7 address format:

- DB{dbNumber}.{startAddr}:{type}
- DB{dbNumber}.{startAddr}.{bitOffset}:bit
- array format:
  - DB{dbNumber}.{startAddr}:{type}[amount]
  - DB{dbNumber}.{startAddr}.{bitOffset}:bit[amount]

**Address Mapping Example**

- DB21.DBB4 (byte): DB21.4:int8 (-127~127) or DB21.4:uint8 (0~256)
- DB21.DBW4 (word): DB21.4:int16 (-32767~32768) or DB21.4:uint16 (0~65536)
- DB21.DBD4 (dword): DB21.4:int32 or DB21.4:uint32 or DB21.4:float
- DB21.DBX4.0 (bit): DB21.4.0:bit

read/write via modbus-tcp (add param -t: modbus)

	php plc-access.php -t modbus S1.0:word[2]=20000,40000
	php plc-access.php -t modbus S1.0:word[2]

modbus address format:

- S{slaveId}.{startAddr}:{type}
- NOTE: startAddr is 0-based. Each address contains a WORD (rather than BYTE in s7 protocol).

Command options:

-h : plc host. default=127.0.0.1:102
-p : proto. Enum(s7(default), modbus)
-x : read/write using 16-based(hex) numbers.

Support types:

- int8
- uint8/byte
- int16/int
- uint16/word
- int32/dint
- uint32/dword
- bit/bool
- float
- char

**read/write array:**

	php plc-access.php -h 192.168.1.101 DB1.1:byte[2]=125,225

**read/write char array:**

	php plc-access.php DB21.0:char[4]=A,B,,C
	php plc-access.php DB21.0:char[4]
	"AB\u0000C"

	php plc-access.php DB21.0:char[2]=A,B DB21.0:uint8[2]
	"AB", [65,66]

	php plc-access.php DB21.0:uint32 -x
	"x41420043"

## Programming read/write PLC

Let's take [S7Plc read/write](https://github.com/skyshore2001/s7plc/) as example:

Usage (level 1): read/write once (short connection)

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

Usage (level 2): read and write in one connection (long connection)

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

```php
$plc->write(["DB21.0:int8[4]", "DB21.4:float[2]"], [ [1,2,3,4], [3.3, 4.4] ]);
$res = $plc->read(["DB21.0:int8[4]", "DB21.4:float[2]"]);
// $res example: [ [1,2,3,4], [3.3, 4.4] ]
```

OR

```php
S7Plc::writePlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float[2]"], [ [1,2,3,4], [3.3, 4.4] ]);
$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float[2]"]);
```

It's ok to contain both array and elements:

```php
S7Plc::writePlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"], [ [1,2,3,4], 3.3, 4.4 ]);
$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"]);
// $res example: [ [1,2,3,4], 3.3, 4.4 ]
```

For modbus protocol, just include "ModbusClient.php" and use class "ModbusClient" instead.

