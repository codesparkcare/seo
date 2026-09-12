<?php
/**
 * LocalRank Pro - 100% Database-Free Configuration
 * Uses clean JSON file storage (config.json) without SQLite or MySQL.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

define('APP_ROOT', __DIR__);
define('CONFIG_FILE', APP_ROOT . '/config.json');

// Read Configuration
function loadConfig() {
    if (!file_exists(CONFIG_FILE)) {
        $exampleFile = APP_ROOT . '/config.example.json';
        if (file_exists($exampleFile)) {
            copy($exampleFile, CONFIG_FILE);
        } else {
            return [];
        }
    }
    $content = file_get_contents(CONFIG_FILE);
    return json_decode($content, true) ?: [];
}

// Save Configuration
function saveConfig($data) {
    return file_put_contents(CONFIG_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// JSON Output Helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Google OAuth Access Token with Auto-Refresh
function getGoogleAccessToken(&$config) {
    $oauth = $config['google_oauth'] ?? [];
    if (empty($oauth['access_token'])) return null;
    
    // Check if token expired or about to expire in 60s
    if (time() >= ($oauth['token_expires_at'] ?? 0) - 60 && !empty($oauth['refresh_token']) && !empty($oauth['client_id']) && !empty($oauth['client_secret'])) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $oauth['client_id'],
            'client_secret' => $oauth['client_secret'],
            'refresh_token' => $oauth['refresh_token'],
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!empty($data['access_token'])) {
            $config['google_oauth']['access_token'] = $data['access_token'];
            $config['google_oauth']['token_expires_at'] = time() + ($data['expires_in'] ?? 3600);
            $config['google_oauth']['is_connected'] = true;
            saveConfig($config);
            return $data['access_token'];
        }
    }
    return $oauth['access_token'];
}

