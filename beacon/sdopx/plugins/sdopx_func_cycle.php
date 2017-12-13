<?php

function sdopx_func_cycle($params, $template)
{
    static $cycle_vars = [];
    $name = (empty($params['name'])) ? 'default' : $params['name'];
    $print = (isset($params['print'])) ? (bool)$params['print'] : true;
    $advance = (isset($params['advance'])) ? (bool)$params['advance'] : true;
    $reset = (isset($params['reset'])) ? (bool)$params['reset'] : false;
    if (!isset($params['values'])) {
        if (!isset($cycle_vars[$name]['values'])) {
            trigger_error("cycle: missing 'values' parameter");
            return;
        }
    } else {
        if (isset($cycle_vars[$name]['values']) && $cycle_vars[$name]['values'] != $params['values']
        ) {
            $cycle_vars[$name]['index'] = 0;
        }
        $cycle_vars[$name]['values'] = $params['values'];
    }
    if (isset($params['delimiter'])) {
        $cycle_vars[$name]['delimiter'] = $params['delimiter'];
    } elseif (!isset($cycle_vars[$name]['delimiter'])) {
        $cycle_vars[$name]['delimiter'] = ',';
    }
    if (is_array($cycle_vars[$name]['values'])) {
        $cycle_array = $cycle_vars[$name]['values'];
    } else {
        $cycle_array = explode($cycle_vars[$name]['delimiter'], $cycle_vars[$name]['values']);
    }
    if (!isset($cycle_vars[$name]['index']) || $reset) {
        $cycle_vars[$name]['index'] = 0;
    }
    if (isset($params['assign'])) {
        $print = false;
        $template->assign($params['assign'], $cycle_array[$cycle_vars[$name]['index']]);
    }
    if ($print) {
        $retval = $cycle_array[$cycle_vars[$name]['index']];
    } else {
        $retval = null;
    }
    if ($advance) {
        if ($cycle_vars[$name]['index'] >= count($cycle_array) - 1) {
            $cycle_vars[$name]['index'] = 0;
        } else {
            $cycle_vars[$name]['index']++;
        }
    }
    return $retval;
}
