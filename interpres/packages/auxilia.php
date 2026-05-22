<?php

function env_ad_boolean($nomen, $default = false)
{
    $valor = getenv($nomen);
    if ($valor === false || $valor === '') {
        return $default;
    }

    $parsed = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($parsed === null) {
        return $default;
    }

    return $parsed;
}
