<?php
declare(strict_types=1);

namespace App\Comment\Repository;
use App\Support\JsonHelper;

final class FileCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private string $commentDir) {}


    public function createComment(string $boardId, string $memberId, string $comment): void
    {
        $comments = JsonHelper::read($this->commentDir, ['comments' => []]);
        $comments['comments'][] = [
            'id'        => uniqid('', true),
            'board_id'  => $boardId,
            'member_id' => $memberId,
            'comment'   => $comment,
        ];

        JsonHelper::write($this->commentDir, $comments);
    }

    public function getComments(string $boardId): array
    {
        $comments = JsonHelper::read($this->commentDir, ['comments' => []]);

        $out = [];
        foreach ($comments['comments'] as $comment) {
            if ($comment['board_id'] === $boardId) {
                $out[] = [
                    'comment_id'        => $comment['id'],
                    'member_id' => htmlspecialchars($comment['member_id']),
                    'board_id' => htmlspecialchars($comment['board_id']),
                    'comment'   => htmlspecialchars($comment['comment']),
                ];
            }
        }

        return $out;
    }

    public function updateComment(string $commentId, string $memberId, string $newComment, string $boardId): void
    {
        $comments = JsonHelper::read($this->commentDir, ['comments' => []]);

        $commentId = (string)$commentId;
        $memberId = (string)$memberId;
        $newComment = (string)$newComment;
        $boardId = (string)$boardId;
        $updated = false;
            error_log('updateComment: commentId='.$commentId.', memberId='.$memberId.', newComment='.$newComment.', boardId='.$boardId);


        foreach ($comments['comments'] as $comment) {
            if((string)($comment['id']?? '') === $commentId &&
               (string)($comment['member_id'] ?? '') === $memberId &&
               (string)($comment['board_id'] ?? '') === $boardId) {
                $comment['comment'] = $newComment;
                $updated = true;
                break;
            }
        }
        unset($comment);
        if($updated) {
            JsonHelper::write($this->commentDir, $comments);
        }
    }

    public function deleteComment(string $id): void
    {

    }

}
