<?php
namespace App\Comment\Repository;

interface CommentRepositoryInterface
{
    /** 댓글 쓰기 */
    public function createComment(string $articleKey, string $writer, string $comment): void;

    /** 댓글 목록(최신순) */
    public function getComments(string $articleKey): array;

    /** 댓글 수정 (작성자 검증은 Service에서) */
    public function updateComment(string $id, string $newText): void;

    /** 댓글 삭제 (작성자 검증은 Service에서) */
    public function deleteComment(string $id): void;
}
