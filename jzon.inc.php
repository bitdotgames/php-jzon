<?php

if(!defined('JZON_PHP_VERSION'))
{
  define('JZON_PHP_VERSION', '0.0.3');
  //NOTE: see php_jsoh.h
  //      will be false if jzon extension is not loaded
  define('JZON_EXT_VERSION', phpversion('jzon'));
}

function jzon_show_position($p, $in, $context_chars)
{
  $pre = str_replace("\n", '', jzon_get_past_input($p, $in, $context_chars));
  $c = str_repeat('-', max(0, strlen($pre) - 1));

  return $pre . str_replace("\n", '', jzon_get_upcoming_input($p, $in, $context_chars)) . "\n" . $c . "^";
}

function jzon_get_past_input($c, $in, $context_chars)
{
  $past = substr($in, 0, $c+1);
  return (strlen($past) > $context_chars ? '...' : '') . substr($past, -$context_chars);
}

function jzon_get_upcoming_input($c, $in, $context_chars)
{
  $next = substr($in, $c+1);
  return substr($next, 0, $context_chars) . (strlen($next) > $context_chars ? '...' : '');
}

class jzonParser
{
  const ERR_CONTEXT_CHARS = 200;

  private $in;
  private $len;
  private $c = 0;

  static $ORD_SPACE;
  static $ORD_0;
  static $ORD_9;

  function __construct($input)
  {
    $this->in = $input;
    $this->c = 0;
    $this->len = strlen($this->in);

    self::$ORD_SPACE = ord(' ');
    self::$ORD_0 = ord('0');
    self::$ORD_9 = ord('9');
  }

  function parse()
  {
    $out = null;
    if($this->c < $this->len && $this->in[$this->c] == '[')
      $this->parse_array($out);
    else
      $this->parse_object($out, true);
    $this->skip_whitespace();
    if($this->c != $this->len)
      $this->_error("Trailing content");
    return $out;
  }

  private function parse_value(&$out)
  {
    $this->skip_whitespace();
    $ch = $this->in[$this->c];

    switch($ch)
    {
      case '{': $this->parse_object($out, false); break;
      case '[': $this->parse_array($out); break;
      case '"': $this->parse_string($out); break;
      case '-': $this->parse_number($out); break;
      case 'f': $this->parse_false($out); break;
      case 't': $this->parse_true($out); break;
      case 'n': $this->parse_null($out); break;
      default: 
        ord($ch) >= self::$ORD_0 && ord($ch) <= self::$ORD_9 ? 
          $this->parse_number($out) : 
          $this->_error("Not expected symbol");
    }
  }

  private function _error($error)
  {
    if($this->c < $this->len)
      throw new Exception("Parse error: $error\n" . jzon_show_position($this->c, $this->in, self::ERR_CONTEXT_CHARS));
    else
      throw new Exception("Parse error: $error\n" . jzon_show_position($this->len-1, $this->in, self::ERR_CONTEXT_CHARS));
  }

  private function skip_whitespace()
  {
    while($this->c < $this->len)
    {
      while($this->c < $this->len && (ord($ch = $this->in[$this->c]) <= self::$ORD_SPACE || $ch == ','))
        ++$this->c;

      // skip comment.
      if($this->c < $this->len && $this->in[$this->c] === '#')
      {
        ++$this->c;
        while($this->c < $this->len && $this->in[$this->c] != "\n")
          ++$this->c;
      }
      else
        break;
    }
  }

  private function parse_object(&$out, $root_object)
  {
    if($this->in[$this->c] == '{')
      ++$this->c;
    else if (!$root_object)
      $this->_error("No root object");

    $this->skip_whitespace();

    $out = array();
    // Empty object.
    if($this->in[$this->c] == '}')
    {
      ++$this->c;
      return;
    }

    while($this->c < $this->len)
    {
      $this->skip_whitespace();
      $key = $this->parse_keyname();
      $this->skip_whitespace();

      if($key === null)
        $this->_error("Bad key");
      if($this->in[$this->c] != ':')
        $this->_error("':' expected");

      ++$this->c;
      $value = null;
      $this->parse_value($value);

      $out[$key] = $value;

      $this->skip_whitespace();

      if($this->c < $this->len && $this->in[$this->c] == '}')
      {
        ++$this->c;
        break;
      }
    }
    return 0;
  }

