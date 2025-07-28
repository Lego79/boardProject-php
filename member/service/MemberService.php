<?php
// src/Member/Service/BoardService.php
namespace App\Member\Service;
use App\Member\Repository\MemberRepositoryInterface;

class MemberService
{
    public function __construct(private MemberRepositoryInterface $repo) {}
    
    public function handlePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signIn'])) {
            $this->signIn();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $this->login();
        }
    }

    public function signIn(): bool|string
    {

        $id   = trim($_POST['id'] ?? '');
        $nick = trim($_POST['nickname'] ?? '');
        $pw   = $_POST['password'] ?? '';

        if (empty($id) || empty($nick) || empty($pw)) {
            return '모든 필드를 입력해주세요.';
        }

        if ($this->repo->isDuplicated($id, $nick)) {
            return false;
        }

        $this->repo->save($id, $nick, $pw);
        header('Location: login.php');    
        return true;
    }

    public function login(): bool|string
    {

        $id = trim($_POST['id'] ?? '');
        $pw = $_POST['password'] ?? '';

        if (empty($id) || empty($pw)) {
            return '아이디와 비밀번호를 모두 입력해주세요.';
        }

        if ($this->repo->isUserExisted($id, $pw)) {
            $_SESSION['id'] = $id;

            if (isset($_POST['remember'])) {
                setcookie('remember', $id, [
                    'expires'  => time() + 60 * 60 * 24 * 30,
                    'path'     => '/',
                    'secure'   => false, 
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            header('Location: /boardProject/board/view/board.php');
            return true;
        }

        return '아이디 또는 비밀번호를 확인하세요.';
    }

}
