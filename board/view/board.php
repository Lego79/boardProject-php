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

// ✅ 정렬 먼저 해석
//ORDER의 값은 기본값이 DESC이다,
//order의 값이 'asc' 이냐? 맞느면 asc를 반환하고 아니면 desc를 반환 해라
// ✅ 정렬 포함해서 호출

// 페이징 관리
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 14;

$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$pagination = $boardService->pagination($page, $perPage, $order);






// 뷰로 전달
$totalPages  = $pagination['totalPages'];
$currentPage = $pagination['currentPage'];
$boards      = $pagination['boards'];

// 페이지 번호 슬라이딩 윈도우
$windowSize = 10;
$halfWindow = (int)floor($windowSize / 2);
$startPage  = max(1, $currentPage - $halfWindow);
$endPage    = min($totalPages, $startPage + $windowSize - 1);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>게시판</title>
  <link rel="stylesheet" href="/boardProject/public/css/style.css">
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

    <!-- 페이지네이션: order 유지 -->
    <nav class="pagination">
      <ul>
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="<?= $i === $currentPage ? 'active' : '' ?>">
            <a
              href="?page=<?= $i ?>&order=<?= urlencode($order) ?>"
              aria-current="<?= $i === $currentPage ? 'page' : '' ?>"
            >
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

    <!-- 정렬 버튼 -->
    <form method="get" class="sort-form" style="margin:1em 0;">
      <input type="hidden" name="page" value="<?= (int)$currentPage ?>">
      <button type="submit" name="order" value="ASC">오래된 순</button>
      <button type="submit" name="order" value="DESC">최신 순</button>
    </form>

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
