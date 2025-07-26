<?php

namespace App\Board\Repository;

class BoardRepositoryFactory
{
    public static function create(string $key = ''): BoardRepositoryInterface
     {

        if ($key !== '' && ctype_digit($key)) {
            return new DbBoardRepository(db());
        }

        return match (STORAGE_TYPE) {          
            'file'   => new FileBoardRepository(BOARD_FILE),
            'mysqli' => new DbBoardRepository(db()),
        };
     }
}


