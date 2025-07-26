<?php
use App\Board\Repository\BoardRepositoryFactory;
use App\Board\Service\BoardService;

require dirname(__DIR__, 2) . '/bootstrap.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
$boardId = $_GET['board_id'] ?? '';              

$boardRepo    = BoardRepositoryFactory::create($boardId); // ← key 전달
$boardService = new BoardService($boardRepo);

$board = $boardService->getBoardById ($boardId);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateBoard'])) {
    $ok = $boardService->updateBoard(
        $boardId,
        trim($_POST['title']    ?? ''),
        trim($_POST['contents'] ?? '')
    );
    if ($ok) {
        header('Location: /boardProject/board/view/board.php');
        exit;
    }
    $msg = '수정 실패';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"><title>글 수정</title></head>
<body>
<h1>글 수정</h1>
<?= isset($msg) ? "<p style='color:red'>{$msg}</p>" : '' ?>

<form method="post">
    제목<br>
    <input type="text" name="title" value="<?= htmlspecialchars($board['title']) ?>"><br><br>
    내용<br>
    <textarea name="contents" rows="6"><?= htmlspecialchars($board['contents']) ?></textarea><br>
    <button type="submit" name="updateBoard">수정하기</button>
</form>
</body>
</html>
