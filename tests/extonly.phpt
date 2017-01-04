--TEST--
test for C extension
--FILE--
<?php
include(dirname(__FILE__) . '/../jzon.inc.php');
$str = <<<EOD
["foo", 
  "bar", 
 {meaning_of_life: 42, 
#NOTE: no comma ahead
#      and yes comments are supported!
   hey: "bar" 
  "thatscool": 1
  }
]
EOD;

list($ok, $err, $err_pos, $res) = jzon_parse_c($str);
var_dump($res);
?>
--EXPECT--
array(3) {
  [0]=>
  string(3) "foo"
  [1]=>
  string(3) "bar"
  [2]=>
  array(3) {
    ["thatscool"]=>
    int(1)
    ["meaning_of_life"]=>
    int(42)
    ["hey"]=>
    string(3) "bar"
  }
}
