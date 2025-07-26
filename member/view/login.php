<?php

use App\Member\Repository\MemberRepositoryFactory;
use App\Member\Service\MemberService;

error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require __DIR__ . '/../../bootstrap.php';



$repo     = MemberRepositoryFactory::create();
$service  = new MemberService($repo);
$message  = '';



if(isset($_COOKIE['remember'])){
    $_SESSION['id'] = $_COOKIE['remember'];
    header('Location: /boardProject/board/view/board.php');
    setcookie('remember', $_COOKIE['remember'], time()+86400);
    exit;
}

if($_SERVER["REQUEST_METHOD"] === "POST") {
    $ok = $service->login(
        trim($_POST['id']),
        $_POST['password']
    );
    $_SESSION['id'] = $_POST['id'];

    if ($ok) {
        header('Location: /boardProject/board/view/board.php');
        exit;
    }
    $message = '아이디 또는 비밀번호를 확인하세요.';

}
?>

<html>
<head>

    <title>게시판 로그인</title>
</head>

<body>
<h1> 로그인 페이지</h1>
<br>
<form action="/boardProject/member/view/login.php" method="post">
    <input type="text" name="id" placeholder="아이디">
    <br>
    <input type="password" name="password" placeholder="비밀번호">
    <br>
    <button type="submit" name="login">로그인</button>
</body>
</form>
</html>