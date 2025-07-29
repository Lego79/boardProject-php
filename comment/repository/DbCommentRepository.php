<?php
declare(strict_types=1);

namespace App\Comment\Repository;

use mysqli;
use RuntimeException;

class DbCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private mysqli $conn) {}

    /* 댓글 저장 */
    public function createComment(string $boardId, string $memberId, string $comment): void
    {
        $comment = (string)$comment;
        error_log('createComment: boardId='.$boardId.', memberId='.$memberId.', comment='.$comment);
        $sql = 'INSERT INTO comments (board_id, member_id, comment)
                VALUES (?, ?, ?)';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('iss', $boardId, $memberId, $comment);
        $stmt->execute();
    }

    public function getComments(string $boardId): array
    {
        $sql = 'SELECT id, board_id, member_id, comment
                FROM comments
                WHERE board_id = ?
                ORDER BY id DESC';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('i', $boardId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    public function updateComment(
        string $commentId,
        string $memberId,
        string $newComment,
        string $boardId
    ): void {
        $commentId = (int)$commentId;
        $newComment = (string)$newComment;
        $sql = 'UPDATE comments
                SET comment = ?
                WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
            ?: throw new RuntimeException($this->conn->error);


        $stmt->bind_param(
            'si',
            $newComment,
            $commentId
        );

        $stmt->execute()
            ?: throw new RuntimeException($stmt->error);

        error_log('updateComment affected_rows=' . $stmt->affected_rows);
    }


    public function deleteComment(string $commentId): void
    {
        $sql = 'DELETE FROM comments WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
    }
}
