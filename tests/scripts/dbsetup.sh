#!/usr/bin/env bash

USER_PGSQL="postgres"
USER_MYSQL="root"
PASS_MYSQL=""

function setup_mysql () {
    mysql -e "DROP DATABASE IF EXISTS bolt_unit_test;" -u $USER_MYSQL $PASS_MYSQL
    mysql -e "CREATE DATABASE bolt_unit_test;" -u $USER_MYSQL $PASS_MYSQL
    mysql -e "CREATE USER 'bolt_unit_test'@'localhost' IDENTIFIED BY 'bolt_unit_test';" -u $USER_MYSQL $PASS_MYSQL
    mysql -e "GRANT ALL PRIVILEGES ON bolt_unit_test.* TO 'bolt_unit_test'@'localhost';" -u $USER_MYSQL $PASS_MYSQL
    mysql -e "FLUSH PRIVILEGES;" -u $USER_MYSQL $PASS_MYSQL
}

function setup_pgsql () {
    psql -c "DROP DATABASE IF EXISTS bolt_unit_test;" -U $USER_PGSQL postgres
    psql -c "CREATE DATABASE bolt_unit_test;" -U $USER_PGSQL postgres
    psql -c "CREATE USER bolt_unit_test WITH PASSWORD 'bolt_unit_test';" -U $USER_PGSQL postgres
    psql -c "GRANT ALL PRIVILEGES ON DATABASE bolt_unit_test TO bolt_unit_test;" -U $USER_PGSQL postgres
}

while getopts ":m:n:p:q:" opt; do
    case $opt in
        m)
            USER_MYSQL=$OPTARG
            ;;
        n)
            PASS_MYSQL="--password=$OPTARG"
            ;;
        p)
            USER_PGSQL=$OPTARG
            ;;
        q)
            export PGPASSWORD="$OPTARG"
            ;;
        \?)
            echo "Invalid option: -$OPTARG" >&2
            exit 1
            ;;
        :)
            echo "Option -$OPTARG requires an argument." >&2
            exit 1
            ;;
    esac
done

echo "Setting up MySQL database, user & privileges…"
setup_mysql
[[ $? -gt 0 ]] && exit 128
echo "Setting up PostgreSQL database, user & privileges…"
setup_pgsql
[[ $? -gt 0 ]] && exit 256
echo "Database set-up complete."
