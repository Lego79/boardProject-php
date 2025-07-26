boardProject-php
소개
boardProject-php는 PHP를 이용해 게시판과 댓글 시스템을 단계적으로 만들어 가는 학습용 프로젝트입니다. 처음에는 JSON 파일을 이용한 간단한 데이터 저장부터 시작하여, 이후에는 MySQL 데이터베이스, 쿠키, 세션 등 웹 애플리케이션의 핵심 요소들을 차례로 도입해 나가며 로우 레벨부터 점차 발전된 구조를 구현하는 것을 목표로 합니다. 파일 기반 저장소와 DB 기반 저장소가 공존하도록 설계되어 있어 구현 단계에 따라 저장 방식을 유연하게 전환할 수 있습니다. 서비스 계층과 저장소 계층을 분리하여 Repository 패턴을 적용했으며, 사용자 인증과 권한 확인을 위해 세션을 활용합니다.

주요 기능
게시글 CRUD: 게시글 작성, 목록 조회, 단일 게시글 조회, 수정 기능을 제공합니다. DB 기반 저장소에서는 게시글 추가 시 게시글 번호, 작성자, 제목, 내용 등을 파라미터로 바인딩하여 삽입하며 htmlspecialchars로 값을 이스케이프하여 출력합니다
GitHub
.

파일 기반/DB 기반 저장소: 게시글과 댓글 저장소를 파일(JSON)과 MySQL 중 선택할 수 있습니다. 파일 저장소에서는 게시글과 댓글을 JSON 형태로 저장하고 읽어오며, FileBoardRepository에서 게시글 목록을 읽을 때도 각 항목에 대해 HTML 특수문자를 이스케이프합니다
GitHub
. DB 저장소에서는 mysqli를 사용하여 prepared statement로 안전하게 쿼리를 수행합니다
GitHub
.

댓글 기능: 게시글에 대한 댓글 작성, 목록 조회, 수정, 삭제 기능을 제공합니다. DB 저장소의 댓글 저장소는 댓글 목록을 최신순으로 읽어오고, 수정·삭제 시 준비된 쿼리를 사용합니다
GitHub
. 파일 저장소의 경우 JSON 파일에서 특정 게시글의 댓글만 필터링하여 반환합니다
GitHub
.

서비스 계층 분리: BoardService와 CommentService는 HTTP 요청을 처리하고 저장소에 위임합니다. 예를 들어 게시글 작성 시 서비스가 POST 데이터를 받아 저장소의 createBoard()를 호출하고 완료 후 리다이렉트를 수행합니다
GitHub
. 댓글 수정·삭제 시에는 작성자(writer)와 세션의 사용자 ID를 비교하여 권한을 검사합니다
GitHub
.

세션과 사용자 식별: 로그인하지 않은 사용자는 기본적으로 익명으로 처리되며, 세션에 로그인 정보가 존재할 경우 작성자와 비교하여 수정/삭제 권한을 제어합니다
GitHub
.

프로젝트 구조
python
복사
편집
boardProject-php/
├── bootstrap.php           # 공통 초기화 및 오토로더 설정
├── config/
│   └── dbConnection.php    # DB 연결 설정 (MySQL)
├── global/
│   └── constant/
│       └── filePath.php    # 파일 저장 경로 상수 정의
├── board/
│   ├── service/            # 게시판 서비스 계층
│   │   ├── BoardService.php
│   │   └── all.php         # 서비스 모음 로딩 스크립트
│   ├── repository/
│   │   ├── BoardRepositoryInterface.php
│   │   ├── FileBoardRepository.php
│   │   ├── DbBoardRepository.php
│   │   └── BoardRepositoryFactory.php
│   └── view/               # 게시판 화면 (PHP/HTML)
│       └── board.php 등
├── comment/
│   ├── service/
│   │   └── CommentService.php
│   └── repository/
│       ├── CommentRepositoryInterface.php
│       ├── FileCommentRepository.php
│       ├── DbCommentRepository.php
│       └── CommentRepositoryFactory.php
└── member/
    ├── repository/         # 회원 저장소 (파일/DB)
    └── view/               # 로그인/회원가입 화면

