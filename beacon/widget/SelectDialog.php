<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/16
 * Time: 2:04
 */

namespace widget;


use beacon\Field;

class SelectDialog extends Hidden
{
    public function code(Field $field, $args)
    {
        $args['yee-module'] = 'select_dialog';
        $args['type'] = 'text';
        $btn_args = [];
        $btn_css = isset($args['data-btn-css']) ? $args['data-btn-css'] : $field->dataBtnCss;
        $btn_args['href'] = isset($args['data-href']) ? $args['data-href'] : $field->dataHref;
        $btn_args['width'] = isset($args['data-width']) ? $args['data-width'] : $field->dataWidth;
        $btn_args['height'] = isset($args['data-height']) ? $args['data-height'] : $field->dataHeight;
        $args['data-href'] = '';
        $args['data-width'] = '';
        $args['data-height'] = '';
        $args['data-btn-css'] = '';
        $args['readonly'] = 'readonly';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);
        if (empty($btn_css)) {
            $btn_css = 'yee-btn';
        }
        $btn = [];
        $btn[] = '<a href="javascript:;" yee-module="dialog" class="' . $btn_css . '"';
        $btn[] = ' id="' . htmlspecialchars($attr['id']) . ':select_dialog_btn"';
        foreach ($btn_args as $key => $arg) {
            if (empty($arg)) {
                continue;
            }
            $btn[] = ' data-' . $key . '="' . htmlspecialchars($arg) . '"';
        }
        $btn[] = '>选择</a>';

        return '<input ' . join(' ', $attr) . ' />' . join('', $btn);
    }

}