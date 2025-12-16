<?php
session_start();
session_destroy();
header('Location: check.html');
exit;
?>