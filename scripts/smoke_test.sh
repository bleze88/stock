#!/bin/bash
# Test de fumée post-déploiement : quelques vérifications rapides via curl.
set -euo pipefail

BASE="${1:-https://votre-domaine.example}"
FAIL=0

check() {
    local desc="$1" expected="$2" actual="$3"
    if [ "$actual" = "$expected" ]; then
        echo "OK   - $desc"
    else
        echo "FAIL - $desc (attendu $expected, obtenu $actual)"
        FAIL=1
    fi
}

CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/index.php?r=dashboard")
check "Dashboard non connecté redirige (302)" "302" "$CODE"

CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/index.php?r=login")
check "Page de connexion accessible (200)" "200" "$CODE"

HEADERS=$(curl -sI "$BASE/index.php?r=login")
echo "$HEADERS" | grep -qi "x-frame-options" && echo "OK   - Header X-Frame-Options présent" || { echo "FAIL - Header X-Frame-Options absent"; FAIL=1; }
echo "$HEADERS" | grep -qi "strict-transport-security" && echo "OK   - Header HSTS présent" || { echo "FAIL - Header HSTS absent"; FAIL=1; }

CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/assets/css/app.css")
check "Assets CSS servis (200)" "200" "$CODE"

exit $FAIL
