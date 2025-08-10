<?php
declare(strict_types=1);

namespace App\Board\Repository;

use mysqli;
use RuntimeException;

class DbBoardRepository implements BoardRepository
{
    public function __construct(private mysqli $conn) {}

    /* -------------------- Board -------------------- */

    public function createBoard(string $title, string $content, string $memberId): int
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO board (member_id, title, contents) VALUES (?,?,?)'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('sss', $memberId, $title, $content);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function getBoards(): array
    {
        $sql = 'SELECT id, member_id, title FROM board ORDER BY id DESC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $out = [];
        $no  = 1;
        while ($row = $res->fetch_assoc()) {
            $id        = (int)$row['id'];
            $title     = htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $member_id = htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $out[] = [
                'no'        => $no++,
                // ✅ 호환성: 두 키를 모두 제공
                'id'        => $id,
                'board_id'  => $id,

                'title'     => $title,
                'member_id' => $member_id,
            ];
        }
        if (empty($out)) {
            error_log('[getBoards] empty result');
        }
        return $out;
    }


    public function getBoardById(int $boardId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, member_id, title, contents FROM board WHERE id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('i', $boardId);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return [
            'board_id'  => (int)$row['id'],
            'title'     => $row['title'],
            'member_id' => $row['member_id'],
            'contents'  => $row['contents'],
        ];
    }

    public function updateBoard(int $boardId, string $title, string $content): bool
    {
        $stmt = $this->conn->prepare(
            'UPDATE board SET title = ?, contents = ? WHERE id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('ssi', $title, $content, $boardId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function pagination(
        int    $page,
        int    $perPage,
        string $order,
        ?int   $menuId = null,
        string $keyword = '',
        string $target = 'title',
    ): array {
        $queryParts  = $this->buildSearchQueryParts($keyword, $target, $menuId);
        $totalCount  = $this->getTotalCount($queryParts);

        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min($page, $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $rows   = $this->fetchPageData($queryParts, $order, $perPage, $offset);
        $boards = $this->formatBoardRows($rows, $offset);

        return [
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $currentPage,
            'boards'      => $boards,
        ];
    }

    /**
     * 동적 검색 쿼리 구성.
     * - keyword가 비어 있으면 어떤 경우에도 댓글 JOIN 금지
     * - joinedComments 플래그로 이후 로직 제어
     */
    private function buildSearchQueryParts(string $keyword, string $target, ?int $menuId): array
    {
        $from   = 'FROM board b';
        $where  = [];
        $types  = '';
        $params = [];
        $joined = false;

        $kw = trim($keyword);

        if ($kw === '') {
            // ✅ 키워드 없으면 전체 글: JOIN/WHERE 없음
            return [
                'from'            => $from,
                'whereSql'        => '',
                'types'           => '',
                'params'          => [],
                'joinedComments'  => false,
            ];
        }

        // ✅ 키워드 있을 때만 분기
        if ($target === 'comment') {
            $from   .= ' JOIN comments c ON b.id = c.board_id';
            $where[] = 'c.comment LIKE ?';
            $types  .= 's';
            $params[] = "%{$kw}%";
            $joined = true;
        } elseif ($target === 'member') {
            $where[] = 'b.member_id LIKE ?';
            $types  .= 's';
            $params[] = "%{$kw}%";
        } else { // title or contents
            $where[] = '(b.title LIKE ? OR b.contents LIKE ?)';
            $types  .= 'ss';
            $params[] = "%{$kw}%";
            $params[] = "%{$kw}%";
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        return [
            'from'            => $from,
            'whereSql'        => $whereSql,
            'types'           => $types,
            'params'          => $params,
            'joinedComments'  => $joined,
        ];
    }

    private function getTotalCount(array $qp): int
    {
        $sql = "SELECT COUNT(DISTINCT b.id) AS cnt {$qp['from']}{$qp['whereSql']}";
        $stmt = $this->conn->prepare($sql) ?: throw new RuntimeException($this->conn->error);

        if ($qp['types'] !== '') {
            $stmt->bind_param($qp['types'], ...$qp['params']);
        }
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        return $cnt;
    }

    private function fetchPageData(array $qp, string $order, int $limit, int $offset): array
    {
        $orderDirection = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        // ✅ 문자열 비교 제거, 플래그만 사용
        $groupBy = $qp['joinedComments'] ? ' GROUP BY b.id' : '';

        $sql = "SELECT DISTINCT b.id AS board_id, b.member_id, b.title, b.contents
                {$qp['from']}
                {$qp['whereSql']}
                {$groupBy}
                ORDER BY b.id {$orderDirection}
                LIMIT ? OFFSET ?";

        $types  = $qp['types'] . 'ii';
        $params = [...$qp['params'], $limit, $offset];

        $stmt = $this->conn->prepare($sql) ?: throw new RuntimeException($this->conn->error);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function formatBoardRows(array $rows, int $offset): array
    {
        $boards = [];
        foreach ($rows as $idx => $row) {
            $id     = (int)$row['board_id'];
            $title  = htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $member = htmlspecialchars($row['member_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $boards[] = [
                'no'        => $offset + $idx + 1,
                'id'        => $id,       // 호환 키
                'board_id'  => $id,       // 호환 키
                'title'     => $title,
                'member_id' => $member,
                'contents'  => $row['contents'] ?? null,
            ];
        }
        return $boards;
    }


    public function countBoard(): int
    {
        $res = $this->conn->query('SELECT COUNT(*) AS cnt FROM board');
        if (!$res) {
            throw new RuntimeException($this->conn->error);
        }
        $row = $res->fetch_assoc();
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }

    public function orderByDesc(): array
    {
        $sql = 'SELECT id, member_id, title FROM board ORDER BY id DESC';
        $res = $this->conn->query($sql) ?: throw new RuntimeException($this->conn->error);

        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'board_id'  => (int)$row['id'],
                'title'     => htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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
                'board_id'  => (int)$row['id'],
                'title'     => htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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

    /* -------------------- Likes -------------------- */

    public function addLike(string $memberId, int $boardId): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO board_likes (member_id, board_id) VALUES (?, ?)'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('si', $memberId, $boardId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function removeLike(string $memberId, int $boardId): bool
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM board_likes WHERE member_id = ? AND board_id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('si', $memberId, $boardId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function isLikedByUser(string $memberId, int $boardId): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT 1 FROM board_likes WHERE member_id = ? AND board_id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('si', $memberId, $boardId);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function getLikeCount(int $boardId): int
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS cnt FROM board_likes WHERE board_id = ?'
        ) ?: throw new RuntimeException($this->conn->error);

        $stmt->bind_param('i', $boardId);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new RuntimeException($err);
        }
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        return $cnt;
    }
}
