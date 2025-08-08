<?php
namespace App\Board\Repository;

interface BoardRepository
{
    public function createBoard(string $title, string $content, string $memberId): string;
    public function getBoards(): array;
    public function getBoardById(int|string $boardId): ?array;
    public function updateBoard(int|string $boardId, string $title, string $content): bool; 
    public function pagination(
        int    $page,
        int    $perPage,
        string $order,
        ?int   $menuId  = null,
        string $keyword = '',
        string $target  = 'title',
    ): array;    
    public function countBoard(): int ;
    public function orderByDesc(): array;
    public function orderByAsc(): array;
    public function getMenuFilters(): array;   


}
