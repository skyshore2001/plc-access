# plc-access

PLC read/write lib and command line tool.

Support protocols:

- siemens s7
- modbus-tcp

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

