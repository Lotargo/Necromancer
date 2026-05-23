<?php

function investigare_in_tela($query)
{
    $snippets = [];

    // 1. Primary search: Yahoo Search
    $yahoo_url = "https://search.yahoo.com/search?p=" . urlencode($query);
    $ch = curl_init($yahoo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $yahoo_html = curl_exec($ch);
    $yahoo_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $yahoo_err = curl_error($ch);
    // curl_close($ch);

    if ($yahoo_code === 200 && !empty($yahoo_html)) {
        $matches = [];
        // Extract Yahoo snippets
        preg_match_all('/<div class="compText[^"]*"[^>]*>(.*?)<\/div>/s', $yahoo_html, $matches);
        if (empty($matches[1])) {
            preg_match_all('/<span class="fc-falcon"[^>]*>(.*?)<\/span>/s', $yahoo_html, $matches);
        }
        $snippets = array_slice($matches[1] ?? [], 0, 5);
    }

    // 2. Fallback search: DuckDuckGo HTML (if Yahoo failed or returned nothing)
    if (empty($snippets)) {
        $url = "https://html.duckduckgo.com/html/?q=" . urlencode($query);
        $attempts = 0;
        $max_attempts = 2;
        $html = "";
        $http_code = 0;

        // Generate a unique cookie file to avoid session linking
        $cookie_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ddg_cookies_' . uniqid() . '.txt';

        while ($attempts < $max_attempts) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
            curl_setopt($ch, CURLOPT_REFERER, 'https://duckduckgo.com/');
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

            $html = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close($ch);

            if ($http_code === 200 && !empty($html) && strlen($html) > 1000) {
                break;
            }

            $attempts++;
            if ($attempts < $max_attempts) {
                usleep(500000);
            }
        }

        if (file_exists($cookie_file)) {
            @unlink($cookie_file);
        }

        if ($http_code === 200 && !empty($html)) {
            $matches = [];
            preg_match_all('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $matches);
            if (empty($matches[1])) {
                preg_match_all('/<div[^>]*class="result__snippet"[^>]*>(.*?)<\/div>/s', $html, $matches);
            }
            $snippets = array_slice($matches[1] ?? [], 0, 5);

            // Ultimate fallback to paragraphs if regex missed snippets
            if (empty($snippets)) {
                preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $html, $matches);
                $snippets = array_slice($matches[1] ?? [], 0, 3);
            }
        }
    }

    $clean_snippets = array_map(function ($s) {
        return trim(strip_tags(html_entity_decode($s)));
    }, $snippets);

    if (empty($clean_snippets)) {
        return "Nihil inventum (no snippets found). Query: " . $query;
    }

    return implode("\n---\n", $clean_snippets);
}
