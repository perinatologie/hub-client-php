<?php

require_once __DIR__ . '/../vendor/autoload.php';
$username = getenv('HUB_USERNAME');
$password = getenv('HUB_PASSWORD');
$url = getenv('HUB_URL');

if (!$username || !$password || !$url) {
    throw new RuntimeException("Please refer to the README.md file on how to setup the environment variables");
}
