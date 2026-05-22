<?php

function tempestas_resolvere_locum($location)
{
    $location = trim((string)$location);
    if ($location === '') {
        return [null, "Location name cannot be empty."];
    }

    $geo_url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($location) . "&count=1&language=en&format=json";
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    $ch = curl_init($geo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $geo_res = curl_exec($ch);
    $geo_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($geo_code !== 200 || empty($geo_res)) {
        return [null, "Failed to resolve coordinates for location: " . $location];
    }

    $geo_data = json_decode($geo_res, true);
    if (empty($geo_data['results'][0])) {
        return [null, "Location not found: " . $location];
    }

    $res = $geo_data['results'][0];

    return [[
        'latitude' => $res['latitude'],
        'longitude' => $res['longitude'],
        'name' => $res['name'] ?? $location,
        'country' => $res['country'] ?? '',
        'timezone' => $res['timezone'] ?? 'auto',
        'ua' => $ua,
    ], null];
}

function tempestas_formatare_gmt_offset($utc_offset_seconds)
{
    $utc_offset_hours = ((int)$utc_offset_seconds) / 3600;
    $sign = $utc_offset_hours >= 0 ? '+' : '-';
    $value = rtrim(rtrim(number_format(abs($utc_offset_hours), 2, '.', ''), '0'), '.');

    return "GMT" . $sign . $value;
}

function evocatio_temporis($location)
{
    [$resolved, $error] = tempestas_resolvere_locum($location);
    if ($error !== null) {
        return $error;
    }

    $time_url = "https://api.open-meteo.com/v1/forecast?latitude=" . $resolved['latitude']
        . "&longitude=" . $resolved['longitude']
        . "&current=temperature_2m"
        . "&timezone=" . urlencode($resolved['timezone']);

    $ch = curl_init($time_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $resolved['ua']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $time_res = curl_exec($ch);
    $time_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($time_code !== 200 || empty($time_res)) {
        return "Failed to retrieve current local time for location: " . $resolved['name'];
    }

    $time_data = json_decode($time_res, true);
    if (empty($time_data['current']['time'])) {
        return "Current local time is unavailable for location: " . $resolved['name'];
    }

    $utc_offset_seconds = (int)($time_data['utc_offset_seconds'] ?? 0);

    $output = [
        "location" => $resolved['name'],
        "country" => $resolved['country'],
        "latitude" => $resolved['latitude'],
        "longitude" => $resolved['longitude'],
        "timezone" => $time_data['timezone'] ?? $resolved['timezone'],
        "timezone_abbreviation" => $time_data['timezone_abbreviation'] ?? '',
        "utc_offset_seconds" => $utc_offset_seconds,
        "utc_offset_hours" => $utc_offset_seconds / 3600,
        "gmt_offset" => tempestas_formatare_gmt_offset($utc_offset_seconds),
        "current_local_time" => $time_data['current']['time'],
    ];

    return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function evocatio_tempestatis($location)
{
    [$resolved, $error] = tempestas_resolvere_locum($location);
    if ($error !== null) {
        return $error;
    }

    $forecast_url = "https://api.open-meteo.com/v1/forecast?latitude=" . $resolved['latitude']
        . "&longitude=" . $resolved['longitude']
        . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,rain,showers,snowfall,weather_code,cloud_cover,pressure_msl,wind_speed_10m"
        . "&timezone=" . urlencode($resolved['timezone']);

    $ch = curl_init($forecast_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $resolved['ua']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $forecast_res = curl_exec($ch);
    $forecast_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($forecast_code !== 200 || empty($forecast_res)) {
        return "Failed to retrieve weather forecast for location: " . $resolved['name'];
    }

    $w_data = json_decode($forecast_res, true);
    if (empty($w_data['current'])) {
        return "Weather forecast data is unavailable for location: " . $resolved['name'];
    }

    $current = $w_data['current'];
    $tz_name = $w_data['timezone'] ?? $resolved['timezone'];
    $tz_abbr = $w_data['timezone_abbreviation'] ?? '';
    $utc_offset_seconds = (int)($w_data['utc_offset_seconds'] ?? 0);

    $wmo_codes = [
        0 => "Clear sky",
        1 => "Mainly clear",
        2 => "Partly cloudy",
        3 => "Overcast",
        45 => "Fog",
        48 => "Depositing rime fog",
        51 => "Drizzle: Light intensity",
        53 => "Drizzle: Moderate intensity",
        55 => "Drizzle: Dense intensity",
        56 => "Freezing Drizzle: Light intensity",
        57 => "Freezing Drizzle: Dense intensity",
        61 => "Rain: Slight intensity",
        63 => "Rain: Moderate intensity",
        65 => "Rain: Heavy intensity",
        66 => "Freezing Rain: Light intensity",
        67 => "Freezing Rain: Heavy intensity",
        71 => "Snow fall: Slight intensity",
        73 => "Snow fall: Moderate intensity",
        75 => "Snow fall: Heavy intensity",
        77 => "Snow grains",
        80 => "Rain showers: Slight",
        81 => "Rain showers: Moderate",
        82 => "Rain showers: Violent",
        85 => "Snow showers: Slight",
        86 => "Snow showers: Heavy",
        95 => "Thunderstorm: Slight or moderate",
        96 => "Thunderstorm with slight hail",
        99 => "Thunderstorm with heavy hail",
    ];

    $code = $current['weather_code'] ?? 0;
    $description = $wmo_codes[$code] ?? "Unknown conditions";

    $output = [
        "location" => $resolved['name'],
        "country" => $resolved['country'],
        "latitude" => $resolved['latitude'],
        "longitude" => $resolved['longitude'],
        "timezone" => $tz_name,
        "timezone_abbreviation" => $tz_abbr,
        "utc_offset_hours" => $utc_offset_seconds / 3600,
        "gmt_offset" => tempestas_formatare_gmt_offset($utc_offset_seconds),
        "current_local_time" => $current['time'] ?? 'unknown',
        "temperature" => ($current['temperature_2m'] ?? 'unknown') . " °C",
        "feels_like" => ($current['apparent_temperature'] ?? 'unknown') . " °C",
        "relative_humidity" => ($current['relative_humidity_2m'] ?? 'unknown') . " %",
        "weather_condition" => $description,
        "cloud_cover" => ($current['cloud_cover'] ?? 'unknown') . " %",
        "wind_speed" => ($current['wind_speed_10m'] ?? 'unknown') . " km/h",
        "pressure" => ($current['pressure_msl'] ?? 'unknown') . " hPa",
        "precipitation" => ($current['precipitation'] ?? 0) . " mm",
        "is_day" => ($current['is_day'] ?? 1) == 1 ? "yes" : "no",
    ];

    return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
