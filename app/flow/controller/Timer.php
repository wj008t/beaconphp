<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/31
 * Time: 20:29
 */

namespace app\flow\controller;


use beacon\Controller;
use beacon\Utils;

class Timer extends Controller
{
    public function send($url, $post, $queue)
    {
        $data = parse_url($url);
        if (!isset($data['host'])) {
            return;
        }
        $data['port'] = isset($data['port']) ? $data['port'] : 80;
        $data['path'] = isset($data['path']) ? $data['path'] : '/';
        \Swoole\Async::dnsLookup($data['host'], function ($domainName, $ip) use ($data, $post, $queue) {
            if (empty($ip)) {
                return;
            }
            // echo '-------------' . $data['path'] . '--------------' . "\n";
            $cli = new \swoole_http_client($ip, $data['port']);
            $cli->setHeaders([
                'Host' => $domainName,
                "User-Agent" => 'Chrome/49.0.2587.3',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Encoding' => 'gzip',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
            $cli->set(['timeout' => 1]);
            $cli->post($data['path'], $post, function ($cli) use ($queue) {
                $body = trim($cli->body);
                if (Utils::isJsonString($body)) {
                    try {
                        $data = json_decode($body, true);
                        var_export($data);
                        if (isset($data['status']) && $data['status'] === true) {
                            $row = $this->db->getRow('select * from @pf_flow_queue where id=?', $queue);
                            if ($row != null) {
                                $this->db->update('@pf_flow_queue', ['tice' => 10], $queue);
                            }
                        }
                    } catch (\Exception $exception) {
                    }
                }
                $cli->close();
            });
        });
    }

    public function indexAction()
    {
        if (!IS_CLI) {
            return '必须使用 cli 模式运行 命令： php index.php /flow/timer';
        }
        swoole_timer_tick(3000, function ($timer_id) {
            $flows = [];
            $time = time();
            echo $time . "\n";
            $queue = $this->db->getList('select * from @pf_flow_queue where timeout<? and tice<5 limit 0,10', $time);
            foreach ($queue as $item) {
                $flowId = $item['flowId'];
                $this->db->update('@pf_flow_queue', ['tice' => $item['tice'] + 1], $item['id']);
                if (!isset($flows[$flowId])) {
                    $flows[$flowId] = $this->db->getRow('select * from @pf_flow_list where id=?', $flowId);
                }
                $flow = $flows[$flowId];
                if ($flow == null) {
                    continue;
                }
                //echo "令牌：" . $item['tokenId'] . "\n";
                $post = [];
                $post['condition'] = $item['condition'];
                $post['tokenId'] = $item['tokenId'];
                $post['timeout'] = $item['timeout'];
                $post['branch'] = $item['branch'];
                $post['sign'] = md5(md5($flow['key']) . md5($post['condition'] . '|' . $post['tokenId'] . '|' . $post['timeout'] . '|' . $post['branch']));
                $this->send($item['url'], $post, $item['id']);
            }
        });
        echo "run ok\n";
    }
}