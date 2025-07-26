<?php
declare(strict_types=1);

namespace App\Board\Repository;
use App\Support\JsonHelper;

class FileBoardRepository implements BoardRepositoryInterface
{
    public function __construct(private string $boardDir) {}

    public function createBoard(string $title, string $contents, string $author): string
    {
        $board = JsonHelper::read($this->boardDir, ['board' => []]);
        $board['board'][] = [
            'id'        => uniqid('', true),
            'title'     => $title,
            'contents'  => $contents,
            'member_id' => $author,
        ];

        JsonHelper::write($this->boardDir, $board);
        $message = '게시글이 작성되었습니다.';
    

        return $message;
        
    }

    public function getBoards(): array
    {
        $boards = JsonHelper::read($this->boardDir, ['board' => []]);

        $out = [];
        $no  = 1;

        foreach ($boards['board'] as $board) {
            $title     = htmlspecialchars($board['title']);
            $memberId  = htmlspecialchars($board['member_id']);

            $out[] = [
                'no'     => $no++,
                'title'  => $title,
                'member_id' => $memberId,
                'board_id'    => (string)($board['id'] ?? ''),
            ];
        }

        return $out;
    }



    public function getBoardById(int|string $boardId): ?array
    {
        $boards = JsonHelper::read($this->boardDir, ['board' => []]);

        
        foreach ($boards['board'] as $board) {
            if ($board['id'] === $boardId) {

                return [
                    'boardId'      => $board['id'],
                    'title'   => htmlspecialchars($board['title']),
                    'contents' => nl2br(htmlspecialchars($board['contents'])),
                    'member_id'  => htmlspecialchars($board['member_id']),
                ];
            }
        }

        return null;

    }



    public function updateBoard(int|string $boardId, string $title, string $contents): bool
    {
        $boards = JsonHelper::read($this->boardDir, ['board' => []]);

        $boardId  = (string)$boardId;
        $updated  = false;

        foreach ($boards['board'] as &$board) {
            if ((string)($board['id'] ?? '') === $boardId) {
                $board['title']    = $title;
                $board['contents'] = $contents;
                $updated = true;
                break;
            }
        }
        unset($board); 

        if ($updated) {
            JsonHelper::write($this->boardDir, $boards); // truncate=false(또는 기본값) 권장
        }

        return $updated;
    }


}
