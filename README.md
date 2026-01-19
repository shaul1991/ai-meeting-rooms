# Meeting Room Reservation System

회의실 예약 시스템 - DDD(Domain-Driven Design) 아키텍처 기반의 Laravel 애플리케이션

## 프로젝트 개요

이 프로젝트는 회사 내 회의실 예약을 관리하기 위한 웹 애플리케이션입니다. 사용자는 날짜를 선택하고 각 회의실의 예약 가능 시간대를 확인한 후 예약할 수 있으며, 관리자는 회의실 관리 및 예약 취소 요청을 처리할 수 있습니다.

## 기술 스택

### Backend
- **PHP** 8.2+
- **Laravel** 12.x
- **Laravel Passport** - OAuth2 인증
- **PHPUnit** 11.x - 테스트

### Frontend
- **Tailwind CSS** 4.x
- **Vite** 7.x
- **Blade** 템플릿 엔진

### Infrastructure
- **PostgreSQL** 16 - 메인 데이터베이스
- **Redis** 7 - 캐시 및 세션
- **Docker** - 컨테이너화
- **Nginx** - 웹 서버 (프로덕션)

## 아키텍처

### DDD (Domain-Driven Design)

프로젝트는 DDD 원칙을 따르며, 다음과 같은 구조로 구성됩니다:

```
app/
├── Application/
│   └── Aggregators/          # 애플리케이션 서비스 (유스케이스 조합)
├── Domain/
│   ├── Room/                 # 회의실 도메인
│   │   ├── Entities/         # Room, RoomGroup
│   │   ├── ValueObjects/     # RoomId, Money, OperatingHours
│   │   ├── Services/         # 도메인 서비스
│   │   └── Events/           # 도메인 이벤트
│   ├── Reservation/          # 예약 도메인
│   │   ├── Entities/         # Reservation
│   │   ├── ValueObjects/     # ReservationId, TimeSlot, UserId, ReservationStatus
│   │   ├── Services/         # ReservationService, SlotAvailabilityService
│   │   └── Events/           # 도메인 이벤트
│   └── Notification/         # 알림 도메인
├── Infrastructure/
│   └── Persistence/          # Eloquent Repository 구현
└── Http/
    └── Controllers/          # 웹/API 컨트롤러
```

### Value Objects

도메인 모델의 불변성과 유효성을 보장하는 Value Objects:

- **RoomId** - 회의실 식별자 (UUID)
- **Money** - 금액 (시간당 요금)
- **OperatingHours** - 운영 시간
- **TimeSlot** - 예약 시간대
- **ReservationStatus** - 예약 상태 (확정, 취소요청, 취소됨)

## 도메인

### Room (회의실)

- 회의실 정보 관리 (이름, 위치, 수용인원, 시간당 요금)
- 회의실 그룹 관리
- 요일별 운영 시간 설정
- 회의실 활성화/비활성화

### Reservation (예약)

- 회의실 예약 생성
- 30분 단위 시간 슬롯
- 예약 가능 시간 확인
- 예약 취소 요청 및 처리
- 사용자당 동시 예약 제한

### Notification (알림)

- 예약 관련 알림 처리

## 주요 기능

### 사용자 기능

1. **날짜 우선 회의실 선택**
   - 날짜를 먼저 선택
   - 선택한 날짜의 모든 회의실 시간대 현황 확인
   - 예약된 시간은 비활성 버튼으로 표시
   - 예약 가능한 시간 선택하여 예약

2. **예약 관리**
   - 내 예약 목록 조회
   - 예약 취소 요청

### 관리자 기능

1. **회의실 관리**
   - 회의실 CRUD
   - 운영 시간 설정
   - 활성화/비활성화

2. **예약 관리**
   - 취소 요청 목록 조회
   - 취소 요청 승인/거부
   - 관리자 직접 취소

## API 라우트

### Public Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/rooms` | 회의실 목록 (날짜별 시간대 현황) |
| GET | `/rooms/{id}` | 회의실 상세 정보 |

### Authenticated Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/reservations` | 내 예약 목록 |
| POST | `/reservations` | 예약 생성 |
| POST | `/reservations/{id}/cancel-request` | 예약 취소 요청 |

### Admin Routes

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/admin/rooms` | 회의실 관리 목록 |
| POST | `/admin/rooms` | 회의실 생성 |
| PUT | `/admin/rooms/{id}` | 회의실 수정 |
| PATCH | `/admin/rooms/{id}/toggle-active` | 회의실 활성화 토글 |
| GET | `/admin/reservations/cancel-requests` | 취소 요청 목록 |
| POST | `/admin/reservations/{id}/approve-cancel` | 취소 승인 |
| POST | `/admin/reservations/{id}/reject-cancel` | 취소 거부 |

## 설치 및 실행

### 요구사항

- PHP 8.2 이상
- Composer
- Node.js 18 이상
- Docker & Docker Compose

### 개발 환경 설정

```bash
# 1. 저장소 클론
git clone <repository-url>
cd meeting-room

# 2. Docker 컨테이너 실행 (PostgreSQL, Redis)
docker compose up -d

# 3. 프로젝트 초기 설정
composer setup

# 4. 개발 서버 실행
composer dev
```

`composer dev` 명령어는 다음을 동시에 실행합니다:
- PHP 개발 서버 (php artisan serve)
- Queue Worker (php artisan queue:listen)
- Log Viewer (php artisan pail)
- Vite Dev Server (npm run dev)

### 테스트 실행

```bash
# 전체 테스트 실행
composer test

# 특정 테스트 파일 실행
php artisan test tests/Feature/RoomControllerTest.php

# 특정 테스트 메서드 실행
php artisan test --filter test_회의실_목록_페이지에_날짜_파라미터_없이_접근하면_오늘_날짜가_기본값이다
```

### 코드 스타일

```bash
# Laravel Pint로 코드 스타일 정리
./vendor/bin/pint
```

## 배포

### Docker 배포

```bash
# 배포 스크립트 실행
sudo bash docker/deploy.sh
```

배포 구성:
- Nginx + PHP-FPM
- Supervisor (프로세스 관리)
- OPcache (성능 최적화)

## 데이터베이스 스키마

### 주요 테이블

- `rooms` - 회의실 정보
- `room_groups` - 회의실 그룹
- `reservations` - 예약 정보
- `users` - 사용자 정보

### 약한 결합 정책

FK 제약조건 대신 약한 결합 방식을 사용합니다:

```php
// FK 제약조건 사용하지 않음
$table->uuid('user_id')->comment('users 테이블의 id 참조');
```

이유:
- 마이크로서비스 전환 시 유연성 확보
- 데이터 마이그레이션 용이

## 라이선스

MIT License
