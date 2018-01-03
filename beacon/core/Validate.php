<?php
/**
 * Created by PhpStorm.
 * User=> wj008
 * Date: 2017/12/14
 * Time: 0:26
 */

namespace beacon;


class Validate
{
    /**
     * 默认消息
     * @var array
     */
    private static $default_errors = null;


    /**
     * 代理短写
     * @var array
     */
    private static $alias = [
        'r' => 'required',
        'i' => 'integer',
        'int' => 'integer',
        'num' => 'number',
        'minlen' => 'minlength',
        'maxlen' => 'maxlength',
        'eqto' => 'equalto',
        'eq' => 'equal',
        'neq' => 'notequal'
    ];

    private static $staticFunc = [];

    private $remoteFunc = [];
    private $func = [];
    private $def_errors = [];

    public $context = null;

    public function __construct(HttpContext $context)
    {
        $this->context = $context;
    }

    /**
     * 字符串格式化输出
     * @param string $str
     * @param array $args
     * @return mixed|string
     */
    private static function format(string $str, $args = null)
    {
        if ($args === null) {
            return $str;
        }
        if (!is_array($args)) {
            $args = [$args];
        }
        if (strlen($str) == 0 || count($args) == 0) {
            return $str;
        }
        return preg_replace_callback('@\{(\d+)\}@', function ($m) use ($args) {
            $index = intval($m[1]);
            return isset($args[$index]) ? $args[$index] : '';

        });
    }

    public static function __callStatic($name, $args)
    {
        if (preg_match('@^test_(\w+)$@', $name, $match)) {
            $func = self::getFunc($match[1]);
            if ($func !== null) {
                $out = call_user_func_array($func, $args);
                return $out;
            }
        }
        throw new Exception('Error Method!');
    }

    /**
     * @param $type
     * @return null|string
     */
    public static function getFunc($type)
    {
        $rtype = self::getRealType($type);
        if (method_exists(self::class, 'test_' . $rtype)) {
            return self::class . '::' . 'test_' . $rtype;
        }
        if (isset(self::$staticFunc[$rtype])) {
            return self::$staticFunc[$rtype];
        }
        return null;
    }

    /**
     * @param $type
     * @return mixed
     */
    public static function getRealType($type)
    {
        if (isset(self::$alias[$type])) {
            return self::$alias[$type];
        }
        return $type;
    }

    /**
     * @param $type
     * @param $func
     * @param string $error
     */
    public static function regFnuc($type, $func, $error = '格式错误')
    {
        if (is_string($func)) {
            $func = self::getRealType($func);
            self::$alias[$type] = $func;
            return;
        }
        self::$default_errors[$type] = $error;
        self::$staticFunc[$type] = $func;
    }

    /**
     * 判断是否为空
     * @param string $val
     * @return boolean
     */
    public static function test_required($val)
    {
        if (is_array($val)) {
            return count($val) != 0;
        }
        return !empty($val);
    }

    /**
     * @param $val
     * @return int
     */
    public static function test_email($val)
    {
        return !!preg_match('/^(\w+[-_\.]?)*\w+@(\w+[-_\.]?)*\w+\.\w{2,6}([\.]\w{2,6})?$/', $val);
    }

    public static function test_url($val, $dc = false)
    {
        if ($dc && $val == '#') {
            return true;
        }
        return !!preg_match('/^(http|https|ftp):\/\/\w+\.\w+/i', $val);
    }

    public static function test_equal($val, $str)
    {
        return strval($val) == strval($str);
    }

    public static function test_notequal($val, $str)
    {
        return strval($val) != strval($str);
    }

    public static function test_equalto($context, $val, $key)
    {
        if (!empty($key) && preg_match('/^#(\w+)/i', $key, $m) != 0) {
            $name = isset($m[1]) ? $m[1] : '';
            if (!empty($name)) {
                $str = $context->getRequest()->param($name . ':s');
                if (!empty($str)) {
                    return strval($val) == $str;
                }
            }
        }
        return true;
    }

    public static function test_mobile($val)
    {
        return !!preg_match('/^1[34578]\d{9}$/', $val);
    }

    public static function test_idcard($val)
    {
        return !!preg_match('/^[1-9]\d{5}(19|20)\d{2}(((0[13578]|1[02])([0-2]\d|30|31))|((0[469]|11)([0-2]\d|30))|(02[0-2][0-9]))\d{3}(\d|X|x)$/', $val);
    }

    public static function test_user($val)
    {
        return !!preg_match('/^[a-z]\w*$/i', $val);
    }

    public static function test_regex($val, $re)
    {
        $str = '#' . str_replace('#', '\#', $re) . '#';
        $rt = preg_match($str, $val);
        if ($rt === FALSE) {
            throw new Exception('验证器正则表达式错误!');
        }
        return $rt != 0;
    }

