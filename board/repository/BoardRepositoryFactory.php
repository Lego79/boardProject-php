<?php

namespace App\Board\Repository;

class BoardRepositoryFactory
{
    public static function create(string $key = ''): BoardRepository
     {

        return match (STORAGE_TYPE) {          
            'file'   => new FileBoardRepository(BOARD_FILE),
            'mysqli' => new DbBoardRepository(db()),
        };
     }
}