  private function parse_array(&$out)
  {	
    if($this->in[$this->c] != '[')
      $this->_error("'[' expected");
    
    ++$this->c;
    $this->skip_whitespace();

    $out = array();

    // Empty array.
    if($this->c < $this->len && $this->in[$this->c] == ']')
    {
      ++$this->c;
      return;
    }

    while($this->c < $this->len)
    {
      $this->skip_whitespace();
      $value = null;
      $this->parse_value($value);

      $out[] = $value;

      $this->skip_whitespace();

      if($this->c < $this->len && $this->in[$this->c] == ']')
      {
        ++$this->c;
        break;
      }
    }
  }

  private function parse_keyname()
  {
    if($this->in[$this->c] == '"')
    {
      $end_char = '"';
      ++$this->c;
    }
    else
      $end_char = ':';

    $start = $this->c;

    while($this->c < $this->len)
    {
      $c = $this->in[$this->c]; 
      if($c == $end_char)
      {
        $end = $this->c;
        if($end_char == '"')
          ++$this->c;
        return substr($this->in, $start, $end - $start);
      }
      else if(ord($c) <= self::$ORD_SPACE)
        $this->_error("Found bad key character '$c' with ord=" . ord($c). " in position {$this->c}. HINT: if you ensure about file's syntax - check the file's encoding");

      ++$this->c;
    }

    return null;
  }

  private function parse_string(&$out)
  {
    $str = $this->parse_string_internal();

    if($str === null)
      $this->_error("Bad string");

    $out = $str;
  }

  private function parse_string_internal()
  {
    if($this->in[$this->c] != '"')
      return null;

    ++$this->c;
    $start = $this->c;

    $prev = '';
    while($this->c < $this->len)
    {
      if($this->in[$this->c] == '"' && $prev != '\\')
      {
        $end = $this->c;
        ++$this->c;
        return substr($this->in, $start, $end - $start);
      }
      $prev = $this->in[$this->c];
      ++$this->c;
    }

    return null;
  }

  private function parse_number(&$out)
  {
    $is_float = false;
    $start = $this->c;

    if($this->in[$this->c] == '-')
      ++$this->c;

    while($this->c < $this->len && ord($this->in[$this->c]) >= self::$ORD_0 && ord($this->in[$this->c]) <= self::$ORD_9)
      ++$this->c;

    if($this->c < $this->len && $this->in[$this->c] == '.')
    {
      $is_float = true;
      ++$this->c;

      while($this->c < $this->len && ord($this->in[$this->c]) >= self::$ORD_0 && ord($this->in[$this->c]) <= self::$ORD_9)
        ++$this->c;
    }

    if($this->c < $this->len && ($this->in[$this->c] == 'e' || $this->in[$this->c] == 'E'))
    {
      $is_float = true;
      ++$this->c;

      if($this->c < $this->len && ($this->in[$this->c] == '-' || $this->in[$this->c] == '+'))
        ++$this->c;

      while($this->c < $this->len && ord($this->in[$this->c]) >= self::$ORD_0 && ord($this->in[$this->c]) <= self::$ORD_9)
        ++$this->c;
    }

    if($is_float)
      $out = 1*substr($this->in, $start, $this->c - $start);
    else
      $out = (int)(1*substr($this->in, $start, $this->c - $start));
  }

  private function parse_true(&$out)
  {
    if(substr_compare($this->in, 'true', $this->c, 4) == 0)
    {
      $out = true;
      $this->c += 4;
    }
    else
      $this->_error("'true' expected");
  }

  private function parse_false(&$out)
  {
    if(substr_compare($this->in, 'false', $this->c, 5) == 0)
    {
      $out = false;
      $this->c += 5;
    }
    else
      $this->_error("'false' expected");
  }

  private function parse_null(&$out)
  {
    if(substr_compare($this->in, 'null', $this->c, 4) == 0)
    {
      $out = null;
      $this->c += 4;
    }
    else
      $this->_error("'null' expected");
  }
}

function jzon_parse($str)
{
  if(JZON_EXT_VERSION === JZON_PHP_VERSION)
  {
    list($ok, $err, $err_pos, $res) = jzon_parse_c($str);
    if(!$ok)
      throw new Exception($err . "\n" . jzon_show_position($err_pos, $str, jzonParser::ERR_CONTEXT_CHARS));
    return $res;
  }
  else
  {
    $p = new jzonParser($str);
    return $p->parse();
  }
}
