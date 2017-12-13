<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 1:43
 */

namespace core;


class Route
{

    private static $cache_uris = null;
    private static $routeMap = [];
    private static $routePath = null;
    private static $cachePath = null;
    private static $route = null;

    /**
     * 设置路由配置文件路径
     * @param string $path
     */
    public static function setRoutePath(string $path)
    {
        self::$routePath = Utils::path($path);
    }

    /**
     * 注册路由
     * @param string $name
     */
    public static function register(string $name)
    {
        if (empty($name)) {
            return;
        }
        if (empty(self::$routePath)) {
            self::$routePath = Utils::path(ROOT_DIR, 'config');
        }
        $filepath = Utils::path(self::$routePath, $name . '.route.php');
        if (!file_exists($filepath)) {
            return;
        }
        $idata = require $filepath;
        $idata['name'] = $name;
        $idata['base'] = rtrim(empty($idata['base']) ? '' : $idata['base'], '/');
        $idata['base_match'] = '@^' . preg_quote($idata['base'], '@') . '(/.*)?$@i';
        self::$routeMap[$name] = $idata;
    }

    /**
     * 提取uri
     * @param string $url
     * @return mixed|null
     */
    private static function matchUrl(string $url)
    {
        foreach (self::$routeMap as $key => $item) {
            if (preg_match($item['base_match'], $url, $m)) {
                $item['uri'] = empty($m[1]) ? '' : $m[1];
                return $item;
            }
        }
        return null;
    }

    /**
     * 获取路由解析数据
     * @param null $name
     * @return null
     */
    public static function get($name = null)
    {
        if (self::$route == null) {
            return null;
        }
        if ($name == null) {
            return self::$route;
        }
        if (isset(self::$route[$name])) {
            return self::$route[$name];
        }
        return null;
    }

    /**
     * 解析URL路径
     * @param string $url
     * @return array|null
     */
    public static function parse(string $url)
    {
        $url_temp = parse_url($url);
        $url = $url_temp['path'];
        $idata = self::matchUrl($url);
        if ($idata == null) {
            return null;
        }
        //路由路径
        $uri = empty($idata['uri']) ? '/' : $idata['uri'];
        $name = $idata['name'];
        $arg = [
            'app' => $name,
            'base' => $idata['base'],
            'ctl' => '',
            'act' => '',
        ];
        if (!isset($idata['rules']) || !is_array($idata['rules'])) {
            return null;
        }
        foreach ($idata['rules'] as $preg => $item) {
            if (preg_match($preg, $uri, $m)) {
                if (!is_array($item)) {
                    continue;
                }
                foreach ($item as $key => $val) {
                    $tval = null;
                    if (is_string($val)) {
                        $tval = preg_replace_callback('@\$(\d+)@', function ($m2) use ($m) {
                            return isset($m[$m2[1]]) ? $m[$m2[1]] : '';
                        }, $val);
                    } elseif (is_array($val)) {
                        $tval = preg_replace_callback('@\$(\d+)@', function ($m2) use ($m, $val) {
                            return isset($m[$m2[1]]) ? $m[$m2[1]] : $val['def'];
                        }, $val['map']);
                    } else {
                        continue;
                    }
                    $arg[$key] = $tval;
                }
                break;
            }
        }
        if (empty($arg['ctl'])) {
            return null;
        }
        $arg['ctl'] = strtolower($arg['ctl']);
        $arg['act'] = strtolower($arg['act']);
        self::$route = $arg;
        return $arg;
    }

