<?php
/**
 * Cron Job Script for Automatic Ticket Cleanup
 * 
 * This script should be set up to run daily via cron job on your hosting server
 * 
 * To set up the cron job:
 * 1. Access your hosting control panel (cPanel, Plesk, etc.)
 * 2. Go to Cron Jobs section
 * 3. Add a new cron job with these settings:
 *    - Time: 0 2 * * * (runs daily at 2:00 AM)
 *    - Command: php /path/to/your/hosting/directory/cron-cleanup-tickets.php
 * 
 * Example cron command:
 * 0 2 * * * php /home/username/public_html/query-desk/cron-cleanup-tickets.php
 * 
 * Note: Replace the path with your actual hosting directory path
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include the cleanup logic
require_once __DIR__ . '/api/cleanup-old-tickets.php';

// Log the cron execution
$logMessage = "Cron job executed at " . date('Y-m-d H:i:s') . " - Cleanup completed\n";
file_put_contents(__DIR__ . '/cron-cleanup.log', $logMessage, FILE_APPEND | LOCK_EX);

echo "Cron job completed successfully at " . date('Y-m-d H:i:s') . "\n";
?>
