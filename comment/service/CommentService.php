<?php
namespace App\Comment\Service;

use App\Comment\Repository\CommentRepositoryInterface;

final class CommentService
{
    public function __construct(private CommentRepositoryInterface $repo) {}

    public function createComment(string $boardId): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_POST['writeComment'])) return;

        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') return;

        $this->repo->createComment($boardId, $_SESSION['id'] ?? '익명', $comment);
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }

    public function updateComment(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_POST['editComment'])) return;

        if (($_POST['member_id'] ?? '') !== ($_SESSION['id'] ?? '')) die('작성자만 수정');
        $this->repo->updateComment($_POST['comment_id'], $_POST['member_id'] ?? '', trim($_POST['comment'] ?? ''), $_POST['board_id']);
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }

    public function deleteComment(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_POST['deleteComment'])) return;

        if (($_POST['writer'] ?? '') !== ($_SESSION['id'] ?? '')) die('작성자만 삭제');
        $this->repo->deleteComment($_POST['id'] ?? '');
        header('Location: '.$_SERVER['REQUEST_URI']); exit;
    }

    public function getComments(string $boardId): array
    {
        return $this->repo->getComments($boardId);
    }
}
