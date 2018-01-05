<?php

$url = '~/admin/ct_dd_3?tex=e';

$urlinfo = parse_url($url);
print_r($urlinfo);

preg_match('@^~/(\w+)((?:/\w+){1,2})?$@', $urlinfo['path'], $data);
print_r($data);