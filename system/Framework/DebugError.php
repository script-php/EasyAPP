<?php

/**
* @package      DebugError
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class DebugError {

    public function display(\Throwable $e) {
        echo "<div style='background:#2b2b2b;color:#f8f8f2;padding:20px;font-family:monospace;border:2px solid #f92672;border-radius:10px;margin:20px;'>";
        echo "<h2 style='color:#f92672;'>Exception Thrown</h2>";
        echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        echo "<strong>File:</strong> " . $e->getFile() . " (Line " . $e->getLine() . ")<br><br>";
        echo "<strong>Trace:</strong><pre style='white-space:pre-wrap;color:#66d9ef'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }

    public static function log(\Throwable $e, $logFile = CONFIG_DIR_STORAGE . 'logs/error.log') {
        $log = str_repeat("-", 40) . " [" . date('Y-m-d H:i:s') . "] " . str_repeat("-", 40) . PHP_EOL;
        $log .= "Message: " . $e->getMessage() . PHP_EOL;
        $log .= "File: " . $e->getFile() . " (Line " . $e->getLine() . ")" . PHP_EOL;
        $log .= "Trace:" . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
        $log .= str_repeat("-", 40) . " [ END ERROR ] " . str_repeat("-", 40) . PHP_EOL;

        error_log($log, 3, $logFile);
    }

}