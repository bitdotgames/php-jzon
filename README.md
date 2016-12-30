# PHP JZON #

This is a small PHP library for parsing JZON documents. 

This library has a pure PHP implementation and a *much* faster C version shipped as an extension. If the C extension is missing it gracefully fallbacks to the PHP implementation.

## What is JZON? ##

JZON is a *superset* of JSON which is designed to be actively edited by *humans*. 

Humans beings usually hate JSON and they have reasons for that: 

*  they always forget about extra trailing commas

*  they like to add comments

*  they wonder if array keys really need to be quoted with ""

*  they also hate when PHP native parser doesn't reveal the exact place of the parsing error

### Quick Example ###

Say, you have the following JZON document:

```
#!json

["foo",
  "bar",
 {meaning_of_life: 42,
#NOTE: no comma ahead
#      and yes comments are supported!
   hey: "bar"
  "thatscool": 1
  }
]

```

Now you can parse it this way:

```
#!php

<?php
include('jzon.inc.php');
$str = file_get_contents('example.json');
var_dump(jzon_parse($str));
```

And it should output something as follows:

```
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
```

### How do I get set up? ###

* Clone the repo

* If for some reason you can't use an extension just include **jzon.inc.php** and use **jzon_parse($str)** function. Otherwise(recommended) see steps below:

   *   Follow the standard PHP extension installation procedure: **./configure && make && make install**

   *  Add **"extension=jzon.so"** line to your php.ini

   *  Now you can include **jzon.inc.php** and use **jzon_parse($str)** function

## Credits ##

C extension is based on a bit modified code from https://github.com/KarlZylinski/jzon-c repository.