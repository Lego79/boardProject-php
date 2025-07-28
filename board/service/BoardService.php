<?php
namespace App\Board\Service;

use App\Board\Repository\BoardRepositoryInterface;

class BoardService
{
    public function __construct(private BoardRepositoryInterface $repo) {}


    public function handlePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['writePost'])) {
            $this->createBoard();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
            $this->logout();
        }
    }

    public function createBoard(): void
    {
        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');
        $author  = $_SESSION['id']         ?? '익명';


        $this->repo->createBoard($title, $content, $author);
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

 
    public function getBoardById(int|string $boardId): ?array
    {
        return $this->repo->getBoardById($boardId);
    }


    public function updateBoard(int|string $boardId, string $title, string $contents): bool
    {
        return $this->repo->updateBoard($boardId, $title, $contents);
    }
}
