<?php
session_start();
session_unset();
session_destroy();
setcookie('remember', '/boardProject/member/view/login.php', time() - 3600, '/');
header("Location: /boardProject/member/view/login.php");
exit;
