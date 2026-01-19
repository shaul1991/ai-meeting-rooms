#!/bin/bash
# ==========================================
# Meeting Room 배포 스크립트
# ==========================================

set -e

echo "=== Meeting Room 배포 시작 ==="

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 환경 변수 확인
APP_PATH=${APP_PATH:-/var/www/meeting-room}
STORAGE_PATH=${STORAGE_PATH:-/var/www/meeting-room-storage}

echo -e "${YELLOW}APP_PATH: ${APP_PATH}${NC}"
echo -e "${YELLOW}STORAGE_PATH: ${STORAGE_PATH}${NC}"

# 1. 코드 업데이트
echo -e "\n${GREEN}[1/8] 코드 업데이트...${NC}"
cd "$APP_PATH"
git pull origin master

# 2. Composer 의존성 설치
echo -e "\n${GREEN}[2/8] Composer 의존성 설치...${NC}"
composer install --no-dev --optimize-autoloader

# 3. NPM 빌드
echo -e "\n${GREEN}[3/8] 프론트엔드 빌드...${NC}"
npm ci
npm run build

# 4. 캐시 클리어 및 최적화
echo -e "\n${GREEN}[4/8] 캐시 최적화...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. 마이그레이션 실행
echo -e "\n${GREEN}[5/8] 데이터베이스 마이그레이션...${NC}"
php artisan migrate --force

# 6. Passport 키 생성 (없는 경우만)
echo -e "\n${GREEN}[6/8] Passport 키 확인...${NC}"
if [ ! -f "$STORAGE_PATH/oauth-private.key" ] || [ ! -f "$STORAGE_PATH/oauth-public.key" ]; then
    echo "Passport 키 생성 중..."
    php artisan passport:keys --force

    # storage 디렉토리로 키 이동 (심볼릭 링크 또는 복사)
    if [ -f "storage/oauth-private.key" ]; then
        cp storage/oauth-private.key "$STORAGE_PATH/"
        cp storage/oauth-public.key "$STORAGE_PATH/"
        echo "키 파일이 $STORAGE_PATH 로 복사되었습니다."
    fi
else
    echo "Passport 키가 이미 존재합니다."
fi

# 7. Personal Access Client 생성 (없는 경우만)
echo -e "\n${GREEN}[7/8] Passport 클라이언트 확인...${NC}"
if [ -z "$PASSPORT_PERSONAL_ACCESS_CLIENT_ID" ]; then
    echo -e "${YELLOW}경고: PASSPORT_PERSONAL_ACCESS_CLIENT_ID가 설정되지 않았습니다.${NC}"
    echo "다음 명령어로 클라이언트를 생성하세요:"
    echo "  php artisan passport:client --personal --name='Meeting Room Personal Access Client'"
    echo "생성된 Client ID를 .env 파일에 PASSPORT_PERSONAL_ACCESS_CLIENT_ID로 설정하세요."
else
    echo "Passport 클라이언트 ID가 설정되어 있습니다."
fi

# 8. 권한 설정
echo -e "\n${GREEN}[8/8] 권한 설정...${NC}"
chown -R www-data:www-data "$STORAGE_PATH"
chmod -R 775 "$STORAGE_PATH"

echo -e "\n${GREEN}=== 배포 완료 ===${NC}"
echo -e "서비스 URL: https://meeting-room.shaul.link"
