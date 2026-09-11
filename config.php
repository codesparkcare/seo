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
