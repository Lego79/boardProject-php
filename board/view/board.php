<?php
declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');
require dirname(__DIR__, 2) . '/bootstrap.php';
use App\Board\Repository\BoardRepositoryFactory;
use App\Board\Service\BoardService;
if (empty($_SESSION['id'])) {
    header('Location: /boardProject/member/view/login.php');
    exit;
}
$boardService = new BoardService(BoardRepositoryFactory::create());
$boardService->handlePost();  

//전체 게시글 수
$numberOfBoards = $boardService->countBoard();


//페이징 관리
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage    = 10;
$pagination = $boardService->pagination($page, $perPage);

// 뷰에 전달
$totalPages  = $pagination['totalPages'];
$currentPage = $pagination['currentPage'];
$boards      = $pagination['boards'];

//페이징 limit 설정
// 표시할 최대 링크 개수
$maxLinks = 10;

// 슬라이딩 윈도우 계산
$half = (int)floor($maxLinks / 2);
if ($totalPages <= $maxLinks) {
    $startPage = 1;
    $endPage   = $totalPages;
} else {
    $startPage = $currentPage - $half + 1;
    $endPage   = $currentPage + $half;

    // 경계 처리
    if ($startPage < 1) {
        $startPage = 1;
        $endPage   = $maxLinks;
    } elseif ($endPage > $totalPages) {
        $endPage   = $totalPages;
        $startPage = $totalPages - $maxLinks + 1;
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>게시판</title>
  <link rel="stylesheet" href="/boardProject/public/css/style.css">
  <style>

  </style>

</head>
<body>
  <div class="container">
    <div class="welcome">
      <?= htmlspecialchars($_SESSION['id'], ENT_QUOTES) ?>님, 환영합니다!
    </div>

    <table>
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
                <a class="edit-link" href="/boardProject/board/view/boardEdit.php?board_id=<?= urlencode($b['board_id']) ?>">
                  수정
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="pagination">
      <nav class="pagination">
        <ul>
          <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="<?= $i === $currentPage ? 'active' : '' ?>">
              <a href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>

    <hr>

    <h2>글 작성</h2>
    <form method="post">
      <div class="form-group">
        <label for="title">제목</label>
        <input id="title" type="text" name="title" required>
      </div>
      <div class="form-group">
        <label for="content">내용</label>
        <textarea id="content" name="content" rows="6" required></textarea>
      </div>
      <button type="submit" name="writePost">작성</button>
    </form>

    <form method="post">
      <button type="submit" name="logout" class="btn-logout">로그아웃</button>
    </form>
  </div>
</body>
</html>
