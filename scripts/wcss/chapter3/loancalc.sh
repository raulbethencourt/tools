#!/bin/bash

# loancalc -- Given a principal loan amount, interest rate, and
#   duration of loan (years), calculates the per-payment amount.

# Formula is M = P * (J / (1 - (1 + J) ^ -N)),
#   where P = principal, J = monthly interest rate, N = duration (months).

# Users typically enter P, I (annual interest rate), and L (length, years).

. ../../library.sh # Start by sourcing the script library.

[ "$#" -ne 3 ] && {
  echo "Usage: $0 principal interest loan-duration-years" >&2
  exit 2
}

P=$1 I=$2 L=$3
J="$(scriptbc -p 8 "$I" / \(12 \* 100 \))"
