#!/bin/bash
# Meilisearch startup script for Kabas

cd "$(dirname "$0")"

# Configuration
export MEILI_HTTP_ADDR="127.0.0.1:7700"
export MEILI_DB_PATH="./data"
export MEILI_ENV="production"
export MEILI_MASTER_KEY="kabasSecureMasterKey2025ChangeThis"
export MEILI_NO_ANALYTICS=true

# Start Meilisearch
./meilisearch
