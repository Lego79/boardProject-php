<?php
/* ─── 공통 부트스트랩 ─── */
require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Board\Repository\BoardRepositoryFactory;
use App\Board\Service\BoardService;
use App\Comment\Repository\CommentRepositoryFactory;
use App\Comment\Service\CommentService;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$boardIdRaw = $_GET['board_id'] ?? null;

if ($boardIdRaw === null || !ctype_digit((string)$boardIdRaw)) {
    http_response_code(400);
    echo '잘못된 요청입니다.(board_id)';
    exit;
}

$boardId = (int)$boardIdRaw;
if ($boardId <= 0) {
    http_response_code(400);
    echo '잘못된 게시글 번호입니다.';
    exit;
}


/* ─── 게시글 서비스 ─── */
$boardRepo     = BoardRepositoryFactory::create();
$boardservice  = new BoardService($boardRepo);
$board         = $boardservice->getBoardById($boardId);

/* 게시글이 없으면 404 처리 */
if (!$board) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
      <meta charset="utf-8">
      <title>게시글을 찾을 수 없습니다</title>
      <link rel="stylesheet" href="/boardProject/public/css/style.css">
    </head>
    <body>
      <div class="container">
        <h2 class="welcome">게시글을 찾을 수 없습니다.</h2>
        <p class="back-link"><a href="/boardProject/board/view/board.php">목록으로</a></p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

/* ─── 댓글 서비스 ─── */
$commentRepo     = CommentRepositoryFactory::create();
$commentService  = new CommentService($commentRepo);

/* 작성/수정/삭제 처리 */
$commentService->handlePost($boardId);

/* 댓글 목록 */
$comments = $commentService->getComments($boardId);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($board['title'] ?? '게시글') ?></title>
  <link rel="stylesheet" href="/boardProject/public/css/style.css">
</head>
<body>
  <div class="container">
    <!-- 상단 헤더/경로 -->
    <div class="welcome">
      <a href="/boardProject/board/view/board.php">← 목록으로</a>
    </div>

    <!-- 게시글 본문 -->
    <article class="post">
      <h2 class="post-title"><?= htmlspecialchars($board['title']) ?></h2>
      <p class="post-meta">작성자: <strong><?= htmlspecialchars($board['member_id']) ?></strong></p>
      <div class="post-content">
        <?= nl2br(htmlspecialchars($board['contents'])) ?>
      </div>
    </article>

    <form method="post" action="">
      <input type="hidden" name="board_id" value="<?= (int)$boardId ?>">
      <button type="submit" name="toggleLike" class="btn">
        <?= $boardservice->isLikedByUser((int)$boardId) ? '💔 취소' : '👍 좋아요' ?>
        (<?= $boardservice->getLikeCount((int)$boardId) ?>)
      </button>
    </form>

    <hr class="divider">

    <!-- 댓글 영역 -->
    <section class="comments">
      <h3 class="section-title">댓글</h3>

      <?php if ($comments): ?>
        <ul class="comment-list">
          <?php foreach ($comments as $c): ?>
            <li class="comment-item">
              <div class="comment-head">
                <strong class="comment-author"><?= htmlspecialchars($c['member_id']) ?></strong>
              </div>

              <!-- 댓글 수정/삭제 폼 (인라인 편집) -->
              <form method="post" class="comment-form-inline">
                <textarea
                  name="comment"
                  rows="2"
                  class="comment-textarea"
                ><?= htmlspecialchars($c['comment']) ?></textarea>

                <input type="hidden" name="board_id"   value="<?= htmlspecialchars($c['board_id']) ?>">
                <input type="hidden" name="member_id"  value="<?= htmlspecialchars($c['member_id']) ?>">
                <input type="hidden" name="comment_id" value="<?= htmlspecialchars($c['id']) ?>">

                <div class="comment-actions">
                  <button type="submit" name="editComment" class="btn">수정</button>
                  <button type="submit" name="deleteComment" class="btn btn-danger">삭제</button>
                </div>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">등록된 댓글이 없습니다.</p>
      <?php endif; ?>




      <hr class="divider">

      <!-- 신규 댓글 작성 -->
      <h4 class="comment-write-title">댓글 작성</h4>
      <form method="post" class="comment-form-new">
        <input type="hidden" name="board_id" value="<?= htmlspecialchars($boardId) ?>">
        <div class="form-group">
          <label for="newComment" class="sr-only">댓글 내용</label>
          <textarea id="newComment" name="comment" rows="3" class="comment-textarea" required></textarea>
        </div>
        <div class="form-actions">
          <button type="submit" name="writeComment" value="1" class="btn">댓글 작성</button>
        </div>
      </form>
    </section>

    <p class="back-link" style="margin-top: 1rem;">
      <a href="/boardProject/board/view/board.php">목록으로</a>
    </p>
  </div>
</body>
</html>
