<?php
namespace App\Member\Repository;

class MemberRepositoryFactory
{
    public static function create(): MemberRepositoryInterface
    {
        return match (STORAGE_TYPE) {
            'file'   => new FileMemberRepository(MEMBER_FILE),
            'mysqli' => new DbMemberRepository(db()),
            default  => throw new \RuntimeException('잘못된 STORAGE_TYPE: '.STORAGE_TYPE)
        };
    }
}
