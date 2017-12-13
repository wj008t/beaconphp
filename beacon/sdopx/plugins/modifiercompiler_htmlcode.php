<?php

function modifiercompiler_htmlcode($params, $compiler)
{
    $isbr = FALSE;
    if (isset($params[1])) {
        $str = trim($params[1], '\'"');
        if ($str == '1' || strtolower($str) == 'true') {
            $isbr = TRUE;
        }
    }
    if (!$isbr) {
        return 'str_replace(" ","&nbsp;",htmlspecialchars(' . $params[0] . '))';
    } else {
        return 'str_replace(" ","&nbsp;",str_replace(array("\r\n","\n","\r"),\'<br>\',htmlspecialchars(' . $params[0] . ')))';
    }
}
