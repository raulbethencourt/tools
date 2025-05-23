#!/bin/bash

# inpath--Verifies that a specified program is either valid as is
#   or can be found in the PATH directory list

in_path() {
  # Given a command and the PATH, tries to find the command. Returns 0 if
  #   found and executable; 1 if not. Note that this temporarily modifies
  #   the IFS (internal field separator) but restores it upon completion.
  cmd=$1
  ourpath=$2
  result=1
  oldIFS=$IFS
  IFS=":"

  for directory in $ourpath; do
    [ -x "$directory/$cmd" ] && result=0 # If we're here, we found the command.
  done

  IFS=$oldIFS
  return $result
}

checkForCmdInPath() {
  cmd=$1

  [ -n "$cmd" ] && {
    if [ "$(echo "$cmd" | cut -c1)" = "/" ]; then
      [ ! -x "$cmd" ] && return 1
    elif ! in_path "$cmd" "$PATH"; then
      return 2
    fi
  }
}

# Use it as stand-alone
[ "${BASH_SOURCE[*]}" = "$0" ] && {
  [ $# -ne 1 ] && echo "Usage: $0 command" >&2 && exit 1

  checkForCmdInPath "$1"
  case $? in
  0) echo "$1 found in PATH" ;;
  1) echo "$1 not found or not executable" ;;
  2) echo "$1 not found in PATH" ;;
  esac

  exit 0
}
