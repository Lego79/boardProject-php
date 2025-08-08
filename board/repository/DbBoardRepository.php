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

    public function pagination(
        int    $page,
        int    $perPage,
        string $order,
        ?int   $menuId = null,
        string $keyword = '',
        string $target = 'title',
    ): array {
        // 1. 검색 조건(SQL, 타입, 파라미터) 생성
        $queryParts = $this->buildSearchQueryParts($keyword, $target, $menuId);

        // 2. 검색 조건에 맞는 총 게시물 수 조회
        $totalCount = $this->getTotalCount($queryParts);

        // 3. 페이지네이션 관련 값 계산
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        // 4. 현재 페이지에 해당하는 데이터 목록 조회
        $rows = $this->fetchPageData($queryParts, $order, $perPage, $offset);
        
        // 5. 조회된 데이터를 프론트엔드 형식에 맞게 가공
        $boards = $this->formatBoardRows($rows);

        // 6. 최종 결과 반환
        return [
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $currentPage,
            'boards'      => $boards,
        ];
    }

    /**
     * 동적 검색 쿼리(FROM, WHERE)와 파라미터를 생성합니다.
     */
    private function buildSearchQueryParts(string $keyword, string $target, ?int $menuId): array
    {   
        //기본 from 절, board 조회
        $from = 'FROM board b';
        // 'comment'가 검색 조건인 경우
        if ($target === 'comment') {
            //$from = 'FROM board b JOIN comments c ON b.id = c.board_id'; 로 조인절 연결해서 쿼리문 생성
            $from .= ' JOIN comments c ON b.id = c.board_id';
        }
        
        //where절 파라미터 초기화
        $where = [];
        //쿼리 바인딩을 위한 타입
        $types = '';

        $params = [];

        if ($keyword !== '') {
            // if keyowrd = 'test' -> $likeKeyword = '%test%'
            $likeKeyword = '%' . $keyword . '%';
            switch ($target) {
                case 'member':
                    $where[] = 'b.member_id LIKE ?';
                    // 쿼리 바인딩에 타입이 추가로 필요한 경우 .= 을 통해서 파라미터 추가 가능
                    $types .= 's';
                    $params[] = $likeKeyword;
                    break;
                case 'comment':
                    // 검색 결과가 중복될 수 있으므로 DISTINCT 추가 고려
                    $where[] = 'c.comment LIKE ?';
                    $types .= 's';
                    $params[] = $likeKeyword;
                    break;
                default: // 'title' or 'contents'
                    $where[] = '(b.title LIKE ? OR b.contents LIKE ?)';
                    $types .= 'ss';
                    $params[] = $likeKeyword;
                    $params[] = $likeKeyword;
                    break;
            }
        }

        //ex) $whereSql = ' WHERE b.member_id LIKE ? AND b.title LIKE ?'
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        return ['from' => $from, 'whereSql' => $whereSql, 'types' => $types, 'params' => $params];
    }

    /**
     * 주어진 조건으로 총 데이터 개수를 조회합니다.
     */
    private function getTotalCount(array $queryParts): int
    {

        // 구성된 $queryParts를 사용하여 COUNT 쿼리 생성
        // ex) SELECT COUNT(DISTINCT b.id) AS cnt FROM board b WHERE b
        $selectCount = 'SELECT COUNT(DISTINCT b.id) AS cnt';
        
        // ex) $sql = 
        // "SELECT COUNT(DISTINCT b.id) AS cnt 
        //  FROM board b 
        //  WHERE (b.title LIKE '%test%' OR b.contents LIKE '%test%')"
        $sql = "{$selectCount} {$queryParts['from']}{$queryParts['whereSql']}";

        // 생성된 sql 쿼리를 실행 준비
        $stmt = $this->conn->prepare($sql)
            ?: throw new \RuntimeException($this->conn->error);

        if ($queryParts['types'] !== '') {
            //준비된 자료형, 파라미터를 바인딩
            $stmt->bind_param($queryParts['types'], ...$queryParts['params']);
        }

        $stmt->execute();
        $result = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        

        return $result;
    }

    /**
     * 주어진 조건으로 실제 페이지 데이터를 조회합니다.
     */
    private function fetchPageData(array $queryParts, string $order, int $limit, int $offset): array
    {
    // 정렬 순서
    // 1) 정렬 순서 설정
    $orderDirection = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    // → 대문자로 맞춰서 'ASC'인지 확인하고, 맞으면 'ASC' 그대로, 아니면 'DESC'를 사용합니다.

    // 2) 댓글 조인 여부에 따라 그룹화 필요 여부 결정
    $groupBy = ($queryParts['from'] !== 'FROM board b') 
        ? ' GROUP BY b.id' 
        : '';
    // → 만약 FROM 절에 comments 조인이 포함되어 있으면, ' GROUP BY b.id' 문자열을,
    //    그렇지 않으면 빈 문자열을 할당합니다.

    // 3) SQL 문 조합 시작
    $sql = "SELECT DISTINCT b.id AS board_id, b.member_id, b.title, b.contents
            {$queryParts['from']}
            {$queryParts['whereSql']}
            {$groupBy}
            ORDER BY b.id {$orderDirection}
            LIMIT ? OFFSET ?";
    // → 
    //    a) DISTINCT: b.id가 중복되지 않도록 중복 행 제거
    //    b) FROM/WHERE: 이전에 만든 조인·검색 조건 삽입
    //    c) GROUP BY: 댓글 조인 시 한 게시물을 한 번만 조회
    //    d) ORDER BY: 게시물 ID 순으로 정렬
    //    e) LIMIT/OFFSET: 페이징 범위 지정

    // 4) 파라미터 타입·값 준비
    $types  = $queryParts['types'] . 'ii';
    $params = [...$queryParts['params'], $limit, $offset];
    // → 기존 검색 파라미터 뒤에 LIMIT/OFFSET용 'i','i' 타입과 값을 추가합니다.

    // 5) 준비된 쿼리 실행
    $stmt = $this->conn->prepare($sql)
        ?: throw new \RuntimeException($this->conn->error);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
    // → 최종적으로 결과 배열을 반환합니다.

    }
    
    /**
     * DB에서 조회한 raw 데이터를 프론트엔드용으로 가공합니다.
     */
    private function formatBoardRows(array $rows): array
    {
        $boards = [];
        foreach ($rows as $idx => $row) {
            $boards[] = [
                'no'        => $idx + 1, // 이 번호는 페이지 내 순서이므로 offset을 고려해야 할 수도 있습니다.
                'board_id'  => (string)$row['board_id'],
                'title'     => htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'member_id' => htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                'contents'  => $row['contents'],
            ];
        }
        return $boards;
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
    
    public function getMenuFilters(): array
    {
        $sql = 'SELECT id, menu_name FROM menuFilter ORDER BY id ASC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $menus = [];
        while ($row = $res->fetch_assoc()) {
            $menus[] = [
                'id'        => (int)$row['id'],
                'menu_name' => htmlspecialchars($row['menu_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ];
        }
        return $menus;
    }







}
