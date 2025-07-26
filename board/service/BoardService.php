<?php
namespace App\Board\Service;

use App\Board\Repository\BoardRepositoryInterface;

class BoardService
{
    public function __construct(private BoardRepositoryInterface $repo) {}

    public function createBoard(): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST') 
            {
                //중괄호 생략 하지 않는 것으로
                return;
            }

        $path = $this->repo->createBoard(
            $_POST['title']   ?? '',
            $_POST['content'] ?? '',
            $_SESSION['id']   ?? '익명'
        );
        header('Location: /boardProject/board/view/board.php');
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