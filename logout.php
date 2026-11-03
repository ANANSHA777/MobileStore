<?php
/**
 * logout.php — Session Destruction Handler
 */
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
