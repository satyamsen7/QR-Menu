<?php
// Test file to debug routing
echo "<h1>Routing Test</h1>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>PHP Self: " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p>Query String: " . $_SERVER['QUERY_STRING'] . "</p>";
echo "<p>GET parameters:</p>";
echo "<pre>";
print_r($_GET);
echo "</pre>";
?> 