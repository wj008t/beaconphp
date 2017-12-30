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
    public function indexAction(Request $request)
    {
        $request->setContentType('json');
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            $_SERVER['DOCUMENT_ROOT'] = Utils::path(ROOT_DIR, 'www');
        }
        $config = Config::get('upload.*');
        $catSizes = $request->param('catSizes:s', '');
        $catType = $request->param('catType:i', 0);
        $strictSize = $request->param('strictSize:i', 0);
        \ChromePhp::log($catSizes, $catType, $strictSize);

        try {
            $upload = new Uploader('filedata', $config);
            $upload->saveFile();
            if ($upload->getState() != 'SUCCESS') {
                $this->error($upload->getState());
            }
            $files = $upload->getFileInfo();
            $msg = array();
            $msg['url'] = $files[0]['url'];
            $msg['localName'] = $files[0]['fileName'];
            $msg['orgName'] = $files[0]['name'];
            $msg['files'] = [];
            foreach ($files as $file) {
                $msg['files'][] = [
                    'url' => $file['url'],
                    'localName' => $file['fileName'],
                    'orgName' => $file['name'],
                ];
            }
            $this->success("上传成功", $msg);
        } catch (\Exception $e) {
            $this->error('上传失败，未知错误');
        }

    }

    public function testAction()
    {
        $info = [];
        $info[] = '<form action="/service/upfile" enctype="multipart/form-data" method="post">';
        $info[] = '图片尺寸：<input type="text" name="cat_size"> 逗号隔开 如 100x100,200,200x300<br>';
        $info[] = '上传文件：<input type="file" name="filedata" multiple="multiple"><br>';
        $info[] = '<input type="submit" value="Upload"></form>';
        return join('', $info);
    }
}