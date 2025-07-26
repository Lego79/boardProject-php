<?php
declare(strict_types=1);

namespace App\Comment\Repository;

use mysqli;
use RuntimeException;

class DbCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private mysqli $conn) {}

    /* 댓글 저장 */
    public function createComment(string $boardId, string $writer, string $comment): void
    {
        $sql = 'INSERT INTO comments (member_id, board_id, comment)
                VALUES (?, ?, ?)';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('sis', $writer, $boardId, $comment);
        $stmt->execute();
    }

    /* 댓글 목록 (최신순) */
    public function getComments(string $boardId): array
    {
        $sql = 'SELECT id, board_id, member_id AS writer, comment
                FROM comments
                WHERE board_id = ?
                ORDER BY id DESC';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('i', $boardId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    /* 댓글 수정 */
    public function updateComment(string $commentId, string $memberId, string $newComment, string $boardId): void
    {
        $sql = 'UPDATE comments SET comment = ? WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('si', $newText, $id);
        $stmt->execute();
    }

    /* 댓글 삭제 */
    public function deleteComment(string $id): void
    {
        $sql = 'DELETE FROM comments WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
}
