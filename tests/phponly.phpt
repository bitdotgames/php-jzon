--TEST--
test for PHP implementation
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

$p = new jzonParser($str);
var_dump($p->parse());
?>
--EXPECT--
array(3) {
  [0]=>
  string(3) "foo"
  [1]=>
  string(3) "bar"
  [2]=>
  array(3) {
    ["meaning_of_life"]=>
    int(42)
    ["hey"]=>
    string(3) "bar"
    ["thatscool"]=>
    int(1)
  }
}