    public static function test_number($val)
    {
        return !!preg_match('/^[\-\+]?((\d+(\.\d*)?)|(\.\d+))$/', $val);
    }

    public static function test_integer($val)
    {
        return !!preg_match('/^[\-\+]?\d+$/', $val);
    }

    public static function test_max($val, $num, $noeq = false)
    {
        if ($noeq) {
            return floatval($val) < floatval($num);
        } else {
            return floatval($val) <= floatval($num);
        }
    }

    public static function test_min($val, $num, $noeq = false)
    {
        if ($noeq) {
            return floatval($val) > floatval($num);
        } else {
            return floatval($val) >= floatval($num);
        }
    }

    public static function test_range($val, $min, $max, $noeq = false)
    {
        return self::rule_range($val, $min, $noeq) && self::test_min($val, $max, $noeq);
    }

    public static function test_minlength($val, $len)
    {
        return mb_strlen($val, 'UTF-8') >= intval($len);
    }

    public static function test_maxlength($val, $len)
    {
        return mb_strlen($val, 'UTF-8') <= intval($len);
    }

    public static function test_rangelength($val, $minlen, $maxlen)
    {
        return self::test_minlength($val, $minlen) && self::test_maxlength($val, $maxlen);
    }

    public static function test_money($val)
    {
        return preg_match('/^[\-\+]{0,1}\d+[\.]\d{1,2}$/', $val) != 0 || self::test_integer($val);
    }

    public static function test_date($val)
    {
        return !!preg_match('/^\d{4}-\d{1,2}-\d{1,2}(\s\d{1,2}(:\d{1,2}(:\d{1,2})?)?)?$/', $val);
    }

    public function addRemoute(string $name, $func)
    {
        $this->remoteFunc[$name] = $func;
    }

    public function addFunc(string $type, $func, string $error = null)
    {
        $this->func[$type] = $func;
        if (!empty($error)) {
            $this->def_errors[$type] = $error;
        }
    }

    public function checkField(Field $field)
    {
        if (Validate::$default_errors == null) {
            Validate::$default_errors = Config::get('form.validate_default_errors', []);
        }
        $name = $field->name;
        if (!empty($field->error)) {
            return false;
        }
        if ($field->dataValOff) {
            return true;
        }
        $rules = $field->dataVal;
        if ($rules == null) {
            return true;
        }
        $errors = $field->dataValMsg;
        if ($errors == null) {
            $errors = [];
        }
        $tempErrors = [];
        foreach ($errors as $type => $err) {
            $rtype = Validate::getRealType($type);
            $tempErrors[$rtype] = $err;
        }
        $errors = $tempErrors;
        $tempRules = [];
        foreach ($rules as $type => $args) {
            $rtype = Validate::getRealType($type);
            $tempRules[$rtype] = $args;
        }
        $rules = $tempRules;
        $value = $field->value;
        //验证非空
        if ($rules['required']) {
            $func = isset($this->func['required']) ? $this->func['required'] : Validate::getFunc('required');
            $r = call_user_func_array($func, [$value]);
            if (!$r) {
                $err = isset($this->def_errors['required']) ? $this->def_errors['required'] : (isset($errors['required']) ? $errors['required'] : (isset(Validate::$default_errors['required']) ? Validate::$default_errors['required'] : '必填项'));
                $field->error = $err;
                return false;
            }
            unset($rules['required']);
        }
        if (strlen($value) > 0 || $rules['force']) {
            unset($rules['force']);
            foreach ($rules as $type => $args) {
                if (!is_array($args)) {
                    $args = [$args];
                }
                $xargs = array_slice($args, 0);
                array_unshift($args, $value);
                if ($type == 'equalto') {
                    array_unshift($args, $this->context);
                }
                $func = null;
                if ($type == 'remote') {
                    $func = isset($field->remoteFunc) ? $field->remoteFunc : (isset($this->remoteFunc[$name]) ? $this->remoteFunc[$name] : null);
                } else {
                    $func = isset($this->func[$type]) ? $this->func[$type] : Validate::getFunc($type);
                }
                if ($func == null) {
                    continue;
                }
                $out = call_user_func_array($func, $args);
                if (is_bool($out)) {
                    if ($out) {
                        continue;
                    }
                    $err = isset($this->def_errors[$type]) ? $this->def_errors[$type] : (isset($errors[$type]) ? $errors[$type] : (isset(Validate::$default_errors[$type]) ? Validate::$default_errors[$type] : '格式错误'));
                    $field->error = Validate::format($err, $xargs);
                    return false;
                }
                if (is_array($out) && isset($out['status']) && !$out['status'] && !empty($out['error'])) {
                    $field->error = Validate::format($out['error'], $xargs);
                    return false;
                }

            }
        }
        return true;
    }
}