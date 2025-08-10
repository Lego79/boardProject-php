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

//ORDER의 값은 기본값이 DESC이다,
//order의 값이 'asc' 이냐? 맞느면 asc를 반환하고 아니면 desc를 반환 해라

// 페이징 관리
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 14;
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$keyword    = trim($_GET['keyword'] ?? '');      
$target     = $_GET['target'] ?? 'title';      

// 게시글 목록을 가져옵니다.
$pagination = $boardService->pagination(
    $page,
    $perPage,
    $order,
    $menuId = null,
    $keyword,
    $target
);


$baseQuery = [
    'order'  => $order,
    'target' => $target,
    'keyword' => $keyword,
];

// 뷰로 전달
$totalPages  = $pagination['totalPages'];
$currentPage = $pagination['currentPage'];
$boards      = $pagination['boards'];

// 페이지 번호 슬라이딩 윈도우
$windowSize = 10;
$halfWindow = (int)floor($windowSize / 2);
$startPage  = max(1, $currentPage - $halfWindow);
$endPage    = min($totalPages, $startPage + $windowSize - 1);

//필터용 메뉴 가져오기
$menus = $boardService->getMenuFilters();  


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
    <nav class="top-nav" style="margin-bottom:1rem;">
      <a href="/boardProject/board/view/board.php"
        style="font-weight:bold; text-decoration:none;">
        📋 전체 글
      </a>
    </nav>
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
              <a href="/boardProject/board/articles/article.php?board_id=<?= urlencode((string)$b['board_id']) ?>">
                <?= htmlspecialchars($b['title'], ENT_QUOTES) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($b['member_id'], ENT_QUOTES) ?></td>
            <td>
              <?php if ($editable): ?>
                <a class="edit-link" href="/boardProject/board/view/boardEdit.php?board_id=<?= urlencode((string)$b['board_id']) ?>">
                  수정
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>     <!-- 페이지가 2개 이상일 때만 표시 -->
    <nav class="pagination">
      <ul>
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <?php
            $baseQuery['page'] = $i;             // 이전 답변의 $baseQuery 재사용
            $url = '?' . http_build_query($baseQuery);
          ?>
          <li class="<?= $i === $currentPage ? 'active' : '' ?>">
            <a href="<?= $url ?>" aria-current="<?= $i === $currentPage ? 'page' : '' ?>">
              <?= $i ?>
            </a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>


    <!-- 메뉴 + 검색 -->
    <form method="get" class="filter-form" style="display:flex; gap:6px; margin:1em 0;">

      <select name="target">
        <option value="title"   <?= ($_GET['target']??'')==='title'   ?'selected':''?>>제목+내용</option>
        <option value="member"  <?= ($_GET['target']??'')==='member'  ?'selected':''?>>작성자</option>
        <option value="comment" <?= ($_GET['target']??'')==='comment' ?'selected':''?>>댓글</option>
      </select>

    <input type="text" name="keyword" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

      <button type="submit">검색</button>

      <!-- 정렬·페이지 파라미터 유지 -->
      <input type="hidden" name="page"  value="<?= $currentPage ?>">
      <input type="hidden" name="order" value="<?= $order ?>">
    </form>


    <!-- 정렬 버튼 -->
    <form method="get" class="sort-form" style="margin:1em 0;">
      <input type="hidden" name="page" value="<?= (int)$currentPage ?>">
      <input type="hidden" name="target" value="<?= htmlspecialchars($target, ENT_QUOTES) ?>">
      <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword, ENT_QUOTES) ?>">
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
