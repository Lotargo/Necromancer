<?php

$title = $args['title'] ?? 'Simulation';
$type = $args['type'] ?? 'oscilloscope';
$code = $args['code'] ?? '';

if (empty($code)) {
    return "Error: No Pascal code provided.";
}

if (strlen($code) > 65536) {
    return "Error: Pascal code exceeds the maximum allowed size of 64KB.";
}

$mechanica_host = getenv("MECHANICA_HOST") ?: "127.0.0.1";
$mechanica_port = getenv("MECHANICA_PORT") ?: 8082;
$unique_id = uniqid('sim_');

$payload = "RUN_SIMULATION|" . $unique_id . "|" . $code . "\n";

$fp = @fsockopen($mechanica_host, $mechanica_port, $errno, $errstr, 10);
if (!$fp) {
    return "Error: Computational core (Mechanica) is not responding.";
}

fwrite($fp, $payload);
fflush($fp);
stream_socket_shutdown($fp, STREAM_SHUT_WR);

// Read the first header (e.g. 200|Success| or 500|...)
$header = '';
while (!feof($fp)) {
    $char = fgetc($fp);
    if ($char === false) {
        break;
    }
    $header .= $char;
    // Header finishes when we read the two prefix bars and name or similar.
    // Wait, let's read until we get "200|Success|" or "500|...|" or we can just read the first line!
    // Since our Mechanica outputs the header, followed by stdout:
    // "200|Success|" - wait, did Mechanica write a newline after the header?
    // Let's check mechanica.pas line 290:
    // `Responsum := '200|Success|'; fpSend(...)`
    // Wait! Mechanica did NOT send a newline after the 200 header! It sends exactly '200|Success|'.
    // And for 500 error, it sends e.g. '500|Security|...'+sLineBreak.
    // Let's read until the second bar '|' or third bar '|' is read.
    // Let's write a simple parser: read until we have three '|' characters.
    if (substr_count($header, '|') === 2) {
        break;
    }
}

$parts = explode('|', $header);
if ($parts[0] !== '200') {
    // If it's an error, let's read the rest of the error message
    $error_body = '';
    while (!feof($fp)) {
        $error_body .= fgets($fp, 1024);
    }
    fclose($fp);
    $err_kind = $parts[1] ?? 'Error';
    $err_msg = ($parts[2] ?? '') . $error_body;
    return "Pascal Execution " . $err_kind . ": " . trim($err_msg);
}

// 200 success! We can start streaming
echo "data: " . json_encode([
    "event" => "simulation_init",
    "sim_id" => $unique_id,
    "title" => $title,
    "type" => $type
]) . "\n\n";
flush();

// Read output line by line from Mechanica and pipe as simulation_data events
while (!feof($fp)) {
    $line = fgets($fp, 2048);
    if ($line === false) {
        break;
    }
    $line = trim($line);
    if ($line === '') {
        continue;
    }

    // Attempt to decode JSON to ensure it's valid coordinate data
    $coord = json_decode($line, true);
    if (is_array($coord)) {
        echo "data: " . json_encode([
            "event" => "simulation_data",
            "sim_id" => $unique_id,
            "data" => $coord
        ]) . "\n\n";
        flush();
    }
}
fclose($fp);

// Send final simulation_end event
echo "data: " . json_encode([
    "event" => "simulation_end",
    "sim_id" => $unique_id
]) . "\n\n";
flush();

return "Simulation '" . $title . "' executed and streamed to UI successfully.";
