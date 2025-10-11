#!/bin/bash

# Script pour tester toutes les pages de l'application
# Vérifie les codes HTTP et identifie les erreurs

BASE_URL="http://localhost:8000"
LOG_FILE="test-pages-results.log"

echo "=== TEST DES PAGES - $(date) ===" > $LOG_FILE
echo "" >> $LOG_FILE

# Fonction pour tester une URL
test_url() {
    local url=$1
    local name=$2
    local http_code=$(curl -s -o /dev/null -w "%{http_code}" -L "$url" -H "Accept: application/json")

    if [ $http_code -eq 200 ] || [ $http_code -eq 302 ]; then
        echo "✓ $name - HTTP $http_code"
        echo "✓ $name - HTTP $http_code" >> $LOG_FILE
    else
        echo "✗ $name - HTTP $http_code"
        echo "✗ $name - HTTP $http_code" >> $LOG_FILE
    fi
}

echo "Testing public pages..."
test_url "$BASE_URL/" "Homepage"
test_url "$BASE_URL/login" "Login"
test_url "$BASE_URL/register" "Register"

echo ""
echo "Testing authenticated pages (will redirect to login)..."
test_url "$BASE_URL/dashboard" "Dashboard"
test_url "$BASE_URL/user/dashboard" "User Dashboard"
test_url "$BASE_URL/articles" "Articles Index"
test_url "$BASE_URL/events" "Events Index"
test_url "$BASE_URL/books" "Books Index"
test_url "$BASE_URL/trainings" "Trainings Index"
test_url "$BASE_URL/chat" "Chat"
test_url "$BASE_URL/profile" "Profile"

echo ""
echo "Results saved to $LOG_FILE"
cat $LOG_FILE
