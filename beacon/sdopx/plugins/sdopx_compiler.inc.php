<?php

function compile_to_camel($name) {
    if (empty($name)) {
        return $name;
    }
    return ucfirst(preg_replace_callback('@_+[A-Za-z]@', function($vals) {
                return strtoupper(ltrim($vals[0], '_'));
            }, $name));
}

function modifiercompiler_upper($params) {
    return 'strtoupper(' . $params[0] . ')';
}

function modifiercompiler_lower($params) {
    return 'strtolower(' . $params[0] . ')';
}

function modifiercompiler_equal($params) {
    if (isset($params[3])) {
        return "({$params[0]}=={$params[1]}?{$params[2]}:{$params[3]})";
    }
    if (isset($params[2])) {
        return "({$params[0]}=={$params[1]}?{$params[2]}:'')";
    }
    if (isset($params[1])) {
        return "({$params[0]}=={$params[1]}?{$params[0]}:'')";
    }
    return "({$params[0]}!=''?{$params[0]}:'')";
}

function modifiercompiler_notempty($params) {
    if (isset($params[1])) {
        return "(empty({$params[0]})?'':({$params[1]}))";
    }
    return $params[0];
}

function modifiercompiler_empty($params) {
    if (isset($params[1])) {
        return "(empty({$params[0]})?'':{$params[1]})";
    }
    return "(empty({$params[0]})?'':{$params[0]})";
}

function modifiercompiler_notequal($params) {
    if (isset($params[3])) {
        return "({$params[0]}!={$params[1]}?{$params[2]}:{$params[3]})";
    }
    if (isset($params[2])) {
        return "({$params[0]}!={$params[1]}?{$params[2]}:'')";
    }
    if (isset($params[1])) {
        return "({$params[0]}!={$params[1]}?{$params[0]}:'')";
    }
    return $params[0];
}

function modifiercompiler_strip_tags($params) {
    if (!isset($params[1]) || $params[1] === true || trim($params[1], '"') == 'true') {
        return "preg_replace('!<[^>]*?>!', ' ', {$params[0]})";
    } else {
        return 'strip_tags(' . $params[0] . ')';
    }
}

function modifiercompiler_default($params) {
    $output = $params[0];
    if (!isset($params[1])) {
        $params[1] = "''";
    }
    array_shift($params);
    foreach ($params as $param) {
        $output = '(($tmp = @(' . $output . '))===null||$tmp===\'\' ? ' . $param . ' : $tmp)';
    }
    return $output;
}

function modifiercompiler_money($params) {
    if (!isset($params[1])) {
        $params[1] = "'￥'";
    }
    return '(' . $params[1] . '.sprintf("%01.2f", floatval(' . $params[0] . ')))';
}
