#!/usr/bin/env bash
### A small helper that outputs all dotenv variables.
# ./dotenv/print.sh --help prints usage information

DIR=$( dirname $0)

if ! command -v php >/dev/null; then
  echo "ERROR: PHP is required for computing dotenv." >/dev/stderr
  exit 1;
fi

set -e
if [[ "$1" = "app" ]]; then
  php $DIR/loader.php app $2
elif [[ -z $1 ]]; then
  php $DIR/loader.php app
  ## Apply app variables before generated site variables.
  ## see loader.sh
  set -a
  eval "$(php $DIR/loader.php app)"
  set +a
  php $DIR/loader.php site
else
  echo "By default, the complete environment for a given site (determined by $SITE) is printed."
  echo "Usage: PHAPP_ENV=foo $0"
  echo "Usage: PHAPP_ENV=foo SITE=example $0"
  echo ""
  echo "If the optional parameter \"app\" app is passed, only app variables - shared across all sites - are printed."
  echo "Usage: PHAPP_ENV=foo $0 app"
  echo "In order to skip reading an existing \".env\" file, e.g. when generating it,  \"false\" may be passed as second argument as so:"
  echo "Usage: PHAPP_ENV=foo $0 app false"
fi
