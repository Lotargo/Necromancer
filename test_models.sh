#!/bin/bash
PROVIDERS=("gemini" "groq" "cerebras" "sambanova")
for prov in "${PROVIDERS[@]}"; do
    echo "Testing provider: $prov"
    DIR="tabularium/provisores/$prov"
    KEY=$(head -n 1 "$DIR/claves.txt")
    URL=$(head -n 1 "$DIR/url.txt")
    MODELS=$(cat "$DIR/modela.txt")

    for model in $MODELS; do
        echo "  Testing model: $model"

        HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$URL" \
            -H "Authorization: Bearer $KEY" \
            -H "Content-Type: application/json" \
            -d '{
                "model": "'"$model"'",
                "messages": [{"role": "user", "content": "test"}],
                "max_tokens": 10
            }')

        if [ "$HTTP_STATUS" -eq 200 ]; then
            echo "    [OK] $model"
        else
            echo "    [FAIL] $model (HTTP $HTTP_STATUS)"
            curl -s -X POST "$URL" \
            -H "Authorization: Bearer $KEY" \
            -H "Content-Type: application/json" \
            -d '{
                "model": "'"$model"'",
                "messages": [{"role": "user", "content": "test"}],
                "max_tokens": 10
            }'
            echo ""
        fi
    done
done
