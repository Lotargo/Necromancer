<?php

function purgare_sessionem_aequilibrio($id_sessionis)
{
    $aequilibrium_host = getenv("AEQUILIBRIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($aequilibrium_host, 8081, $errno, $errstr, 2);
    if (!$fp)
        return false;
    fwrite($fp, "PURGARE_SESSIONEM|" . $id_sessionis . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    return trim($responsum);
}

function loqui_cum_aequilibrio($id_sessionis)
{
    $aequilibrium_host = getenv("AEQUILIBRIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($aequilibrium_host, 8081, $errno, $errstr, 2);
    if (!$fp)
        return false;
    fwrite($fp, "PETERE_CLAVEM|" . $id_sessionis . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    return trim($responsum);
}
