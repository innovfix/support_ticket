<?php
// Simple PHP test file
echo "PHP is working!";
echo "<br>PHP Version: " . phpversion();
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Current directory: " . __DIR__;
echo "<br>Document root: " . $_SERVER['DOCUMENT_ROOT'] ?? 'Not set';
?>






