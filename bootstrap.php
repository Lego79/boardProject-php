<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', '1');          // 개발 단계만 ON

require_once __DIR__.'/config.php';
require_once __DIR__.'/support/JsonHelper.php';
require_once __DIR__.'/db.php';
require_once __DIR__ . '/vendor/autoload.php';
/* 레이어별 파일 한꺼번에 로드 */
require_once __DIR__.'/member/repository/all.php';
require_once __DIR__.'/member/service/all.php';
require_once __DIR__.'/board/repository/all.php';
require_once __DIR__.'/board/service/all.php';
require_once __DIR__.'/comment/repository/all.php';
require_once __DIR__.'/comment/service/all.php';
