<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/11
 * Time: 1:43
 */

namespace beacon;

defined('IS_CGI') or define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' ? true : false);
defined('IS_CLI') or define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
defined('IS_WIN') or define('IS_WIN', strstr(PHP_OS, 'WIN') ? true : false);
defined('HTTP_SWOOLE') or define('HTTP_SWOOLE', false);


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
     * 解析URL路径
     * @param string $url
     * @return array|null
     */
    public static function parse(HttpContext $context, string $url)
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
        foreach ($arg as $key => $val) {
            if (in_array($key, ['act', 'ctl', 'base', 'app'])) {
                continue;
            }
            if (!isset($context->_get[$key])) {
                $context->_get[$key] = $val;
            }
            if (!isset($context->_param[$key])) {
                $context->_param[$key] = $val;
            }
        }
        $context->_route = $arg;
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


    public static function run($url = null, \swoole_http_request $req = null, \swoole_http_response $res = null)
    {
        try {
            $context = new HttpContext($req, $res);
            $request = $context->getRequest();
            if ($request->isAjax()) {
                $context->setContentType('json');
            } else {
                $context->setContentType('html');
            }
            if ($url === null) {
                if (IS_CLI && !HTTP_SWOOLE) {
                    if (isset($context->_server['argv']) && !empty($context->_server['argv'][1])) {
                        $context->_server['REQUEST_URI'] = $context->_server['argv'][1];
                        self::parse($context->_server['argv'][1]);
                        $data = parse_url($context->_server['REQUEST_URI']);
                        if (isset($data['query'])) {
                            parse_str($data['query'], $args);
                            foreach ($args as $key => $val) {
                                $context->_get[$key] = $val;
                                $context->_get[$key] = $val;
                            }
                        }
                    } else {
                        $context->_server['REQUEST_URI'] = '/';
                        self::parse($context, '/');
                    }
                } else {
                    if (isset($context->_server['PATH_INFO'])) {
                        self::parse($context, $context->_server['PATH_INFO']);
                    } else {
                        self::parse($context, $context->_server['REQUEST_URI']);
                    }
                }
            } else {
                self::parse($context, $url);
            }
            if ($context->_route == null) {
                throw new RouteEndError('未初始化路由参数');
            }
            if (empty($context->_route['app'])) {
                throw new RouteEndError('不存在的路径');
            }
            if (empty($context->_route['ctl'])) {
                throw new RouteEndError('不存在的控制器');
            }
            if (empty($context->_route['act'])) {
                throw new RouteEndError('不存在的控制器方法');
            }
            $app = $context->_route['app'];
            $ctl = Utils::toCamel($context->_route['ctl']);
            $act = Utils::toCamel($context->_route['act']);
            $act = lcfirst($act);
            $data = isset(self::$routeMap[$app]) ? self::$routeMap[$app] : [];
            if (empty($data['path'])) {
                throw new RouteEndError('没有设置应用目录');
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
                                    case '\beacon\HttpContext':
                                    case 'beacon\HttpContext':
                                        $args[] = $context;
                                        break;
                                    default :
                                        $args[] = $request->param($name, $def);
                                        break;
                                }
                            }
                        }
                        $example = new $class($context);
                        if (method_exists($example, 'initialize')) {
                            $example->initialize($request);
                        }
                        $out = $method->invokeArgs($example, $args);
                        if ($context->getContentType() == 'application/json' || $context->getContentType() == 'text/json') {
                            $context->write(json_encode($out));
                            return $context->end();
                        } else {
                            if (is_array($out)) {
                                $context->setContentType('json');
                                $context->write(json_encode($out));
                                return $context->end();
                            } else {
                                $context->write($out);
                                return $context->end();
                            }
                        }
                    }
                } catch (\RouteException $e) {
                    throw $e;
                }
            } else {
                throw new RouteEndError('不存在的控制器');
            }
        } catch (RouteEndError $exception) {
            if ($res !== null) {
                $res->end('');
            }
            return;
        } catch (\Exception $exception) {
            if (IS_CLI && defined('HTTP_SWOOLE') && HTTP_SWOOLE) {
                echo $exception->getCode() . $exception->getMessage();
                echo "\n";
                echo $exception->getTraceAsString();
                $res->status(404);
                $res->end();
                return;
            }
            if (IS_CLI) {
                echo $exception->getMessage();
                echo "\n";
                echo $exception->getTraceAsString();
            } else {
                echo '<h1>' . $exception->getMessage() . '</h1>';
                echo '<pre>' . $exception->getTraceAsString() . '</pre>';
            }
        } catch (\Error $error) {
            if (IS_CLI && defined('HTTP_SWOOLE') && HTTP_SWOOLE) {
                echo $error->getCode() . $error->getMessage();
                echo "\n";
                echo $error->getTraceAsString();
                $res->status(500);
                $res->end();
                return;
            }
            if (IS_CLI) {
                echo $error->getMessage();
                echo "\n";
                echo $error->getTraceAsString();
            } else {
                echo '<h1>' . $error->getMessage() . '</h1>';
                echo '<pre>' . $error->getTraceAsString() . '</pre>';
            }
        } finally {
            $context = null;
        }
    }

    /**
     * HTTP_SWOOLE 处理静态资源
     * @param null $url
     * @param null $paths
     */
    public static function runStatic($url = null, \swoole_http_request $req = null, \swoole_http_response $res = null, $paths = null)
    {
        if ($url == null) {
            $url = $req->server['request_uri'];
        }
        if ($paths == null) {
            return false;
        }
        $pregs = [];
        foreach ($paths as $path) {
            $pregs[] = preg_quote($path, '@');
        }
        if (!preg_match('@^/?(' . join('|', $pregs) . ')@', $url, $data)) {
            return false;
        }
        $parse = parse_url($url);
        if (!empty($parse['path'])) {
            $filename = Utils::path(ROOT_DIR, 'www', $parse['path']);
            if (!file_exists($filename)) {
                $res->status(404);
                $res->end('');
                return true;
            }
            $last_modified_time = filemtime($filename);
            if (isset($req->header['if-modified-since'])) {
                $time = @strtotime($req->header['if-modified-since']);
                //var_export($req->header['if-modified-since'] . '|' . $time . '|' . $last_modified_time);
                if ($time >= $last_modified_time) {
                    $res->status(304);
                    $res->end('');
                    return true;
                }
            }
            $info = pathinfo($filename);
            $extension = $info['extension'];
            $context = new HttpContext($req, $res);
            $context->setContentType($extension);
            $context->setHeader('Last-Modified', gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
            $context->setHeader('Etag', md5($last_modified_time));
            $res->end(file_get_contents($filename));
            return true;
        }
    }


}

