#!/bin/bash

# DIR -- Pretends we're the DIR command in DOS and displays the contents
#   of the specified file, accepting some of the standard DIR flags.

function usage() {
  cat <<EOF >&2
  Usage: $0 [DOS flags] directory or directories
  Where:
   /D        sort by columns
   /H        show help for this shell script
   /N        show long listing format with filenames on right
   /OD       sort by oldest to newest
   /O-D      sort by newest to oldest
   /P        pause after each screenful of information
   /Q        show owner of the file
   /S        recursive listing
   /W        use wide listing format
EOF
  exit 1
}

# =================
# BEGIN MAIN SCRIPT
# =================

postcmd=""
flags=""

while [ "$#" -gt 0 ]; do
  case "$1" in
  /D) flags="$flags -x" ;;
  /H) usage ;;
  /[NQW]) flags="$flags -l" ;;
  /OD) flags="$flags -rt" ;;
  /O-D) flags="$flags -t" ;;
  /P) postcmd="more" ;;
  /S) flags="$flags -s" ;;
  *) break ;; # Unknown flag: probably a DIR specifier break;
    #   so let's get out of the while loop.
  esac
  shift # Processed flag; let's see if there's another.
done

# Done processing flags; now the command itself;
# shellcheck disable=SC2012,SC2124
cmd="ls $flags $@"
[ -n "$postcmd" ] && cmd="$cmd | $postcmd"
eval "$cmd"

exit 0
