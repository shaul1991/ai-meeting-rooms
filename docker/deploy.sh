#!/bin/bash
# ==========================================
# Meeting Room 배포 스크립트
# 사용법: sudo bash deploy.sh
# ==========================================

set -e

APP_NAME="meeting-room"
SOURCE_PATH="/home/vibe/workspace/meeting-room"
DEPLOY_PATH="/var/www/meeting-room"
STORAGE_PATH="/var/www/meeting-room-storage"

echo "=== Meeting Room 배포 시작 ==="

# 1. 프론트엔드 빌드
echo "[1/8] 프론트엔드 빌드 중..."
cd "$SOURCE_PATH"
npm ci
npm run build

# 2. 배포 디렉토리 생성 및 복사
echo "[2/8] 프로젝트 복사 중..."
if [ -d "$DEPLOY_PATH" ]; then
    echo "기존 디렉토리 업데이트"
    rsync -av --exclude='storage' --exclude='.git' --exclude='node_modules' "$SOURCE_PATH/" "$DEPLOY_PATH/"
else
    echo "새 디렉토리 생성"
    mkdir -p "$DEPLOY_PATH"
    rsync -av --exclude='storage' --exclude='.git' --exclude='node_modules' "$SOURCE_PATH/" "$DEPLOY_PATH/"
fi

# 3. Storage 디렉토리 설정
echo "[3/8] Storage 디렉토리 설정 중..."
if [ ! -d "$STORAGE_PATH" ]; then
    mkdir -p "$STORAGE_PATH"/{app/public,framework/{cache/data,sessions,views},logs}
fi
chown -R 82:82 "$STORAGE_PATH"
chmod -R 775 "$STORAGE_PATH"

# 4. Caddyfile에 meeting-room 추가
echo "[4/8] Caddy 설정 추가 중..."
if ! grep -q "meeting-room.shaul.link" /etc/caddy/Caddyfile; then
    cat >> /etc/caddy/Caddyfile << 'EOF'

# Meeting Room - 회의실 예약 서비스
meeting-room.shaul.link {
    import remove_headers
    reverse_proxy localhost:10200 {
        header_down -Server
        header_down -Via
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto {scheme}
    }
}
EOF
    echo "Caddy 설정 추가 완료"
else
    echo "Caddy 설정 이미 존재"
fi

# 5. Caddy 검증 및 리로드
echo "[5/8] Caddy 리로드 중..."
caddy validate --config /etc/caddy/Caddyfile
systemctl reload caddy
echo "Caddy 리로드 완료"

# 6. Docker 컨테이너 재시작
echo "[6/8] Docker 컨테이너 재시작 중..."
cd "$DEPLOY_PATH/docker"
docker compose -f docker-compose.deploy.yml --profile prod up -d
docker restart meeting-room-prod

# 컨테이너 준비 대기
echo "컨테이너 준비 대기 중..."
sleep 5

# 7. 캐시 클리어
echo "[7/8] 캐시 클리어 중..."
docker exec meeting-room-prod php artisan cache:clear
docker exec meeting-room-prod php artisan config:clear
docker exec meeting-room-prod php artisan route:clear
docker exec meeting-room-prod php artisan view:clear
docker exec meeting-room-prod php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; }"

# 8. 캐시 재생성
echo "[8/8] 캐시 최적화 중..."
docker exec meeting-room-prod php artisan config:cache
docker exec meeting-room-prod php artisan route:cache
docker exec meeting-room-prod php artisan view:cache
docker exec meeting-room-prod php artisan event:cache

echo ""
echo "=== 배포 완료 ==="
echo "URL: https://meeting-room.shaul.link"
echo ""
echo "마이그레이션 실행:"
echo "  docker exec meeting-room-prod php artisan migrate"
