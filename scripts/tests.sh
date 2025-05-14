#!/bin/bash

help=1
while getopts "hu:" opt; do
  # shellcheck disable=SC2220
  case "$opt" in
  h) help=0 ;;
  u) url="--url=$OPTARG" ;;
  esac
done
shift $((OPTIND - 1))

[ "$help" -eq 0 ] && {
  cat <<EOF >&2
Usage: $0 [OPTION]...
Read the list of bns tests and allow dynamic choise
using the utility fzf.

  -h  show this help and quit
  -u  the url to test (default=http://localhost:8080)

Examples:
  tests
  tests -u https://aff.bluenotecrm.net/live/aff14
EOF
  exit 1
}

# =================
# BEGIN MAIN SCRIPT
# =================

# Display list of tests trough fzf for selection.
test=$(
  find "$BNS_TOOLS"/tests -type f -name '*.curl' -print0 |
    xargs -0I {} basename {} |
    fzf
)
[ -z "$test" ] && exit 1 # Exit if we don't chose a test

# If we don't pass the url flag we use the "bns test" default
bns test -v --continue-on-fail "${url:-}" --curl "$BNS_TOOLS"/tests/"$test"

cat <<EOL
----------------------
To relaunch this test:
----------------------
bns test -v --continue-on-fail ${url:-} --curl "$BNS_TOOLS"/tests/$test
EOL

exit 0
