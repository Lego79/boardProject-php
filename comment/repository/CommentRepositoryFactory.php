<?php
namespace App\Comment\Repository;

use RuntimeException;
final class CommentRepositoryFactory
{
    public static function create(): CommentRepositoryInterface
    {

        return match (STORAGE_TYPE) {
            'file'   => new FileCommentRepository(COMMENT_FILE),
            'mysqli' => new DbCommentRepository(db()),
            default  => throw new RuntimeException('잘못된 STORAGE_TYPE: ' . STORAGE_TYPE),
        };
    }
}

