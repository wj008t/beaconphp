<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 22:35
 */
Swoole\Async::dnsLookup("www.baidu.com", function ($domainName, $ip) {
    if (empty($ip)) {
        return;
    }
    $cli = new swoole_http_client($ip, 8088);
    $cli->setHeaders([
        'Host' => $domainName,
        "User-Agent" => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $cli->set(['timeout' => 1]);
    $cli->get('/flow/work', function ($cli) {
        var_dump('qqq');
        var_dump($cli->body);
        $cli->close();
    });
});