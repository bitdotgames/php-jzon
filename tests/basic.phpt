--TEST--
jzon_parse($str) - basic tests for jzon_parse
--FILE--
<?php
include(dirname(__FILE__) . '/../jzon.inc.php');
$str = '["foo", "bar"]';
var_dump(jzon_parse($str));
?>
--EXPECT--
array(2) {
  [0]=>
  string(3) "foo"
  [1]=>
  string(3) "bar"
}
