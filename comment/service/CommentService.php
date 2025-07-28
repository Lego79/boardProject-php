<?php
namespace App\Comment\Service;

use App\Comment\Repository\CommentRepositoryInterface;

final class CommentService
{
    public function __construct(private CommentRepositoryInterface $repo) {}

    /** 요청 메서드 분기 및 처리 통합 */
    public function handlePost(string $boardId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['writeComment'])) {
            $this->createComment($boardId);
        } elseif (isset($_POST['editComment'])) {
            $this->updateComment();
        } elseif (isset($_POST['deleteComment'])) {
            $this->deleteComment();
        }
    }

    private function createComment(string $boardId): void
    {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') return;

        $this->repo->createComment($boardId, $_SESSION['id'] ?? '익명', $comment);
        $this->redirect();
    }

    private function updateComment(): void
    {
        if (($_POST['member_id'] ?? '') !== ($_SESSION['id'] ?? '')) {
            die('작성자만 수정할 수 있습니다.');
        }

        $comment = trim($_POST['comment'] ?? '');
        $this->repo->updateComment(
            $_POST['comment_id'] ?? '',
            $_POST['member_id'] ?? '',
            $comment,
            $_POST['board_id'] ?? ''
        );
        $this->redirect();
    }

    private function deleteComment(): void
    {
        if (($_POST['member_id'] ?? '') !== ($_SESSION['id'] ?? '')) {
            die('작성자만 삭제할 수 있습니다.');
        }

        $this->repo->deleteComment($_POST['comment_id'] ?? '');
        $this->redirect();
    }

    public function getComments(string $boardId): array
    {
        return $this->repo->getComments($boardId);
    }

    private function redirect(): void
    {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
