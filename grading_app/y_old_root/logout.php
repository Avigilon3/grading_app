<?php
session_start();
require 'includes/config.php'; // optional, not required for logout
session_destroy();
header("Location: index.php");
exit;
?>
