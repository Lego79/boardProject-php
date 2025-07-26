<?php
namespace App\Member\Repository;
use App\Support\JsonHelper;



class FileMemberRepository implements MemberRepositoryInterface
{
    public function __construct(private string $memberDir) {}
    public function save(string $id, string $nickname, string $password): void
    {
        error_log('save() start: id='.$id.', nick='.$nickname);
        $data = JsonHelper::read($this->memberDir, ['members' => []]);
        $before = count($data['members']);

        $data['members'][] = [
            'id'        => $id,
            'nickname'  => $nickname,
            'password'  => $password,
        ];
        JsonHelper::write($this->memberDir, $data);

        // error_log('save() done: members '. $before.' -> '.count($data['members']));
    }


    public function isDuplicated(string $id, string $nickname): bool
    {   
        $members = JsonHelper::read($this->memberDir, ['members' => []]);
        foreach ($members['members'] as $member) {
            if (($member['id'] ?? null) === $id || ($member['nickname'] ?? null) === $nickname) {
                return true;
            }
        }
        return false;
    }   

    public function isUserExisted(string $id, string $password): bool
    {
        $members = JsonHelper::read($this->memberDir, ['members' => []]);
        foreach ($members['members'] as $member) {
            if (($member['id'] ?? null) === $id || ($member['nickname'] ?? null) === $password) {
                return true;
            }
        }
        return false;  
    
    }

    public function login(string $id,  string $password): void
    {

    }

}
