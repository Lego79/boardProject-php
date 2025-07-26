<?php
declare(strict_types=1);

const STORAGE_TYPE = 'mysqli';


function db(): ?mysqli
{
    if (STORAGE_TYPE !== 'mysqli') {
        return null;
    }

    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(
            '127.0.0.1',
            'root',
            '1234',
            'APMProject',
            3306
        );

        if (!$conn) {
            throw new RuntimeException(
                'DB 연결 실패: ' . mysqli_connect_error()
            );
        }
        mysqli_set_charset($conn, 'utf8mb4');   // UTF-8 설정
    }
    return $conn;
}
