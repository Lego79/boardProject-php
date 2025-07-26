<?php
namespace App\Member\Repository;

interface MemberRepositoryInterface
{
    public function isDuplicated(string $id, string $nickname): bool;
    public function save(string $id, string $nickname, string $password): void;

    public function login(string $id, string $password): void;
    public function isUserExisted(string $id, string $password): bool;
}
