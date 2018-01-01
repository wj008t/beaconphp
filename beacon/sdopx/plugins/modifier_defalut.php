<?php
function modifier_defalut($string, $default)
{
    if (!isset($string) || $string === null || $string === '') {
        return $default;
    }
    return $string;
}
