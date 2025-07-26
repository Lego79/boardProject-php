<?php
/* Service 하위 모든 PHP 파일 자동 로드 */
foreach (glob(__DIR__.'/*.php') as $file) {
    if (basename($file) !== 'all.php') {
        require_once $file;
    }
}
