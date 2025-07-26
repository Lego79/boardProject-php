<?php
namespace App\Board\Repository;

interface BoardRepositoryInterface
{
    public function createBoard(string $title, string $content, string $author): string;
    public function getBoards(): array;
    public function getBoardById(int|string $key): ?array;
    public function updateBoard(int|string $key, string $title, string $content): bool; 
}