디렉터리 구성 설명
bootstrap.php – 프로젝트 전반에서 필요한 설정과 함수들을 불러오는 초기화 스크립트입니다.

config/dbConnection.php – 데이터베이스 연결을 설정하는 곳입니다. MySQL 계정을 수정하여 사용합니다.

global/constant/filePath.php – 게시글과 댓글을 파일로 저장할 경우 사용할 디렉터리 경로를 정의합니다.

board/service – 클라이언트 요청을 처리하고 저장소와 상호작용하는 비즈니스 로직을 담당합니다. 예: 게시글 작성 후 리다이렉트 처리
GitHub
.

board/repository – 게시글 데이터를 저장·조회하는 계층입니다. 파일 기반(FileBoardRepository)과 DB 기반(DbBoardRepository) 구현체가 있으며, 공통 인터페이스를 통해 의존성을 분리했습니다. DB 구현체는 SQL 주입을 방지하기 위해 prepared statement를 사용하고, 결과 값을 HTML 이스케이프하여 안전하게 반환합니다
GitHub
.

comment/service & comment/repository – 댓글에 대한 서비스/저장소 계층입니다. 작성자 확인 후 수정·삭제를 수행하는 로직이 포함되어 있습니다
GitHub
.

member – 사용자 로그인 및 회원 관련 기능이 위치합니다.

설치 및 실행 방법
환경 준비

PHP 8.x 이상과 Composer가 설치되어 있어야 합니다.

DB 기능을 사용하려면 MySQL 서버를 설치하고 데이터베이스와 사용자 계정을 생성합니다.

코드 다운로드

bash
복사
편집
git clone https://github.com/Lego79/boardProject-php.git
cd boardProject-php
의존성 설치 (있는 경우)

일부 클래스 로딩을 위해 Composer를 사용할 수 있습니다.

bash
복사
편집
composer install
환경 설정

config/dbConnection.php 파일에서 MySQL 접속 정보를 설정합니다.

파일 저장소를 사용하려면 global/constant/filePath.php에서 게시글과 댓글을 저장할 디렉터리 경로를 지정하고 해당 디렉터리를 생성합니다.

Apache/Nginx 등 웹 서버의 document root를 프로젝트 루트 또는 public 디렉터리로 설정하고 가상 호스트를 구성합니다.

DB 초기화

DB 저장소를 사용할 경우 다음과 같은 예시 SQL을 참고하여 테이블을 생성합니다.

sql
복사
편집
CREATE TABLE board (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    contents TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    member_id VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
실행

웹 서버를 통해 board/view/board.php에 접속하여 게시판을 확인합니다.

파일 기반 저장소와 DB 기반 저장소 전환은 BoardRepositoryFactory 및 CommentRepositoryFactory에서 구현되어 있으므로, 설정에 맞게 인스턴스를 선택합니다.

개발 로드맵
본 프로젝트는 학습용으로 기초부터 차근차근 확장해 나갑니다. 현재까지는 게시글/댓글 기능, 파일·DB 저장소와 서비스 계층 분리에 초점을 맞추었으며, 앞으로 아래와 같은 기능을 추가할 예정입니다.

회원 관리 강화: 비밀번호 해시, 세션 기반 로그인 유지, 권한 관리 등 추가.

쿠키 이용 기능: 최근 본 게시글, 다크 모드 등의 사용자 설정 저장.

검색 및 페이지네이션: 게시글/댓글 목록에 검색 필터와 페이지 나누기 도입.

파일 업로드: 게시글에 이미지나 첨부파일 업로드 기능 추가.

REST API: 프론트엔드와 분리된 API 설계 및 JSON 응답 제공.

기능을 구현하는 과정에서 객체지향 설계 원칙과 보안 고려(입력 검증, CSRF 방지 등)를 익힐 수 있습니다.
