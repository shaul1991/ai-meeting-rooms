#!/bin/bash
# ==========================================
# Passport 초기 설정 스크립트
# ==========================================
# 사용법: ./scripts/setup-passport.sh
#
# 이 스크립트는 다음을 수행합니다:
# 1. Passport 키 생성 (oauth-private.key, oauth-public.key)
# 2. Personal Access Client 생성
# 3. .env 파일에 클라이언트 ID 설정 안내
# ==========================================

set -e

echo "=== Passport 초기 설정 ==="

# 색상 정의
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 프로젝트 디렉토리로 이동
cd "$(dirname "$0")/.."

# 1. Passport 키 생성
echo -e "\n${GREEN}[1/3] Passport 키 생성...${NC}"
if [ -f "storage/oauth-private.key" ] && [ -f "storage/oauth-public.key" ]; then
    echo -e "${YELLOW}Passport 키가 이미 존재합니다.${NC}"
    read -p "키를 다시 생성하시겠습니까? (y/N): " regenerate
    if [ "$regenerate" = "y" ] || [ "$regenerate" = "Y" ]; then
        php artisan passport:keys --force
        echo -e "${GREEN}키가 재생성되었습니다.${NC}"
    fi
else
    php artisan passport:keys
    echo -e "${GREEN}키가 생성되었습니다.${NC}"
fi

# 2. Personal Access Client 생성
echo -e "\n${GREEN}[2/3] Personal Access Client 생성...${NC}"
echo "Personal Access Client를 생성합니다..."

# 클라이언트 생성 및 출력 캡처
output=$(php artisan passport:client --personal --name="Meeting Room Personal Access Client" 2>&1)
echo "$output"

# Client ID 추출 (Passport v13 형식)
client_id=$(echo "$output" | grep -oP 'Client ID[:\s]+\K[a-f0-9-]+' || true)

if [ -z "$client_id" ]; then
    # 대체 패턴 시도
    client_id=$(echo "$output" | grep -oP '\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b' | head -1 || true)
fi

# 3. 환경 변수 설정 안내
echo -e "\n${GREEN}[3/3] 환경 변수 설정 안내${NC}"
echo -e "${CYAN}========================================${NC}"
echo -e "다음 값을 ${YELLOW}.env${NC} 파일에 추가하세요:"
echo ""
if [ -n "$client_id" ]; then
    echo -e "  ${GREEN}PASSPORT_PERSONAL_ACCESS_CLIENT_ID=${client_id}${NC}"
else
    echo -e "  ${YELLOW}PASSPORT_PERSONAL_ACCESS_CLIENT_ID=<위에 출력된 Client ID>${NC}"
fi
echo -e "  ${GREEN}PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=${NC}"
echo -e "  ${GREEN}PASSPORT_ACCESS_TOKEN_EXPIRE=3600${NC}"
echo -e "  ${GREEN}PASSPORT_REFRESH_TOKEN_EXPIRE=14${NC}"
echo ""
echo -e "${CYAN}========================================${NC}"

echo -e "\n${GREEN}=== Passport 설정 완료 ===${NC}"
