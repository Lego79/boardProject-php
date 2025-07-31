<?php
namespace App\Board\Repository;

use mysqli;
use RuntimeException;

class DbBoardRepository implements BoardRepository
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

    public function pagination(int $page, int $perPage): array
    {
        //전체 글 수 카운트 하기
        $totalCount = $this->countBoard();
        //전체 페이지 수 계산 - ceil은 소수점 올림, 페이지 수 정수화
        $totalPages = (int) ceil($totalCount / $perPage);
        //현재 페이지 번호 조정
        // 1 <= $page <= $totalPages
        $page = max(1, min($page, $totalPages));
        // 조회 시작 위치 계산
        $offset = ($page -1) * $perPage;

        //쿼리문
        $sql = <<<SQL
            SELECT id as board_id,
                   member_id,
                   title,
                   contents
            FROM board
            ORDER BY id DESC
            LIMIT ? OFFSET ?
        SQL;

        $stmt = $this -> conn -> prepare($sql)
            ?: throw new RuntimeException($this -> conn -> error);

        //바인드 파람 사용시 - 변수 타입 정의 ii -> int int
        $stmt -> bind_param('ii', $perPage, $offset);

        // prepare(): SQL 문을 DB에 미리 컴파일 요청


       // 실패시 익셉션 던지고 종료하기
        if (!$stmt->execute()) {
            // 실패 시 예외 던지고 종료
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }

        $result = $stmt->get_result();
        $rows = $result -> fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $startNo = $offset + 1;
        $boards  = array_map(function(array $row) use (&$startNo) {
            return [
                'no'        => $startNo++,
                'board_id'  => (string)$row['board_id'],
                'title'     => htmlspecialchars($row['title'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'member_id' => htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'contents'  => $row['contents'],  // 후처리 필요 시 여기에 추가
            ];
        }, $rows);

        $pagedBoards = [
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'boards'      => $boards,
        ];

        return $pagedBoards;


    }




    public function countBoard(): int
    {
        $result = $this->conn->query('SELECT COUNT(*) AS cnt FROM board');
        if (!$result) {
            throw new \RuntimeException($this->conn->error);
        }

        $row = $result->fetch_assoc();
        $count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

        error_log('Counting boards: ' . $count);

        return $count;
    }








}
