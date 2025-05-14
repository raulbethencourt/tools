#!/bin/bash

scriptsPath="$HOME"/tools/scripts

# shellcheck disable=SC1091
. "$scriptsPath"/library.sh && initANSI # Get colors.

help=1
while getopts "hd:" opt; do
  # shellcheck disable=SC2220
  case "$opt" in
  h) help=0 ;;
  d) manDir="$OPTARG" ;;
  esac
done
shift $((OPTIND - 1))

# shellcheck disable=SC2154
[ "$help" -eq 0 ] && {
  cat <<EOF >&2
${purplef}Usage:${reset} $0 [OPTION]...
Show downloaded manuals.

  -h  show this help and quit
  -d  The manuals directory

${purplef}Examples:${reset}
  ${greenf}fman${reset}
  ${greenf}fman${reset} -d ~/manuals
EOF
  exit 1
}

# =================
# BEGIN MAIN SCRIPT
# =================

manDir=${manDir:-$scriptsPath/manuals/}
[ ! -d "$manDir" ] && {
  echo "Manual directory not found it." >&2 && exit 1
}

# Make a selection from the list of manuals with fzf
getFileWithFzf "${manDir:-$scriptsPath/manuals/}"

suffix=$(echo "$file" | grep -oP '[^\.]*$')

case "$suffix" in
"md") glow -p "$dir/$file" ;;
"html") lynx "$dir/$file" ;;
"json") jq "$dir/$file" | less -R ;;
"pdf") pdftotext "$dir/$file" - | ccze -A | less -R ;;
*) echo "File type not handeled." >&2 && exit 1 ;;
esac

exit 0