    /**
     * 反解析URL
     * @param string $app
     * @param string $pathname
     * @param array $query
     * @return mixed|string
     */
    public static function resolve(string $app, string $pathname = '', array $query = [])
    {

        if (empty($app)) {
            return '';
        }
        $temp = [];
        foreach ($query as $key => $val) {
            array_push($temp, $key . '={' . $key . '}');
        }
        $hash = $app . ':' . $pathname . '?' . join('&', $temp);
        $hash = isset($hash[80]) ? md5($hash) : $hash;
        if (empty(self::$cachePath)) {
            self::$cachePath = Utils::path(ROOT_DIR, 'runtime');
        }
        $filepath = Utils::path(self::$cachePath, 'route.cache.php');
        if (self::$cache_uris == null) {
            if (file_exists($filepath)) {
                self::$cache_uris = require $filepath;
            } else {
                self::$cache_uris = [];
            }
        }
        //使用了缓存
        if (isset(self::$cache_uris[$hash])) {
            //  echo '缓存';
            $temp_url = self::$cache_uris[$hash];
            $temp_url = preg_replace_callback('@\{(\w+)\}@', function ($m) use ($query) {
                $key = $m[1];
                return isset($query[$key]) ? urlencode($query[$key]) : '';
            }, $temp_url);
            return $temp_url;
        }
        $idata = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : null;
        if ($idata == null) {
            return '';
        }
        $ctl = '';
        $act = '';
        if (!empty($pathname)) {
            if (preg_match('@^\/?(\w+)(?:\/(\w+))?@', $pathname, $mth)) {
                $ctl = Utils::toUnder($mth[1]);
                if (isset($mth[2])) {
                    $act = Utils::toUnder($mth[2]);
                }
            }
        }
        $args = [];
        foreach ($query as $key => $val) {
            $args[$key] = '{' . $key . '}';
        }
        $base = rtrim(empty($idata['base']) ? '' : $idata['base'], '/');
        if (!isset($idata['resolve']) && !is_callable($idata['resolve'])) {
            return '';
        }
        $out_url = '';
        $info = $idata['resolve']($ctl, $act, $args);
        if (is_string($info)) {
            $out_url = preg_replace_callback('@\{(ctl|act)\}@', function ($m) use ($ctl, $act) {
                if ($m[1] == 'ctl') {
                    return $ctl;
                }
                if ($m[1] == 'act') {
                    return $act;
                }
            }, $info);
            if (preg_match_all('@\{(\w+)\}@', $out_url, $mts)) {
                foreach ($mts[1] as $mt) {
                    $key = $mt;
                    unset($args[$key]);
                }
            }
        } elseif (is_array($info)) {
            $out_url = preg_replace_callback('@\{(ctl|act)\}@', function ($m) use ($ctl, $act) {
                if ($m[1] == 'ctl') {
                    return $ctl;
                }
                if ($m[1] == 'act') {
                    return $act;
                }
            }, $info[0]);
            $args = $info[1];
            if (!isset($info[2]) || $info[2] == false) {
                if (preg_match_all('@\{(\w+)\}@', $out_url, $mts)) {
                    foreach ($mts[1] as $mt) {
                        $key = $mt;
                        unset($args[$key]);
                    }
                }
            }
        }
        $queryStr = [];
        foreach ($args as $key => $val) {
            array_push($queryStr, $key . '={' . $key . '}');
        }
        $temp_url = $base . $out_url;
        if (count($queryStr) > 0) {
            $temp_url .= '?' . join('&', $queryStr);
        }
        self::$cache_uris[$hash] = $temp_url;
        @file_put_contents($filepath, '<?php return ' . var_export(self::$cache_uris, true) . ';');
        // echo '创建缓存';
        $temp_url = preg_replace_callback('@\{(\w+)\}@', function ($m) use ($query) {
            $key = $m[1];
            return isset($query[$key]) ? urlencode($query[$key]) : '';
        }, $temp_url);

        return $temp_url;
    }


