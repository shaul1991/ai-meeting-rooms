# CLAUDE.md

회의실 예약 시스템 프로젝트 가이드

## Project Overview

PHP 8.4, Laravel 12 기반 회의실 예약 서비스. DDD(Domain-Driven Design) 패턴과 Aggregator Service 패턴 적용.

## Commands

```bash
# 개발 환경 Docker 컨테이너 시작
docker compose up -d

# 마이그레이션
php artisan migrate

# 테스트 실행 (PostgreSQL 필요)
php artisan test

# 단일 테스트 파일 실행
php artisan test tests/Unit/Domain/Room/RoomTest.php

# 코드 스타일 정리
./vendor/bin/pint

# 개발 서버 실행
php artisan serve
```

## Architecture

### DDD Layered Architecture

```
app/
├── Domain/                          # 도메인 레이어 (비즈니스 로직)
│   ├── Room/
│   │   ├── Entities/               # Room, RoomGroup
│   │   ├── ValueObjects/           # RoomId, OperatingHours, Money
│   │   ├── Services/               # 도메인 서비스
│   │   └── Events/                 # RoomCreated, RoomUpdated
│   ├── Reservation/
│   │   ├── Entities/               # Reservation
│   │   ├── ValueObjects/           # ReservationId, TimeSlot, ReservationStatus
│   │   ├── Services/               # SlotAvailabilityService, ReservationService
│   │   └── Events/                 # ReservationCreated, ReservationCancelled
│   └── Notification/
├── Application/                     # 애플리케이션 레이어
│   ├── Aggregators/                # ReservationAggregator, RoomAggregator
│   ├── Commands/
│   └── Queries/
└── Infrastructure/                  # 인프라 레이어
    ├── Persistence/
    │   ├── Eloquent/
    │   │   ├── Models/             # RoomModel, ReservationModel
    │   │   └── Repositories/       # RoomRepository, ReservationRepository
    │   └── Mappers/                # Entity <-> Model 변환
    ├── Events/
    └── Providers/
```

### Key Design Patterns

- **Composite Pattern**: RoomGroup (방 그룹 계층 구조)
- **Aggregator Service Pattern**: Aggregator가 Domain Service들을 조율
- **Repository Pattern**: 직접 주입 (Interface 없음)

## Database 규칙 (PostgreSQL)

### 약한 결합 (Soft Reference) 정책

Foreign Key 제약조건을 사용하지 않고 약한 결합 방식 사용.

```php
// ✅ 약한 결합 방식
$table->uuid('user_id')->comment('users 테이블의 id 참조');
$table->uuid('room_id')->comment('rooms 테이블의 id 참조');
```

### SoftDeletes 필수

모든 주요 테이블에 `SoftDeletes` 적용.

## 비즈니스 규칙

### 예약 규칙
- 30분 단위 예약
- 일반 사용자: 최대 2시간, 동시 1개 예약만 가능
- 관리자: 시간 제한 없음
- 취소 요청: 예약일 2일 전까지

### 상태 흐름
```
CONFIRMED → CANCEL_REQUESTED → CANCELLED
          ↘ CANCELLED
          → COMPLETED
          → NO_SHOW
```

## Testing

### 테스트 환경
- Local: Docker Compose (`postgres-test` 컨테이너, 포트 5433)
- CI: GitHub Actions 서비스 컨테이너

```bash
# Docker 컨테이너 시작 후 테스트
docker compose up -d postgres-test
php artisan test
```

### 테스트 구조
- `tests/Unit/Domain/`: 도메인 로직 단위 테스트
- `tests/Feature/`: 통합 테스트
- `tests/Architecture/`: 아키텍처 규칙 테스트
