<?php
// $Id: decorator.inc.php,v 1.8 2007/04/05 11:09:38 mr-russ Exp $

// This group of functions and classes provides support for
// resolving values in a lazy manner (ie, as and when required)
// using the Decorator pattern.

###TODO: Better documentation!!!

// Construction functions:

function field($fieldName, $default = null): FieldDecorator
{
	return new FieldDecorator($fieldName, $default);
}

function merge(/* ... */): ArrayMergeDecorator
{
	return new ArrayMergeDecorator(func_get_args());
}

function concat(/* ... */): ConcatDecorator
{
	return new ConcatDecorator(func_get_args());
}

function callback($callback, $params = null): CallbackDecorator
{
	return new CallbackDecorator($callback, $params);
}

function ifempty($value, $empty, $full = null): IfEmptyDecorator
{
	return new IfEmptyDecorator($value, $empty, $full);
}

function url($base, $vars = null /* ... */): UrlDecorator
{
	// If more than one array of vars is given,
	// use an ArrayMergeDecorator to have them merged
	// at value evaluation time.
	if (func_num_args() > 2) {
		$v = func_get_args();
		array_shift($v);
		return new UrlDecorator($base, new ArrayMergeDecorator($v));
	}
	return new UrlDecorator($base, $vars);
}

function replace($str, $params): ReplaceDecorator
{
	return new ReplaceDecorator($str, $params);
}

// Resolving functions:

function value(&$var, $fields, $esc = null) {
	if ($var instanceof Decorator) {
		$val = $var->value($fields);
	} else {
		$val =& $var;
	}

	if (is_string($val)) {
		switch($esc) {
			case 'xml':
				return strtr($val, [
					'&' => '&amp;',
					"'" => '&apos;', '"' => '&quot;',
					'<' => '&lt;', '>' => '&gt;'
                ]);
			case 'html':
				return htmlentities($val, ENT_COMPAT, 'UTF-8');
			case 'url':
				return urlencode($val);
		}
	}
	return $val;
}

function value_xml_attr($attr, &$var, $fields): string
{
	$val = value($var, $fields, 'xml');
	if (!empty($val)) {
        return " $attr=\"$val\"";
    }

    return '';
}

function value_url(&$var, $fields) {
	return value($var, $fields, 'url');
}

// Underlying classes:

class Decorator
{
	protected $v;

	public function __construct($value) {
		$this->v = $value;
	}

	public function value($fields) {
		return $this->v;
	}
}

class FieldDecorator extends Decorator
{
	protected $d;
	protected $f;

	public function __construct($fieldName, $default = null) {
        parent::__construct(null);
		$this->f = $fieldName;
		if ($default !== null) {
            $this->d = $default;
        }
	}

	public function value($fields) {
		return isset($fields[$this->f]) ? value($fields[$this->f], $fields) : ($this->d ?? null);
	}
}

class ArrayMergeDecorator extends Decorator
{
	protected $m;

	public function __construct($arrays) {
        parent::__construct(null);
		$this->m = $arrays;
	}

	public function value($fields): array
    {
		$accum = [];
		foreach($this->m as $var) {
			$accum = array_merge($accum, value($var, $fields));
		}
		return $accum;
	}
}

class ConcatDecorator extends Decorator
{
	protected $c;

	public function __construct($values) {
        parent::__construct(null);
		$this->c = $values;
	}

	public function value($fields): string
    {
		$accum = '';
		foreach($this->c as $var) {
			$accum .= value($var, $fields);
		}
		return trim($accum);
	}
}

class CallbackDecorator extends Decorator
{
	protected $fn;
	protected $p;

	public function __construct($callback, $param = null) {
        parent::__construct(null);
		$this->fn = $callback;
		$this->p = $param;
	}

	public function value($fields) {
		return call_user_func($this->fn, $fields, $this->p);
	}
}

class IfEmptyDecorator extends Decorator
{
	protected $e;
	protected $f;

	public function __construct($value, $empty, $full = null) {
        parent::__construct($value);
		$this->e = $empty;
		if ($full !== null) {
            $this->f = $full;
        }
	}

	public function value($fields) {
		$val = value($this->v, $fields);
		if (empty($val)) {
            return value($this->e, $fields);
        }

        return isset($this->f) ? value($this->f, $fields) : $val;
    }
}

class UrlDecorator extends Decorator
{
	protected $b;
	protected $q;

	public function __construct($base, $queryVars = null) {
        parent::__construct(null);
		$this->b = $base;
		if ($queryVars !== null) {
            $this->q = $queryVars;
        }
	}

	public function value($fields) {
		$url = value($this->b, $fields);

		if ($url === false) {
            return '';
        }

		if (!empty($this->q)) {
			$queryVars = value($this->q, $fields);

			$sep = '?';
			foreach ($queryVars as $var => $value) {
				$url .= $sep . value_url($var, $fields) . '=' . value_url($value, $fields);
				$sep = '&';
			}
		}
		return $url;
	}
}

class ReplaceDecorator extends Decorator
{
	protected $s;
	protected $p;

	public function __construct($str, $params) {
        parent::__construct(null);
		$this->s = $str;
		$this->p = $params;
	}

	public function value($fields) {
		$str = $this->s;
		foreach ($this->p as $k => $v) {
			$str = str_replace($k, value($v, $fields), $str);
		}
		return $str;
	}
}
