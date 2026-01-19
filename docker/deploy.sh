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

# 1. 배포 디렉토리 생성 및 복사
echo "[1/5] 프로젝트 복사 중..."
if [ -d "$DEPLOY_PATH" ]; then
    echo "기존 디렉토리 업데이트"
    rsync -av --exclude='storage' --exclude='.git' --exclude='node_modules' "$SOURCE_PATH/" "$DEPLOY_PATH/"
else
    echo "새 디렉토리 생성"
    mkdir -p "$DEPLOY_PATH"
    rsync -av --exclude='storage' --exclude='.git' "$SOURCE_PATH/" "$DEPLOY_PATH/"
fi

# 2. Storage 디렉토리 설정
echo "[2/5] Storage 디렉토리 설정 중..."
if [ ! -d "$STORAGE_PATH" ]; then
    mkdir -p "$STORAGE_PATH"/{app/public,framework/{cache/data,sessions,views},logs}
fi
chown -R 82:82 "$STORAGE_PATH"
chmod -R 775 "$STORAGE_PATH"

# 3. Caddyfile에 meeting-room 추가
echo "[3/5] Caddy 설정 추가 중..."
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

# 4. Caddy 검증 및 리로드
echo "[4/5] Caddy 리로드 중..."
caddy validate --config /etc/caddy/Caddyfile
systemctl reload caddy
echo "Caddy 리로드 완료"

# 5. Docker 컨테이너 시작
echo "[5/5] Docker 컨테이너 시작 중..."
cd "$DEPLOY_PATH/docker"
docker compose -f docker-compose.deploy.yml --profile prod up -d

echo ""
echo "=== 배포 완료 ==="
echo "URL: https://meeting-room.shaul.link"
echo ""
echo "마이그레이션 실행:"
echo "  docker exec meeting-room-prod php artisan migrate"
