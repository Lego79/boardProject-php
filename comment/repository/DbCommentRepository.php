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

    /* 댓글 목록 (최신순) */
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

    /* 댓글 수정 */
    public function updateComment(
        string $commentId,
        string $memberId,
        string $newComment,
        string $boardId
    ): void {
        $commentId = (int)$commentId;
        $newComment = (string)$newComment;

        // 1) 로그로 파라미터 확인
        error_log(
            'updateComment: commentId=' . $commentId .
            ', memberId='   . $memberId   .
            ', newComment=' . $newComment .
            ', boardId='    . $boardId
        );

        // 2) 쿼리 준비: comment만 갱신, id 기준
        $sql = 'UPDATE comments
                SET comment = ?
                WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
            ?: throw new RuntimeException($this->conn->error);

        // 3) bind_param 타입·변수 순서 수정
        // 's' → $newComment (string)
        // 'i' → $commentId  (int)
        $stmt->bind_param(
            'si',
            $newComment,
            $commentId
        );

        // 4) 실행 및 에러 체크
        $stmt->execute()
            ?: throw new RuntimeException($stmt->error);

        // 5) 영향 받은 행(row) 수 로그
        error_log('updateComment affected_rows=' . $stmt->affected_rows);
    }


    /* 댓글 삭제 */
    public function deleteComment(string $commentId): void
    {
        $sql = 'DELETE FROM comments WHERE id = ?';
        $stmt = $this->conn->prepare($sql)
             ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('i', $commentId);
        $stmt->execute();
    }
}
