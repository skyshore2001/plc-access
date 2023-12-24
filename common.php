<?php
define("T_HOUR", 3600);
define("T_DAY", 24*T_HOUR);
define("FMT_DT", "Y-m-d H:i:s");
define("FMT_D", "Y-m-d");

function mypack($data, $format = null)
{
	if (is_string($format)) 
		return pack($format, $data);

	if (is_null($format)) {
		$cnt = count($data);
		assert($cnt % 2 == 0);
		$params = [];
		$format0 = "";
		for ($i=0; $i<$cnt; $i+=2) {
			$format0 .= $data[$i];
			$params[] = $data[$i+1];
		}
		array_unshift($params, $format0);
		return call_user_func_array("pack", $params);
	}

	$cnt = count($format);
	assert($cnt % 2 == 0);
	$params = [];
	$format0 = "";
	for ($i=0; $i<$cnt; $i+=2) {
		$format0 .= $format[$i];
		$params[] = $data[$format[$i+1]];
	}
	array_unshift($params, $format0);
	return call_user_func_array("pack", $params);
}

function myunpack($packData, $format)
{
	if (is_string($format))
		return unpack($format, $packData);
	$cnt = count($format);
	assert($cnt % 2 == 0);
	$format0 = null;
	for ($i=0; $i<$cnt; $i+=2) {
		if ($format0 !== null) {
			$format0 .= '/';
		}
		$format0 .= $format[$i] . $format[$i+1];
	}
	return unpack($format0, $packData);
}

function arrCopy(&$ret, $arr, $fields=null)
{
	if ($ret == null)
		$ret = [];
	if ($fields == null) {
		foreach ($arr as $k=>$v) {
			if (is_int($k)) {
				$ret[] = $v;
			}
			else {
				$ret[$k] = $v;
			}
		}
		return;
	}
	foreach ($fields as $f) {
		if (is_array($f))
			@$ret[$f[0]] = $arr[$f[1]];
		else
			@$ret[$f] = $arr[$f];
	}
}

class Guard
{
	private $fn;
	function __construct($fn) {
		assert(is_callable($fn));
		$this->fn = $fn;
	}
	function __destruct() {
		call_user_func($this->fn);
	}
}

