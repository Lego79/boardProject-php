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
<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"><title>File I/O 회원가입</title></head>
<body>
<h1>회원가입</h1>

<?php if ($message): ?>
    <p style="color:red"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">
    <input type="text"     name="id"       placeholder="아이디"   required><br>
    <input type="text"     name="nickname" placeholder="별명"     required><br>
    <input type="password" name="password" placeholder="비밀번호" required><br>
    <button type="submit" name='signIn'>회원가입</button>
</form>

<form action="login.php" method="get" style="margin-top:1rem">
    <button type="submit">로그인 하러 가기</button>
</form>
</body>
</html>