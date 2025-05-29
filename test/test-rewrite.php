<?php
// Simple test file to verify mod_rewrite is working
echo "<!DOCTYPE html>";
echo "<html><head><title>Rewrite Test</title></head><body>";
echo "<h1>Mod Rewrite Test</h1>";
echo "<p>If you can see this page, mod_rewrite is working!</p>";
echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Query String: " . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'None') . "</p>";
echo "</body></html>";
?>
