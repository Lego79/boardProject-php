<?php
/* 1) 인터페이스를 맨 먼저 로드 */
require_once __DIR__ . '/MemberRepositoryInterface.php';

/* 2) 나머지 구현 클래스들을 순회 로드 */
foreach (glob(__DIR__ . '/*.php') as $file) {
    $base = basename($file);
    if ($base === 'all.php' || $base === 'MemberRepositoryInterface.php') {
        continue;                       // 이미 로드했으므로 건너뜀
    }
    require_once $file;
}
