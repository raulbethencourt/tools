#!/bin/bash

scriptsPath="$HOME"/tools/scripts

# shellcheck disable=SC1091
. "$scriptsPath"/library.sh && initANSI # Get colors.

help=1
verbose=""
while getopts "hvd:e:f:" opt; do
  # shellcheck disable=SC2220
  case "$opt" in
  h) help=0 ;;
  v) verbose="-v" ;;
  d) restdir="$OPTARG" ;;
  e) envFile="$OPTARG" ;;
  f) endpointFile="$OPTARG" ;;
  esac
done
shift $((OPTIND - 1))

case "$help" in
0)
  cat <<EOF >&2
${purplef}Usage:${reset} $0 [OPTION]...
Launch curl queries from a files using fzf for selection.

  -h  show this help and quit
  -d  the rest directorie (default=\$HOME/tools/scripts/rest)
  -e  the env file to use (default=\$HOME/tools/scripts/rest/.env)
  -f  the endpoint file to use (default=\$HOME/tools/scripts/rest/endpoint.sh)
      If you use this the rest directory has not use.

${purplef}Examples:${reset}
  ${greenf}fcurl${reset}
  ${greenf}fcurl -d /home/rabeta/tools/scripts/rest -e /home/rabeta/rest/.env
EOF
  exit 1
  ;;
esac

# =================
# BEGIN MAIN SCRIPT
# =================

ftmp=$(mktemp) && rm "$ftmp"

# Use default rest queries directory if not declared
restdir=${restdir:-~/tools/scripts/rest}
[ -d "$restdir" ] || {
  echo "Error : $restdir directory not found." >&2; exit 2
}

# Search endpoint if not passed as option
if [ -z "$endpointFile" ]; then
  # Make a selection from the list of manuals with fzf
  getFileWithFzf "$restdir"
  endpointFile="$dir/$file"
else
  [ ! -f "$endpointFile" ] && {
    echo "Error : $endpointFile file not found." >&2 && exit 1
  }
  dir=$(dirname "$endpointFile")
fi

# Use temporary file to do variables parsing
cat "$endpointFile" >"$ftmp"

# Search env file in directory path
[ -z "$envFile" ] && getEnvFileFromPath "$dir"

# Replace constants values
while IFS= read -r line; do
  echo "$line" | grep -F "#" &>/dev/null || [ -z "$line" ] && continue
  const=$(echo "$line" | awk -F '=' '{print $1}')
  ! grep "$const" "$ftmp" &>/dev/null && continue
  value=$(echo "$line" | awk -F '=' '{print $2}')

  sed -i "s|{{$const}}|$value|g" "$ftmp"
done < <(cat "$envFile")

# Get headers for query
sed -i "s|{{verbose}}|$verbose|g" "$ftmp"

# Execute query
# shellcheck disable=SC1090
response=$(source "$ftmp")
echo "$response"

exit 0
