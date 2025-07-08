#!/bin/bash
set -e

# Perform all actions as the 'postgres' user
export PGUSER="$POSTGRES_USER"

# Create the databases for each microservice
psql -v ON_ERROR_STOP=1 --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE app_order;
    CREATE DATABASE app_invoice;
EOSQL
