<?php

function sani($val) {
    if (is_array($val)) {
        return array_map('sani', $val);
    }
    return str_replace(['|', "\r", "\n"], '', $val);
}

function sani_nuntius($val) {
    if (is_array($val)) {
        return array_map('sani_nuntius', $val);
    }
    return str_replace(['|', "\r", "\n"], [' ', '', ' '], $val);
}

function sanitizare_internam($value)
{
    return str_replace(["|", "\r", "\n"], [" ", "", " "], (string)$value);
}
