<?php
$log = file_get_contents('/var/www/html/storage/logs/laravel.log');
$lines = explode("\n", trim($log));
$last = json_decode(end($lines), true);
echo ($last['message'] ?? 'no message') . "\n";
if (isset($last['exception'])) {
    echo substr($last['exception'], 0, 1000) . "\n";
}
