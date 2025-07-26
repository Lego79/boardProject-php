<?php
declare(strict_types=1);

namespace App\Comment\Repository;
use App\Support\JsonHelper;

final class FileCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private string $CommentDir) {}


    public function createComment(string $boardId, string $memberId, string $comment): void
    {
        $comments = JsonHelper::read($this->CommentDir, ['comments' => []]);
        $comments['comments'][] = [
            'id'        => uniqid('', true),
            'board_id'  => $boardId,
            'member_id' => $memberId,
            'comment'   => $comment,
        ];

        JsonHelper::write($this->CommentDir, $comments);
    }

    public function getComments(string $boardId): array
    {
        $comments = JsonHelper::read($this->CommentDir, ['comments' => []]);

        $out = [];
        foreach ($comments['comments'] as $comment) {
            if ($comment['board_id'] === $boardId) {
                $out[] = [
                    'id'        => $comment['id'],
                    'member_id' => htmlspecialchars($comment['member_id']),
                    'board_id' => htmlspecialchars($comment['board_id']),
                    'comment'   => htmlspecialchars($comment['comment']),
                ];
            }
        }

        error_log('FileCommentRepository::getComments() => '.count($out).' rows');
        return $out;
    }

    public function updateComment(string $commentId, string $newText): void
    {
        
    }

    public function deleteComment(string $id): void
    {

    }

}
