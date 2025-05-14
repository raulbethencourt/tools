#!/bin/bash

initANSI() {
  # Foreground colors
  blackf=$(tput setaf 0)
  redf=$(tput setaf 1)
  greenf=$(tput setaf 2)
  yellowf=$(tput setaf 3)
  bluef=$(tput setaf 4)
  purplef=$(tput setaf 5)
  cyanf=$(tput setaf 6)
  whitef=$(tput setaf 7)

  # Background colors
  blackb=$(tput setab 0)
  redb=$(tput setab 1)
  greenb=$(tput setab 2)
  yellowb=$(tput setab 3)
  blueb=$(tput setab 4)
  purpleb=$(tput setab 5)
  cyanb=$(tput setab 6)
  whiteb=$(tput setab 7)

  # Bold, italic, underline and inverse style toggles
  boldon=$(tput smso)
  boldoff=$(tput rmso)
  italicon=$(tput sitm)
  italicoff=$(tput ritm)
  ulon=$(tput smul)
  uloff=$(tput rmul)
  inver=$(tput rev)

  reset=$(tput sgr0)
}
validint() {
  # Validate first field and test that value against min value $2 and/or
  #   max value $3 if they are supplied. If the value isn't within range
  #   or it's not composed of just digits, fail.

  number="$1"
  min="$2"
  max="$3"

  # set -x

  [ -z "$number" ] && echo "You didn't enter anything. Please enter a number" >&2 && return 1

  # Is the first character a '-' sign?
  [ "$(echo "$number" | cut -c1)" = "-" ] && testvalue=$(echo "$number" | cut -d- -f2) || testvalue="$number"

  # Create a version of the number that has no digits for testing.
  nodigits=$(echo "$testvalue" | sed ' s/[[:digit:]]//g')

  [ -n "$nodigits" ] && echo "Invalid number format! Only digits, no commas, spaces, etc." >&2 && return 1

  [ -n "$min" ] && {
    # Is the input less than the minimum value ?
    [ "$number" -lt "$min" ] && echo "Your value is too small: smallest acceptable value is $min." >&2 && return 1
  }

  [ -n "$max" ] && {
    # Is the input greater than the maximun value ?
    [ "$number" -gt "$max" ] && echo "Your value is too big: largest acceptable value is $max." >&2 && return 1
  }

  return 0
}
validfloat() {
  fvalue="$1"

  # Check whether the input number has a decimal point.
  # shellcheck disable=SC2001,SC2015
  [ -n "$(echo "$fvalue" | sed 's/[^.]//g')" ] && {
    decimalPart=$(echo "$fvalue" | cut -d. -f1)
    fractionalPart=$(echo "$fvalue" | cut -d. -f2)

    # Start by testing the decimal part, which is everything
    #   to the left of the decimal point.
    [ -n "$decimalPart" ] && {
      ! validint "$decimalPart" "" "" && return 1
    }

    # Now let's test the fractional value.
    #
    # To start, you can't have a negative sign after the decimal point
    #   like 33.-1, so let's test for the '-' sign in the decimal.
    [ -n "$(echo "$fractionalPart" | sed "s/[^-]//g")" ] && {
      echo "Invalid floating-point number: '-' not allowed after decimal point." >&2 && return 1
    }
    [ -n "$fractionalPart" ] && {
      validint "$fractionalPart" "0" "" && return 0 || return 1
    } || {
      echo "The number is empty after the dot." >&2 && return 1
    }
  } || {
    echo "The input is not a floating-point number." >&2 && return 1
  }
}
exceedDaysInMonth() {
  # Given a month name and day number in that month, this function will
  #   return 0 if the specified day value is less than or equal to the
  #   max days in the month; 1 otherwise.

  case $(echo "$1" | tr '[:upper:]' '[:lower:]') in
  jan*) days=31 ;; feb*) days=28 ;;
  mar*) days=31 ;; apr*) days=30 ;;
  may*) days=31 ;; jun*) days=30 ;;
  jul*) days=31 ;; aug*) days=31 ;;
  sep*) days=30 ;; oct*) days=31 ;;
  nov*) days=30 ;; dec*) days=31 ;;
  *)
    echo "$0: Unknown month name $1" >&2
    exit 1
    ;;
  esac

  [ "$2" -lt 1 ] || [ "$2" -gt "$days" ] && return 1 || return 0 # The number is valid day.
}
isLeapYear() {
  # This function returns 0 if the specified year is a leap year;
  #   1 otherwise.
  #
  # The formula for chequing whether a year is a leap year is :
  #   1. Years not divisible by 4 are not leap years.
  #   2. Years divisible by 4 and by 400 are leap years.
  #   3. Years divisible by 4, not divisible by 400, but divisible
  #      by 100 are not leap years.
  #   4. All other years divisible by 4 are leap years.

  year="$1"
  if [ "$((year % 4))" -ne 0 ]; then
    return 1 # Nope, not a leap year.
  elif [ "$((year % 400))" -eq 0 ]; then
    return 0 # Yes, it's a leap year.
  elif [ "$((year % 100))" -eq 0 ]; then
    return 1
  else
    return 0
  fi
}
validAlphaNum() {
  # Remove all unacceptable chars and whitespace.
  validchars=${1//[^[:alnum:]]/}

  [ "$validchars" = "$1" ] && return 0 || return 1
}
monthNumToName() {
  # Set the 'month' variable to the appropriate value,
  case "$1" in
  1) month="Jan" ;; 2) month="Feb" ;;
  3) month="Mar" ;; 4) month="Apr" ;;
  5) month="May" ;; 6) month="Jun" ;;
  7) month="Jul" ;; 8) month="Aug" ;;
  9) month="Sep" ;; 10) month="Oct" ;;
  11) month="Nov" ;; 12) month="Dec" ;;
  *) echo "$0: Unknown month value $1" >&2 && exit 1 ;;
  esac
  return 0
}
nicenumber() {
  separator=${1//[[:digit:]]/}
  [ -n "$separator" ] && [ "$separator" != "$DD" ] && {
    echo "$0 : Unknown decimal separator $separator encountered." >&2
    exit 1
  }

  # Note that we assume that '.' is the decimal separator in the INPUT value
  #   to this script. The decimal separator in the output value is '.' unless
  #   specified by the user with the -d flag.
  integer=$(echo "$1" | cut -d. -f1) # Left of the decimal
  decimal=$(echo "$1" | cut -d. -f2) # Right of the decimal

  # Check if number has more than the integer part.
  [ "$decimal" != "$1" ] && result="${DD:= '.'}$decimal" # There's a fractional part, so let's include it.

  thousands=$integer

  while [ "$thousands" -gt 999 ]; do
    remainder=$((thousands % 1000))

    # We need 'remainder' to bet three digits. Do we need to add zeros ?
    while [ ${#remainder} -lt 3 ]; do # Force leading zeros
      remainder="0$remainder"
    done

    result="${TD:=","}${remainder}${result}" # Builds right to left
    thousands=$((thousands / 1000))          # To left of remainder, if any
  done

  nicenum="${thousands}${result}"
  [ -n "$2" ] && echo "$nicenum"
}
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
echon() {
  if checkForCmdInPath printf; then
    printf "%s" "$*"
  else
    echo "$*" | tr -d '\n'
  fi
}
getFileWithFzf() {
  file="$1"
  [ -z "$file" ] && exit 1

  FD=$(find "$file" -mindepth 1 -maxdepth 1 ! -name '.*')

  dir=$(
    echo "$FD" |
      sed -n '1p' |
      xargs -I {} dirname {}
  )
  file=$(
    echo "$FD" |
      xargs -I {} basename {} 2>/dev/null |
      fzf --bind=tab:up --bind=btab:down --bind=ctrl-g:first
  ) || exit 1
  [ -d "$dir/$file" ] && getFileWithFzf "$dir/$file/"
}
getEnvFileFromPath() {
  envDir="$1"
  [ -z "$envDir" ] && {
    echo "You must specify env directory." >&2 && exit 1
  }
  envFile=$(find "$envDir" -name ".env*")
  parentDir=$(dirname "$envDir")

  [ "$envDir" == "$HOME" ] && {
    echo "You don't have any env file in your path." >&2 && exit 1
  }
  [ -z "$envFile" ] && getEnvFileFromPath "$parentDir"
}
