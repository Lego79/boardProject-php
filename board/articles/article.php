<?php
/* ─── 공통 부트스트랩 ─── */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Board\Repository\BoardRepositoryFactory;
use App\Board\Service\BoardService;
use App\Comment\Repository\CommentRepositoryFactory;
use App\Comment\Service\CommentService;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$boardId = $_GET['board_id'] ?? '';               // 글 식별자
//board 서비스
$boardRepo     = BoardRepositoryFactory::create();
$boardservice  = new BoardService($boardRepo);

$board = $boardservice->getBoardById($boardId);


/* ─── 댓글 서비스 ─── */
$commentRepo     = CommentRepositoryFactory::create();
$commentService  = new CommentService($commentRepo);

/* 작성/수정/삭제 처리 */
$commentService->createComment($boardId);
$commentService->updateComment();
$commentService->deleteComment();

/* 댓글 목록 */
$comments = $commentService->getComments($boardId);


?>
<!DOCTYPE html><html lang="ko"><head>
<meta charset="utf-8"><title><?= htmlspecialchars($board['title']) ?></title></head>
<body>
<h2><?= htmlspecialchars($board['title']) ?></h2>
<p><?= nl2br(htmlspecialchars($board['contents'])) ?></p>
<p>작성자: <?= $board['member_id'] ?></p>

<hr>
<?php if ($comments): ?>
<h3>댓글</h3>
<ul>
<?php foreach ($comments as $c): ?>
  <li>
    <form method="post" style="display:inline">
      <strong><?= htmlspecialchars($c['member_id']) ?></strong>
      <textarea name="comment" rows="2"><?= htmlspecialchars($c['comment']) ?></textarea>
      <input type="hidden" name="board_id"     value="<?= $c['board_id'] ?>">
      <input type="hidden" name="member_id"    value="<?= $c['member_id'] ?>">
      <input type="hidden" name="comment_id"   value="<?= $c['id'] ?>">
      <button type="submit" name="editComment">수정</button>
      <button type="submit" name="deleteComment">삭제</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
<hr>
<?php endif; ?>

<form method="post">
  <!-- board_id/파일명을 key 로 사용 -->
  <input type="hidden" name="board_id" value="<?= htmlspecialchars($boardId) ?>">
  <textarea name="comment" rows="3" required></textarea>
  <button type="submit" name="writeComment" value="1">댓글 작성</button>
</form>

<br><a href="/boardProject/board/view/board.php">목록으로</a>
</body></html>
