<?php

function sdopx_func_flow($params, $template)
{
    $name = (empty($params['name'])) ? '' : $params['name'];
    $branch = (empty($params['branch'])) ? '' : $params['branch'];
    $task = (empty($params['task'])) ? '' : $params['task'];
    $text = (empty($params['text'])) ? '' : $params['text'];
    $attr = (empty($params['attr'])) ? [] : $params['attr'];
    $token = \app\flow\lib\Flow::getToken($task, $name, $branch);
    if ($token <= 0) {
        return '';
    }
    $html = [];
    $html[] = '<a';
    foreach ($attr as $key => $val) {
        $html[] = $key . '="' . htmlspecialchars($val) . '"';
    }
    $html[] = '>';
    $code = join(' ', $html);
    $code .= $text;
    $code .= '</a>';
    return $code;
}
