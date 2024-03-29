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
- NOTE: startAddr is 0-based. 
If the type is bit/bool, it reads/writes the coils area of PLC, or else the holding registers areas for other types.
For holding registers (i.e. PLC area 4), each address contains a 2-byte or 16-bit WORD (rather than BYTE in S7 protocol).
For coils (i.e. PLC area 0), each address contains 1 bit.
Some tools like modscan use address format like 40001-49999 for holding registers or 00000-09999 for coils, but here they are both mapped to S{slaveId}.0-S{slaveId}.9998.

Command options:

-h : plc host. default=127.0.0.1, default port for s7 is 102, and 502 for modbus
-p : proto. Enum(S7(default), modbus)
-x : read/write using 16-based(hex) numbers.
-byteorder: byte order mode. default value 0 means Big-Endian or Network-Order or MSB(Most Significant Bit)-First mode.
1 means Litten-Endian or LSB(Least Significant Bit)-First mode.

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

If element count is less than the specified count, 0 is padded; 
or truncated if element count is greater.

	php plc-access.php -h 192.168.1.101 DB1.1:byte[20]=9,10
	set first 2 bytes to 9,10, and others to 0

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

## Programming read/write PLC

Usage (level 1): read/write once (short connection)

```php
require("common.php");
require("class/PlcAccess.php");

try {
	PlcAccess::writePlc("s7", "192.168.1.101", [
		["DB21.0:int32", 70000],
		["DB21.4:float", 3.14],
		["DB21.12.0:bit", 1]
	]);

	$res = PlcAccess::readPlc("s7", "192.168.1.101", ["DB21.0:int32", "DB21.4:float", "DB21.12.0:bit"]);
	var_dump($res);
	// on success $res=[ 70000, 3.14, 1 ]
}
catch (PlcAccessException $ex) {
	echo('error: ' . $ex->getMessage());
}
```

Usage (level 2): read and write in one connection (long connection)

```php
try {
	$plc = PlcAccess::create("s7", "192.168.1.101"); // default tcp port 102: "192.168.1.101:102"
	$plc->write([
		["DB21.0:int32", 70000],
		["DB21.4:float", 3.14],
		["DB21.12.0:bit", 1]
	]);
	$res = $plc->read(["DB21.0:int32", "DB21.4:float", "DB21.12.0:bit"]);
	// on success $res=[ 30000, 3.14, 1 ]
}
catch (PlcAccessException $ex) {
	echo('error: ' . $ex->getMessage());
}
```

**Read/write array**

```php
$plc->write([
	["DB21.0:int8[4]", [1,2,3,4]],
	["DB21.4:float[2]", [3.3, 4.4]
]);
$res = $plc->read(["DB21.0:int8[4]", "DB21.4:float[2]"]);
// $res example: [ [1,2,3,4], [3.3, 4.4] ]
```

If element count is less than the specified count, 0 is padded; 
or truncated if element count is greater.

	$plc->write([ ["DB21.0:int8[4]", [1,2]] ]); // equal to set [1,2,0,0]
	$plc->write([ ["DB21.0:int8[4]", []] ]); // all 4 clear to 0

It's ok to contain both array type and non-array type:

```php
$plc->write([
	["DB21.0:int8[4]", [3,4]],
	["DB21.4:float", 3.3],
	["DB21.8:float", 4.4]
]);
$res = $plc->read(["DB21.0:int8[4]", "DB21.4:float", "DB21.8:float"]);
// $res example: [ [1,2,3,4], 3.3, 4.4 ]
```

For modbus protocol, just change protocol to "modbus" and use modbus style address like 'S1.0:word':

	$plc = PlcAccess::create("modbus", "192.168.1.101"); // default tcp port 105
	$plc->write(["S1.0:word", 99]);
	$res = $plc->read(["S1.0:word"]);

**Read/write string**

Type `char[capacity]` is fixed-length string:

	$plc->write([ ["DB21.0:char[4]", "abcd"] ]);
	$res = $plc->read(["DB21.0:char[4]"]);

Note: the max capacity for the fixed-length string is 256.

You can write any chars including non-printing ones. It will pad 0 if the string length is not enough, or truncate to capacity if too long.

	$plc->write([ ["DB21.0:char[4]", "\x01\x02\x03"] ]); // actually write "\x01\x02\x03\x00"
	$plc->write([ ["DB21.0:char[4]", "abcdef"] ]); // actually write "abcd"

Type `string[capacity]` is variable-length string, compatible with Siemens S7 string (1 byte capacity + 1 byte length + chars). The max capacity is 254.

	$plc->write([ ["DB21.0:string[4]", "ab"] ]); // actually write "\x04\x02ab"
	$res = $plc->read(["DB21.0:string[4]"]); // result is "ab"

For variable-length string, all chars (capacity) are read and just return string with the actual length.
