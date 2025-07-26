<?php
namespace App\Member\Repository;

use mysqli;
use RuntimeException;

class DbMemberRepository implements MemberRepositoryInterface
{
    public function __construct(private mysqli $conn) {}

    public function save(string $id, string $nickname, string $password): void
    {
        if ($this->isDuplicated($id, $nickname)) {
            return;
        }


        $stmt = $this->conn->prepare(
            'INSERT INTO members (id, nickname, password) VALUES (?,?,?)'
        );
        if (!$stmt) {
            throw new RuntimeException('prepare error: ' . $this->conn->error);
        }
        $stmt->bind_param('sss', $id, $nickname, $password);
        if (!$stmt->execute()) {
            throw new RuntimeException('execute error: ' . $stmt->error);
        }
    }

    public function isDuplicated(string $id, string $nickname): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT 1 FROM members WHERE id = ? OR nickname = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new RuntimeException('prepare error: ' . $this->conn->error);
        }
        $stmt->bind_param('ss', $id, $nickname);
        $stmt->execute();
        return (bool) $stmt->get_result()->fetch_row();
    }

    public function isUserExisted(string $id, string $password): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT password FROM members WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new RuntimeException('prepare error: ' . $this->conn->error);
        }
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if($password === $row['password']) {
            return true;
        } else {
            return false;
        }

    }

    public function login(string $id, string $password): void
    {

    }
}
