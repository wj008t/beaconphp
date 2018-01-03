<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 0:52
 */

namespace app\flow\form;

use beacon\DB;
use beacon\Form;
use beacon\HttpContext;

class FlowForm extends Form
{
    public $title = '添加流程';

    public function __construct(HttpContext $context, string $type = '')
    {
        if ($type == 'edit') {
            $this->title = '编辑流程';
        }
        parent::__construct($context, $type);
    }

    protected function load()
    {
        return [
            'name' => [
                'label' => '名称',
                'data-val' => ['r' => true],
                'data-val-msg' => ['r' => '名称不能为空'],
            ],
            'gateway' => [
                'label' => '网关地址',
                'data-val' => ['r' => true],
                'data-val-msg' => ['r' => '网关地址不能为空'],
            ],
            'key' => [
                'label' => '签名码',
                'data-val' => ['r' => true],
                'data-val-msg' => ['r' => '签名码不能为空'],
            ],
            'addtime' => [
                'label' => '添加时间',
                'view-close' => true,
                'default' => date('Y-m-d H:i:s'),
            ],
        ];
    }
}