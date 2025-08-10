<?php
declare(strict_types=1);

namespace App\Board\Repository;

interface BoardRepository
{
    // Board CRUD / Query
    public function createBoard(string $title, string $content, string $memberId): int;
    public function getBoards(): array;
    public function getBoardById(int $boardId): ?array;
    public function updateBoard(int $boardId, string $title, string $content): bool;
    public function countBoard(): int;

    // Pagination + Filters
    public function pagination(
        int    $page,
        int    $perPage,
        string $order,
        ?int   $menuId   = null,
        string $keyword  = '',
        string $target   = 'title',
    ): array;

    public function orderByDesc(): array;
    public function orderByAsc(): array;
    public function getMenuFilters(): array;

    // Likes
    public function addLike(string $memberId, int $boardId): bool;
    public function removeLike(string $memberId, int $boardId): bool;
    public function isLikedByUser(string $memberId, int $boardId): bool;
    public function getLikeCount(int $boardId): int;
}
