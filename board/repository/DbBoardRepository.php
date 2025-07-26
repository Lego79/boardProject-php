<?php
namespace App\Board\Repository;

use mysqli;
use RuntimeException;

class DbBoardRepository implements BoardRepositoryInterface
{
    public function __construct(private mysqli $conn) {}

    public function createBoard(string $title, string $content, string $author): string
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO board (member_id, title, contents) VALUES (?,?,?)'
        ) ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param('sss', $author, $title, $content);
        $stmt->execute();
        return (string) $stmt->insert_id;  
    }

    public function getBoards(): array
    {
        $sql = 'SELECT id, member_id, title 
                  FROM board 
              ORDER BY id DESC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $out = [];
        $no  = 1;
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'no'     => $no++,
                'title'  => htmlspecialchars($row['title'],  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'member_id' => htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'board_id'    => (string)$row['id'],
            ];
        }
        return $out;
    }


    public function getBoardById(int|string $key): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, member_id, title, contents FROM board WHERE id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        /* ① 실제 파라미터 사용 */
        $stmt->bind_param('i', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return null;

        /* ② 파일 모드와 키 통일 */
        return [
            'boardId'      => (string)$row['id'],
            'title'   => $row['title'],
            'member_id'  => $row['member_id'],
            'contents' => $row['contents'],   // ← 키 이름 맞춤
        ];
    }


    public function updateBoard(int|string $id, string $title, string $content): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE board SET title = ?, contents = ? WHERE id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('ssi', $title, $content, $id);
        return $stmt->execute();
    }


}
