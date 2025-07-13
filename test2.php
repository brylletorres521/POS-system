#!<?php echo PHP_BINARY; ?>
<?php
echo "<h1>PHP Handler Test</h1>";
echo "<p>If you can see this, your server is processing PHP files correctly with explicit handler.</p>";
echo "<p>Current PHP version: " . phpversion() . "</p>";
echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
?> 