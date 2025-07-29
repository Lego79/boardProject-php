<?php
declare(strict_types=1);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

/* 오토로더 + 부트스트랩 */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Board\Repository\BoardRepositoryFactory;
use App\Board\Service\BoardService;

/* 로그인 확인 */
if (empty($_SESSION['id'])) {
    header('Location: /boardProject/member/view/login.php');
    exit;
}

$boardService = new BoardService(
    BoardRepositoryFactory::create()
);
$boardService->handlePost();  

$boards = $boardService->getBoards();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>게시판</title>
</head>
<body>

<h1><?= htmlspecialchars($_SESSION['id'], ENT_QUOTES) ?>님, 환영합니다!</h1>

<table border="1" cellspacing="0" cellpadding="4">
  <thead>
    <tr>
      <th>번호</th>
      <th>제목</th>
      <th>작성자</th>
      <th>수정</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($boards as $b): ?>
      <?php $editable = ($b['member_id'] === $_SESSION['id']); ?>
      <tr>
        <td><?= $b['no'] ?></td>
        <td>
          <a href="/boardProject/board/articles/article.php?board_id=<?= urlencode($b['board_id']) ?>">
            <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
          </a>
        </td>
        <td><?= htmlspecialchars($b['member_id'], ENT_QUOTES) ?></td>
        <td>
          <?php if ($editable): ?>
            <a href="/boardProject/board/view/boardEdit.php?board_id=<?= urlencode($b['board_id']) ?>">수정</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<hr>
<h2>글 작성</h2>
<form method="post">
  제목 <input type="text" name="title" required><br><br>
  내용 <textarea name="content" rows="6" required></textarea><br>
  <button type="submit" name="writePost">작성</button>
</form>

<form method="post" style="margin-top:1rem">
  <button type="submit" name="logout">로그아웃</button>
</form>

</body>
</html>
