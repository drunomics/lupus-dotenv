## A bash file that can be sourced for loading the environment.
# Usage in bash:
#   source ./dotenv/loader.sh
#
# Optional:
# SITE=example.com - Use the respective site instead of the default site while
#   preparing the app environment.

# Determine current dir and load .env file so PHAPP_ENV can be initialized.
DIR=$( dirname "${BASH_SOURCE[0]}" )

if ! APP_ENV="$(php $DIR/loader.php app)"; then
  echo "ERROR: $APP_ENV" > /dev/stderr
else
  # Export all the variables by enabling -a.
  set -a
  eval "$APP_ENV"
  eval "$(php $DIR/loader.php site)"
  set +a
fi
