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

    public function pagination(int $page, int $perPage, string $order): array
    {
        // 현재 클래스의 countBoard 메서드를 사용하여 전체 게시글 수를 가져옵니다.
        $totalCount = $this->countBoard();

        // totalCount/ perPage로 전체 페이지 수를 계산합니다.
        // ceil을 사용하여 올림 처리합니다. 1.0 2.0인 값을 int로 형변환을 합니다
        $totalPages = (int) ceil($totalCount / $perPage);

        // 전체 페이지가 1보다 작으면 1을 반환합니다
        // 반환한 totalPages의 값과 $page의 값을 비교하여 현재 페이지를 반환합니다
        // 페이지 번호가 1보다 작거나 전체 페이지 수보다 크면 1로 설정합니다.
        $page = max(1, min($page, max(1, $totalPages))); // 게시글 0건일 때 보호

        // 페이지 번호에 따라 오프셋을 계산합니다.
        // 페이지 번호가 1이면 오프셋은 0, 페이지 번호가 2이면 오프셋은 perPage의 값이 됩니다.
        // 페이지 번호가 3이면 오프셋은 perPage * 2의 값이 됩니다.
        // 오프셋은 현재 페이지 번호에서 1을 빼고 perPage를 곱합니다.
        // 예를 들어, 페이지 번호가 2이고 perPage가 14이면
        // 오프셋은 (2 - 1) * 14 = 14
        // 예를 들어, 페이지 번호가 3이고 perPage가 14이면
        // 오프셋은 (3 - 1) * 14 = 28
        $offset = ($page - 1) * $perPage;

        //$order의 값이 'asc' 이냐? 맞느면 asc를 반환하고 아니면 desc를 반환 합니다
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // 쿼리의 id desc, asc 있던 부분을 $order로 변경하여, 입력된 변수 값으로 처리합니다
        $sql = <<<SQL
            SELECT id as board_id, member_id, title, contents
            FROM board
            ORDER BY id {$order}
            LIMIT ? OFFSET ?
        SQL;

        // 쿼리 실행을 위해 prepare합니다.
        $stmt = $this->conn->prepare($sql) ?: throw new RuntimeException($this->conn->error);
        // 쿼리의 LIMIT와 OFFSET에 사용할 파라미터를 바인딩합니다.
        $stmt->bind_param('ii', $perPage, $offset);

        // 쿼리를 실행합니다.
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        // 쿼리 결과를 가져옵니다.
        $result = $stmt->get_result();
        //
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        //boards를 담을 배열을 생성
        $boards = [];

        //$rows = 게시글 offset, limit에 해당하는 게시글을 담고 있습니다.
        //as $rows as $idx => $row의 의미는 
        // $rows 배열의 각 요소를 순회하면서 인덱스와 값을 가져오는 것입니다.
        // $idx는 현재 요소의 인덱스(0부터 시작)이고,
        // $row는 현재 요소의 값입니다.
        // 이 반복문을 통해 각 게시글의 정보를 boards 배열에 추가합니다.
        foreach ($rows as $idx => $row) {
            $boards[] = [
                // 이렇게 했을 때 asc를 하든지, desc를 하든지 번혹 1번부터 매겨지게 됩니다.
                'no'        => $idx + 1,
                'board_id'  => (string)$row['board_id'],
                'title'     => htmlspecialchars(
                                $row['title'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ),
                'member_id' => htmlspecialchars(
                                $row['member_id'],
                                ENT_QUOTES | ENT_SUBSTITUTE,
                                'UTF-8'
                            ),
                'contents'  => $row['contents'],
            ];
        }

        return [
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'boards'      => $boards,
            // (필요시 'order'도 함께 반환)
        ];
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

    public function orderByDesc(): array
    {
        $sql = 'SELECT id, member_id, title FROM board ORDER BY id DESC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'board_id' => (string)$row['id'],
                'title'    => htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'member_id' => htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ];
        }
        return $out;
    }

    public function orderByAsc(): array
    {
        $sql = 'SELECT id, member_id, title FROM board ORDER BY id ASC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'board_id' => (string)$row['id'],
                'title'    => htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'member_id' => htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ];
        }
        return $out;
    }








}
