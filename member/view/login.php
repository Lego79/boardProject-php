<?php

use App\Member\Repository\MemberRepositoryFactory;
use App\Member\Service\MemberService;

error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
require __DIR__ . '/../../bootstrap.php';



$memberRepo     = MemberRepositoryFactory::create();
$memberService  = new MemberService($memberRepo);
$message  = '';

$memberService->handlePost(); // POST 요청 처리 위임
 
?>

<html>
<head>

    <title>게시판 로그인</title>
</head>

<body>
<h1> 로그인 페이지</h1>
<br>
    <div class="form-group">
        <form action="/boardProject/member/view/login.php" method="post">
            <input type="text" name="id" placeholder="아이디">
            <br>
            <input type="password" name="password" placeholder="비밀번호">
            <br>
            <button type="submit" name="login">로그인</button>
        </form>
    </div>
    </body>
</html>