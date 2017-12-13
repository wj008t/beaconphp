<?php

function modifier_rmhtml($string)
{
    return trim(preg_replace('@<.*>@si', '', $string));
}
