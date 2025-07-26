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

/* POST 처리 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['nickname'], $_POST['password'])) {
    $ok = $service->register(
        trim($_POST['id']),
        trim($_POST['nickname']),
        $_POST['password']
    );


    if ($ok) {
        header('Location: login.php');      // 가입 성공 → 로그인 화면
        exit;
    }
    $message = '아이디 또는 닉네임이 이미 사용 중입니다.';
}
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
    <button type="submit">회원가입</button>
</form>

<form action="login.php" method="get" style="margin-top:1rem">
    <button type="submit">로그인 하러 가기</button>
</form>
</body>
</html>