    public static function run($url = null)
    {
        Request::instance()->setContentType('html');
        if ($url === null) {
            if (isset($_SERVER['PATH_INFO'])) {
                self::parse($_SERVER['PATH_INFO']);
            } else {
                self::parse($_SERVER['REQUEST_URI']);
            }
        } else {
            self::parse($url);
        }
        if (self::$route == null) {
            throw new \Exception('未初始化路由参数');
        }
        if (empty(self::$route['app'])) {
            throw new \Exception('不存在的路径');
        }
        if (empty(self::$route['ctl'])) {
            throw new \Exception('不存在的控制器');
        }
        if (empty(self::$route['act'])) {
            throw new \Exception('不存在的控制器方法');
        }
        $app = self::$route['app'];
        $ctl = Utils::toCamel(self::$route['ctl']);
        $act = Utils::toCamel(self::$route['act']);
        $act = lcfirst($act);
        $data = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : [];
        if (empty($data['path'])) {
            throw new \Exception('没有设置应用目录');
        }
        $config = Utils::path(ROOT_DIR, $data['path'], 'config.php');
        if (file_exists($config)) {
            $cfgData = Config::loadFile($config);
            foreach ($cfgData as $key => $val) {
                Config::set($key, $val);
            }
        }
        $namespace = isset($data['namespace']) ? $data['namespace'] : $data['path'];
        $class = trim(str_replace(['/', '\\'], '\\', $namespace), '\\') . '\\controller\\' . $ctl;
        if (class_exists($class)) {
            try {
                $oReflectionClass = new \ReflectionClass($class);
                $method = $oReflectionClass->getMethod($act . 'Action');
                if ($method->isPublic()) {
                    $params = $method->getParameters();
                    $args = [];
                    if (count($params) > 0) {
                        $request = Request::instance();
                        foreach ($params as $param) {
                            $name = $param->getName();
                            $type = 'any';
                            if (is_callable([$param, 'hasType'])) {
                                if ($param->hasType()) {
                                    $refType = $param->getType();
                                    if ($refType != null) {
                                        $type = $refType->getName();
                                        $type = empty($type) ? 'any' : $type;
                                    }
                                }
                            }
                            if ($type == 'any') {
                                if (is_callable([$param, 'getClass'])) {
                                    $refType = $param->getClass();
                                    if ($refType != null) {
                                        $type = $refType->getName();
                                        $type = empty($type) ? 'any' : $type;
                                    }
                                }
                            }
                            $def = null;
                            //如果有默认值
                            if ($param->isOptional()) {
                                $def = $param->getDefaultValue();
                                if ($type == 'any') {
                                    $type = gettype($def);
                                }
                            }

                            switch ($type) {
                                case 'bool':
                                case 'boolean':
                                    $args[] = $request->param($name . ':b', $def);
                                    break;
                                case 'int':
                                case 'integer':
                                    $val = $request->param($name . ':s', $def);
                                    if (preg_match('@[+-]?\d*\.\d+@', $val)) {
                                        $args[] = $request->param($name . ':f', $def);
                                    } else {
                                        $args[] = $request->param($name . ':i', $def);
                                    }
                                    break;
                                case 'double':
                                case 'float':
                                    $args[] = $request->param($name . ':f', $def);
                                    break;
                                case 'string':
                                    $args[] = $request->param($name . ':s', $def);
                                    break;
                                case 'array':
                                    $args[] = $request->param($name . ':a', $def);
                                    break;
                                case '\core\Request':
                                case 'core\Request':
                                    $args[] = $request;
                                    break;
                                default :
                                    $args[] = $request->param($name, $def);
                                    break;
                            }
                        }
                    }
                    $example = new $class();
                    if (method_exists($example, 'initialize')) {
                        $example->initialize();
                    }
                    $out = $method->invokeArgs($example, $args);
                    if (Request::instance()->getContentType() == 'application/json') {
                        echo json_encode($out);
                        exit;
                    } else {
                        if (is_array($out)) {
                            Request::instance()->setContentType('json');
                            echo json_encode($out);
                            exit;
                        } else {
                            echo $out;
                        }
                    }
                }
            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            throw new \Exception('不存在的控制器');
        }
    }
}

