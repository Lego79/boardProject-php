<?php
declare(strict_types=1);

namespace App\Board\Service;

use App\Board\Repository\BoardRepository;
use RuntimeException;

class BoardService
{
    public function __construct(private BoardRepository $repo) {}

    public function handlePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['writePost'])) {
                $this->createBoard();
                return;
            }
            if (isset($_POST['logout'])) {
                $this->logout();
                return;
            }
            // (선택) 좋아요 토글 핸들링: 같은 상세 페이지에서 폼 submit로 처리할 경우
            if (isset($_POST['toggleLike']) && isset($_POST['board_id'])) {
                $this->toggleLike((int)$_POST['board_id']);
                return;
            }
        }
        // 정렬은 GET 처리
    }

    public function createBoard(): void
    {
        $title    = trim($_POST['title']   ?? '');
        $content  = trim($_POST['content'] ?? '');
        $memberId = $_SESSION['id'] ?? null;

        if (!$memberId) {
            throw new RuntimeException('로그인이 필요합니다.');
        }
        if ($title === '' || $content === '') {
            throw new RuntimeException('제목/내용은 비어 있을 수 없습니다.');
        }

        $this->repo->createBoard($title, $content, $memberId);
        header('Location: /boardProject/board/view/board.php');
        exit;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        setcookie('remember', '', time() - 3600, '/');
        header('Location: /boardProject/member/view/login.php');
        exit;
    }

    public function getBoards(): array
    {
        return $this->repo->getBoards();
    }

    public function countBoard(): int
    {
        $cnt = $this->repo->countBoard();
        error_log('Counting boards: ' . $cnt);
        return $cnt;
    }

    public function getBoardById(int $boardId): ?array
    {
        return $this->repo->getBoardById($boardId);
    }

    public function updateBoard(int $boardId, string $title, string $contents): bool
    {
        return $this->repo->updateBoard($boardId, $title, $contents);
    }

    public function pagination(
        int    $page,
        int    $perPage,
        string $order,
        ?int   $menuId  = null,
        string $keyword = '',
        string $target  = 'title',
    ): array {
        return $this->repo->pagination($page, $perPage, $order, $menuId, $keyword, $target);
    }

    public function getMenuFilters(): array
    {
        return $this->repo->getMenuFilters();
    }

    /* -------------------- Likes -------------------- */

    public function toggleLike(int $boardId): void
    {
        $memberId = $_SESSION['id'] ?? null;
        if (!$memberId) {
            throw new RuntimeException('로그인이 필요합니다.');
        }
        if ($this->repo->isLikedByUser($memberId, $boardId)) {
            $this->repo->removeLike($memberId, $boardId);
        } else {
            $this->repo->addLike($memberId, $boardId);
        }

        // 현재 페이지로 돌아가기
        header('Location: /boardProject/board/view/boardDetail.php?board_id=' . $boardId);
        exit;
    }

    public function getLikeCount(int $boardId): int
    {
        return $this->repo->getLikeCount($boardId);
    }

    public function isLikedByUser(int $boardId): bool
    {
        $memberId = $_SESSION['id'] ?? null;
        if (!$memberId) return false;
        return $this->repo->isLikedByUser($memberId, $boardId);
    }
}
