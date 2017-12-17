<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/17
 * Time: 13:59
 */

namespace app\service\controller;


use beacon\Config;
use beacon\Controller;
use beacon\Request;
use beacon\Uploader;
use beacon\Utils;

class Upfile extends Controller
{
    //通用上传 兼容xheditor
    public function IndexAction(Request $request)
    {
        $config = Config::get('upload.*');
        $immediate = $request->get('immediate:i');
        $strict_size = $request->post('strict_size:i', 0);

        $upload = new Uploader('filedata', $config);
        if ($upload->upFile()) {
            $upload->saveFile();
        }
        $info = $upload->getFileInfo();
        //要求严格控制图片大小
        $vals = array();
        if ($strict_size && $info['isimg']) {
            $pic_width = $request->post('img_width:i', 0);
            $pic_height = $request->post('img_height:i', 0);
            $path = Utils::path(ROOT_DIR, $info['url']);
            $img = getimagesize($path);
            if (!(empty($img[0]) || empty($img[1]))) {
                if ($pic_width != $img[0] || $pic_height != $img[1]) {
                    unlink($path);
                    $this->error("请上传 宽为 $pic_width px,高为 $pic_height px 的图片,您目前的尺寸({$img[0]}x{$img[1]})不符合要求。");
                }
            }
        }

        if ($info['state'] != 'SUCCESS') {
            $this->error($info['state']);
        }
        $msg = array();
        $msg['url'] = $immediate == 1 ? '!' . $info['url'] : $info['url'];
        $msg['localname'] = $info['title'];
        $this->success("上传成功", $msg);
    }
}