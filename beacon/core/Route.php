<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 1:43
 */

namespace beacon;

defined('DEV_DEBUG') or define('DEV_DEBUG', false);
defined('IS_CGI') or define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' ? true : false);
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
defined('IS_WIN') or define('IS_WIN', strstr(PHP_OS, 'WIN') ? true : false);

class RouteEndError extends \Error implements \Throwable
{
//让路由结束退出的错误
}

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
        if (file_exists($filepath)) {
            $idata = require $filepath;
        } else {
            $idata = [
                'path' => 'app/' . $name,
                'namespace' => 'app\\' . $name,
                'base' => '/' . ($name == 'home' ? '' : $name),
                'rules' => [
                    '@^/(\w+)/(\w+)/(\d+)$@i' => [
                        'ctl' => '$1',
                        'act' => '$2',
                        'id' => '$3',
                    ],
                    '@^/(\w+)/(\w+)$@i' => [
                        'ctl' => '$1',
                        'act' => '$2',
                    ],
                    '@^/(\w+)/?$@i' => [
                        'ctl' => '$1',
                        'act' => 'index',
                    ],
                    '@^/$@' => [
                        'ctl' => 'index',
                        'act' => 'index',
                    ],
                ],
                'resolve' => function ($ctl, $act, $keys) {
                    $url = '/{ctl}';
                    if (!empty($act)) {
                        $url .= '/{act}';
                    }
                    if (isset($keys['id'])) {
                        $url .= '/{id}';
                    }
                    return $url;
                }
            ];
        }
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
        uasort(self::$routeMap, function ($a, $b) {
            if (strlen($a['base']) == strlen($b['base'])) {
                return 0;
            }
            return strlen($a['base']) > strlen($b['base']) ? -1 : 1;
        });
        foreach (self::$routeMap as $name => $item) {
            if (preg_match($item['base_match'], $url, $m)) {
                $item['uri'] = empty($m[1]) ? '' : $m[1];
                $item['uri'] = preg_replace('@^/index\.php@i', '/', $item['uri']);
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
        if (preg_match('@\.json$@i', $url)) {
            $_SERVER['REQUEST_AJAX'] = true;
            $url = preg_replace('@\.json$@i', '', $url);
        }
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
        foreach ($arg as $key => $val) {
            if (in_array($key, ['act', 'ctl', 'base', 'app'])) {
                continue;
            }
            if (!isset($_GET[$key])) {
                $_GET[$key] = $val;
            }
            if (!isset($_REQUEST[$key])) {
                $_REQUEST[$key] = $val;
            }
        }
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

    /**
     * ~/ctl/act
     * ^/admin/ctl/act
     * @param string $url
     */
    public static function url(string $url = '', array $query = [])
    {
        if (!(isset($url[1]) && ($url[0] == '~' || $url[0] == '^') && $url[1] == '/')) {
            if ($query == null || count($query) == 0) {
                return $url;
            }
        }
        $info = parse_url($url);
        $path = isset($info['path']) ? $info['path'] : '';
        $str_query = isset($info['query']) ? $info['query'] : '';
        $query = is_array($query) ? $query : [];
        if (!empty($str_query)) {
            $temp = [];
            parse_str($str_query, $temp);
            $query = array_merge($temp, $query);
        }

        if (!(isset($url[1]) && ($url[0] == '~' || $url[0] == '^') && $url[1] == '/')) {
            $str_query = http_build_query($query);
            if (!empty($str_query)) {
                return $path . '?' . $str_query;
            }
            return $path;
        }
        if ($url[0] == '~') {
            $app = self::get('app');
            $path = substr($path, 1);
            return self::resolve($app, $path, $query);
        }
        if (!preg_match('@^\^/(\w+)((?:/\w+){1,2})?$@', $path, $data)) {
            return $url;
        }
        $app = isset($data[1]) ? $data[1] : self::get('app');
        $path = isset($data[2]) ? $data[2] : self::get('path');
        return self::resolve($app, $path, $query);
    }


    public static function run($url = null)
    {
        try {
            $request = Request::instance();
            if ($url === null) {
                if (IS_CLI) {
                    if (isset($_SERVER['argv']) && !empty($_SERVER['argv'][1])) {
                        $request_uri = $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
                        self::parse($_SERVER['argv'][1]);
                        $data = parse_url($_SERVER['REQUEST_URI']);
                        if (isset($data['query'])) {
                            parse_str($data['query'], $args);
                            foreach ($args as $key => $val) {
                                $_GET[$key] = $val;
                                $_REQUEST[$key] = $val;
                            }
                        }
                    } else {
                        $request_uri = $_SERVER['REQUEST_URI'] = '/';
                        self::parse('/');
                    }
                } else {
                    if (isset($_SERVER['PATH_INFO'])) {
                        $request_uri = $_SERVER['PATH_INFO'];
                    } else {
                        $request_uri = $_SERVER['REQUEST_URI'];
                    }
                    if (empty($request_uri)) {
                        $request_uri = '/';
                    }
                    self::parse($request_uri);
                }
            } else {
                $request_uri = $url;
                self::parse($url);
            }

            if (self::$route == null) {
                throw new RouteEndError('未初始化路由参数,url:' . $request_uri);
            }
            if (empty(self::$route['app'])) {
                throw new RouteEndError('不存在的路径,url:' . $request_uri);
            }
            if (empty(self::$route['ctl'])) {
                throw new RouteEndError('不存在的控制器,url:' . $request_uri);
            }
            if (empty(self::$route['act'])) {
                throw new RouteEndError('不存在的控制器方法,url:' . $request_uri);
            }
            $app = self::$route['app'];
            $ctl = Utils::toCamel(self::$route['ctl']);
            $act = Utils::toCamel(self::$route['act']);
            $act = lcfirst($act);
            $data = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : [];
            if (empty($data['path'])) {
                throw new RouteEndError('没有设置应用目录,url:' . $url);
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
            if (!class_exists($class)) {
                throw new RouteEndError('不存在的控制器:' . $class);
            }
            try {
                $oReflectionClass = new \ReflectionClass($class);
                $method = $oReflectionClass->getMethod($act . 'Action');
                if ($method->isPublic()) {
                    $params = $method->getParameters();
                    $args = [];
                    if (count($params) > 0) {
                        foreach ($params as $param) {
                            $name = $param->getName();
                            $type = 'any';
                            if (is_callable([$param, 'hasType'])) {
                                if ($param->hasType()) {
                                    $refType = $param->getType();
                                    if ($refType != null) {
                                        if (is_callable([$refType, 'getName'])) {
                                            $type = $refType->getName();
                                        } else {
                                            $type = strval($refType);
                                        }
                                        $type = empty($type) ? 'any' : $type;
                                    }
                                }
                            }
                            if ($type == 'any') {
                                if (is_callable([$param, 'getClass'])) {
                                    $refType = $param->getClass();
                                    if ($refType != null) {
                                        if (is_callable([$refType, 'getName'])) {
                                            $type = $refType->getName();
                                        } else {
                                            $type = strval($refType);
                                        }
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
                                case '\beacon\Request':
                                case 'beacon\Request':
                                    $args[] = $request;
                                    break;
                                default :
                                    $args[] = $request->param($name, $def);
                                    break;
                            }
                        }
                    }
                    $example = new $class();
                    if ($request->isAjax()) {
                        $request->setContentType('json');
                    } else {
                        $request->setContentType('html');
                    }
                    if (method_exists($example, 'initialize')) {
                        $example->initialize($request);
                    }
                    $out = $method->invokeArgs($example, $args);
                    if ($request->getContentType() == 'application/json' || $request->getContentType() == 'text/json') {
                        echo json_encode($out);
                        exit;
                    } else {
                        if (is_array($out)) {
                            $request->setContentType('json');
                            echo json_encode($out);
                            exit;
                        } else {
                            if (!empty($out)) {
                                echo $out;
                            }
                        }
                    }
                }
            } catch (\Error $e) {
                throw $e;
            } catch (\Exception $e) {
                throw $e;
            }
        } catch (RouteEndError $exception) {
            echo "<h1>404 {$request_uri} Not Found!</h1>";
            Console::error('Sdopx Exception: ' . $exception->getMessage());
            Console::info($exception->getTraceAsString());
            return;
        } catch (\sdopx\SdopxException  $exception) {
            Console::error('Sdopx Exception: ' . $exception->getMessage());
            Console::info($exception->getStack());
            Console::info($exception->getTraceAsString());
        } catch (\Exception $exception) {
            Console::error('BeaconPHP Exception: ' . $exception->getMessage());
            Console::info($exception->getTraceAsString());
        } catch (\Error $error) {
            Console::error('BeaconPHP Error: ' . $error->getMessage());
            Console::info($error->getTraceAsString());
        }
    }


}

