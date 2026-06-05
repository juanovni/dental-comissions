#!/usr/bin/env bash

set -e

if [ -n "$POSTGRES_USER" ] && [ -n "$POSTGRES_DB" ]; then
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
        CREATE DATABASE ${POSTGRES_DB}_testing;
        GRANT ALL PRIVILEGES ON DATABASE ${POSTGRES_DB}_testing TO $POSTGRES_USER;
EOSQL

    echo "Testing database ${POSTGRES_DB}_testing created."
fi
