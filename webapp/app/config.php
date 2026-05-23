<?php
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'webapp_db');
define('DB_USER', getenv('DB_USER') ?: 'webapp');
define('DB_PASS', getenv('DB_PASS') ?: 'webapp123');

define('SYSLOG_HOST', getenv('SYSLOG_HOST') ?: '172.20.0.30');
define('SYSLOG_PORT', getenv('SYSLOG_PORT') ?: 514);

function log_to_syslog($message, $level = LOG_INFO) {
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    $syslog_message = "<134>" . date('M d H:i:s') . " webapp: " . $message;
    socket_sendto($sock, $syslog_message, strlen($syslog_message), 0, SYSLOG_HOST, SYSLOG_PORT);
    socket_close($sock);
}

function get_db_connection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            log_to_syslog("Database connection failed: " . $conn->connect_error, LOG_ERR);
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        log_to_syslog("Database exception: " . $e->getMessage(), LOG_ERR);
        die("Database error");
    }
}
?>
