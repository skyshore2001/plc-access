# plc-access

PLC read/write lib and command line tool.

[中文文档](README-zh.md)

Support protocols:

- Siemens S7
- Modbus-Tcp

Reference:

- [s7plc](https://github.com/skyshore2001/s7plc/): A php lib to read/write Siemens S7 PLC series like S7-1200/S7-1500 via S7 protocol.
- [plcserver](https://github.com/skyshore2001/plcserver/): PLC access service that supports read/write/**watch and callback** via web service

## command-line tool: plc-access.php

read S7 PLC:

	php plc-access.php -h 192.168.1.101 DB1.1:int8

write S7 PLC: (it reads just after writes)

	php plc-access.php -h 192.168.1.101 DB1.1:uint8=200

write using hex digit (16-based number) with C-language "0x" style:

	php plc-access.php DB21.1:uint8=0xff
	(write 255)

option "-x": show result using hex digit

	php plc-access.php DB21.1:uint8=0xff -x
	(0xff)

	php plc-access.php DB21.1.0:bit DB21.1.7:bit  -x
	(0x01, 0x00)

Siemens S7 address format:

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
- NOTE: startAddr is 0-based. Each address contains a WORD (rather than BYTE in S7 protocol).

Command options:

-h : plc host. default=127.0.0.1:102
-p : proto. Enum(S7(default), modbus)
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
- char[capacity]
- string[capacity]

**read/write array:**

	php plc-access.php -h 192.168.1.101 DB1.1:byte[2]=125,225

**read/write char/string:**

`char[capacity]` type is fixed-length string. Write function will pad 0 if `length<capacity`, or truncate to capacity if `length>capacity`.
Read function read fixed length that is equal to capacity.

	php plc-access.php DB21.0:char[4]="AB"
	php plc-access.php DB21.0:char[4]
	"AB\x00\x00"

`string[capacity]` type is variable-length string that is compatible with Siemens S7 string (1 byte capacity + 1 byte length + chars).
Write function just writes actual length (truncate to capacity if `length>capacity`).
Read function read all chars to truncate to actual length.

	php plc-access.php DB21.0:string[4]="AB"
	php plc-access.php DB21.0:string[4]
	"AB"

You can use C-language "\x" style:

	php plc-access.php DB21.0:char[4]="A\x00\x01B"
	php plc-access.php DB21.0:char[4]
	"A\x00\x01B"

We can use byte[4] or uint16[2] or uint32 to reach the same effect:

	php plc-access.php DB21.0:byte[4]=0x61,0,1,0x62 -x
	[0x61, 0x00, 0x01, 0x62]

	php plc-access.php DB21.0:uint16[2]=0x6100,0x0162 DB21.0:byte[4] -x

	php plc-access.php DB21.0:uint32=0x61000162 DB21.0:byte[4] -x

NOTE: It depends to the byte order for uint16/uint32. This example only applies to the big-endian machine like Siemens S7 PLC.

- TODO: some option to specify byte order.

## Programming read/write PLC

Let's take [S7Plc read/write](https://github.com/skyshore2001/s7plc/) as example:

Usage (level 1): read/write once (short connection)

```php
require("common.php");
require("class/S7Plc.php");
// require("class/ModbusClient.php"); // for modbus-tcp

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

It's ok to contain both array type and non-array type:

```php
S7Plc::writePlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"], [ [1,2,3,4], 3.3, 4.4 ]);
$res = S7Plc::readPlc("192.168.1.101", ["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"]);
// $res example: [ [1,2,3,4], 3.3, 4.4 ]
```

For modbus protocol, just include "ModbusClient.php" and use class "ModbusClient" instead.

**Read/write string**

Type `char[capacity]` is fixed-length string:

	$plc = new S7Plc("192.168.1.101"); // default tcp port 102: "192.168.1.101:102"
	$plc->write(["DB21.0:char[4]"], [ ["abcd"] ]);
	$res = $plc->read(["DB21.0:char[4]"]);

Note: the max capacity for the fixed-length string is 256.

You can write any chars including non-printing ones. It will pad 0 if the string length is not enough, or truncate to capacity if too long.

	$plc->write(["DB21.0:char[4]"], [ ["\x01\x02\x03"] ]); // actually write "\x01\x02\x03\x00"
	$plc->write(["DB21.0:char[4]"], [ ["abcdef"] ]); // actually write "abcd"

Type `string[capacity]` is variable-length string, compatible with Siemens S7 string (1 byte capacity + 1 byte length + chars). The max capacity is 254.

	$plc->write(["DB21.0:string[4]"], [ ["ab"] ]); // actually write "\x04\x02ab"
	$res = $plc->read(["DB21.0:string[4]"]); // result is "ab"

For variable-length string, all chars (capacity) are read and just return string with the actual length.
