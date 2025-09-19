#!/usr/bin/env bash

DIR=$(dirname "$(realpath "$0")")

mkdir -p "${DIR}"/build
rm -r "${DIR}"/build/*
git checkout-index -a -f --prefix="${DIR}"/build/eve-srp/

# Add required configuration
{
  echo "EVE_SRP_DB_URL=sqlite:///:memory:"
  echo "EVE_SRP_SSO_CLIENT_ID="
  echo "EVE_SRP_SSO_CLIENT_SECRET="
  echo "EVE_SRP_SSO_REDIRECT_URI="
} > "${DIR}"/build/eve-srp/config/.env

# Build
export UID
docker compose exec -u $UID eve_srp_php sh -c "cd build/eve-srp && composer install --no-dev --optimize-autoloader --no-interaction"
docker compose exec -u $UID eve_srp_php sh -c "cd build/eve-srp && bin/doctrine orm:generate-proxies"
docker compose exec -u $UID eve_srp_node sh -c "cd build/eve-srp && npm ci && npm run build"

# Remove unnecessary files
rm "${DIR}"/build/eve-srp/.editorconfig
rm "${DIR}"/build/eve-srp/.gitattributes
rm "${DIR}"/build/eve-srp/.gitignore
rm "${DIR}"/build/eve-srp/build.sh
rm "${DIR}"/build/eve-srp/composer.json
rm "${DIR}"/build/eve-srp/composer.lock
rm "${DIR}"/build/eve-srp/compose.yaml
rm "${DIR}"/build/eve-srp/package.json
rm "${DIR}"/build/eve-srp/package-lock.json
rm "${DIR}"/build/eve-srp/phpunit.xml
rm "${DIR}"/build/eve-srp/Screenshot-Edit-Request.png
rm "${DIR}"/build/eve-srp/webpack.config.js
rm "${DIR}"/build/eve-srp/config/.env
rm "${DIR}"/build/eve-srp/config/docker-nginx.conf
rm "${DIR}"/build/eve-srp/config/Dockerfile
rm "${DIR}"/build/eve-srp/config/dockerfile-php80-fpm
rm "${DIR}"/build/eve-srp/config/dockerfile-php81-fpm
rm "${DIR}"/build/eve-srp/config/dockerfile-php82-fpm
rm "${DIR}"/build/eve-srp/storage/.gitkeep
rm -r "${DIR}"/build/eve-srp/.github
rm -r "${DIR}"/build/eve-srp/node_modules
rm -r "${DIR}"/build/eve-srp/resources
rm -r "${DIR}"/build/eve-srp/tests

# Create archive
cd "${DIR}"/build || exit
if [[ "$1" ]]; then
    NAME=$1
else
    NAME=$(git rev-parse --short HEAD)
fi
tar -czf eve-srp-"${NAME}".tar.gz eve-srp
sha256sum eve-srp-"${NAME}".tar.gz > eve-srp-"${NAME}".sha256

# Cleanup
rm -r "${DIR}"/build/eve-srp
