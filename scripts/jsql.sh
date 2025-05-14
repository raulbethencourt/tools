#!/bin/bash

# Error handling function
error_exit() {
  echo "Error: $1" >&2 && exit "${2:-1}"
}

help=1
verbose=0
while getopts "hs:p:l:w:v" opt; do
  # shellcheck disable=SC2220
  case "$opt" in
  h) help=0 ;;
  s) server="$OPTARG" ;;
  p) loginPath="$OPTARG" ;;
  l) limit="$OPTARG" ;;
  w) where="$OPTARG" ;;
  v) verbose=1 ;;
  esac
done
shift $((OPTIND - 1))

jsonFields=""
mysql=$(which mysql)
[ ! -x "$mysql" ] && error_exit "MySQL client not found. Please install MySQL client." 1
# Validate database name (only alphanumeric, underscore and hyphen allowed)
[[ -n "$1" && "$1" =~ ^[a-zA-Z0-9_-]+$ ]] && db="$1" || help=0
# Validate table name (only alphanumeric, underscore and hyphen allowed)
[[ -n "$2" && "$2" =~ ^[a-zA-Z0-9_-]+$ ]] && table="$2" || help=0
fields="$3"

[ "$#" -eq 0 ] || [ "$help" -eq 0 ] && {
  cat <<EOF >&2
Usage: $0 [OPTION]... [DATABASE]... [TABLE]... [FIELDS]...
Recover data from mysql table and transform it to json array.

The database and the table are obligatory.
The fields list must be an quoted string separete by spaces and they are optional.
  
  -h  show this help and quit
  -s  make ssh query to remote server
  -p  use specific login-path (default = dockerroot)
  -l  query results limit (default = 50)
  -w  where clause for the mysql query (default = 1)
  -v  enable verbose mode (shows MySQL queries)
  
Examples:
  jsql some_db some_table
  jsql -s vh40 -l 10 some_db some_table "field1 field2 field3"
  jsql -w "id like '%123%'" some_db some_table "*"
EOF
  exit 1
}

# =================
# BEGIN MAIN SCRIPT
# =================

if [ -z "$fields" ] || [ "$fields" = "*" ]; then
  # We create the query and we add ssh cmd si a server is given
  mysqlQueryDescribe="mysql --login-path='${loginPath:-dockerroot}' --max_allowed_packet=100M \
    --connect-timeout=10 -NLse 'use $db;describe $table;'"

  [ "$verbose" -eq 1 ] && {
    echo "MySQL Describe Query:" >&2
    echo "$mysqlQueryDescribe" >&2
  }
  [ -n "$server" ] && mysqlQueryDescribe="ssh $server \"$mysqlQueryDescribe\"" # do command into server

  # Create list of fields for json using the result of mysql describe query
  while IFS= read -r fieldDescription; do
    field=$(echo "$fieldDescription" | awk '{print $1}' 2>/dev/null)
    jsonFields="'$field',$field,$jsonFields"
  done < <(eval "$mysqlQueryDescribe" | tac || { # Execute the mysql query and reorder fields
    echo "Mysql error in describe query." >&2 && exit 1
  })
else
  # We build the mysql json object query with "'field', field" syntax
  for field in $fields; do
    jsonFields="'$field',$field,$jsonFields"
  done
fi

# Create query :
#   We use a subquery to make limit effectif
#   I remove extra coma generated when creating list of fields
query=$(
  cat <<SQL
use $db;
select json_arrayagg(jsonObj)
from 
(
  select json_object(${jsonFields::-1}) as jsonObj
  from $table 
  where ${where:-1}
  limit ${limit:-50}
) t;
SQL
)

[ "$verbose" -eq 1 ] && {
  echo "DEBUG: MySQL Query:" >&2
  echo "$query" >&2
}

# Validate where clause for basic SQL injection prevention
[ -n "$where" ] && {
  ! [[ "$where" =~ ^[[:alnum:]_[:space:]=\>\<\.\'\"%-]+$ ]] && error_exit "Invalid WHERE clause" 1
}

# We create the query and we add ssh cmd si a server is given
mysqlQueryJson="$mysql --login-path=${loginPath:-dockerroot} --max_allowed_packet=100M \
  --connect-timeout=10 -NLse \"$query\""
[ -n "$server" ] && mysqlQueryJson="ssh -o ConnectTimeout=10 $server \"$(echo "$mysqlQueryJson" |
  sed 's/"use/\\"use/g' |
  sed 's/;"/;\\"/g')\"" # Must escape the double quote for ssh command with timeout

queryResult=$(
  eval "$mysqlQueryJson" || {
    echo "Mysql error in json query." >&2 && exit 1
  }
)
# HACK: parsing content to make jq understand json values
echo "$queryResult" | sed -e 's/\\\\"/"/g' -e 's/"\[/\[/g' -e 's/\]"/\]/g' \
  -e 's/"{/{/g' -e 's/}"/}/g' -e 's/\\\\n//g' -e 's/\\\\//g'

exit 0
