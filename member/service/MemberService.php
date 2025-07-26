<?php
// src/Member/Service/BoardService.php
namespace App\Member\Service;



use App\Member\Repository\MemberRepositoryInterface;

class MemberService
{
    public function __construct(private MemberRepositoryInterface $repo) {}

    public function register(string $id, string $nick, string $pw): bool
    {
        if ($this->repo->isDuplicated($id, $nick)) {
            return false;                  
        }
        $this->repo->save($id, $nick, $pw);
        return true;
    }
    public function login(string $id, string $pw): bool
    {
        if ($this->repo->isUserExisted($id, $pw)) {
            return true;
        }

        return false;
    }

}
