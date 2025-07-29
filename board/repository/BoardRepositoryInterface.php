<?php
namespace App\Board\Repository;

interface BoardRepositoryInterface
{
    public function createBoard(string $title, string $content, string $memberId): string;
    public function getBoards(): array;
    public function getBoardById(int|string $boardId): ?array;
    public function updateBoard(int|string $boardId, string $title, string $content): bool; 
}
