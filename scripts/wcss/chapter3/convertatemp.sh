#!/bin/bash

# convertatemp -- Temperature conversion script that lest the user enter
#   a temperature in Fahrenheit, Celsius, or Kelvin and receive the
#   equivalent temperature in the other two units as the output.

[ "$#" -eq 0 ] && {
  cat <<EOF >&2
Usage: $0 temperature[F|C|K]
where the suffix:
  F   indicates input is in Fahrenheit (default)
  C   indicates input is in Celsius
  K   indicates input is in Kelvin
EOF
  exit 1
}

unit="$(echo "$1" | sed -e 's/[-[:digit:]]*//g' | tr '[:lower:]' '[:upper:]')"
# shellcheck disable=SC2001
temp="$(echo "$1" | sed -e 's/[^-[:digit:]]*//g')"

case ${unit:=F} in
F) # Fahrenheit to Celsius formula: Tc = (F -32) / 1.8
  farn="$temp"
  cels="$(echo "scale=2; ($farn -32) / 1.8" | bc)"
  kelv="$(echo "scale=2; $cels + 273.15" | bc)"
  ;;
C) # Celsius to Fahrenheit formula: Tf = (9/5)*Tc+32
  cels="$temp"
  kelv="$(echo "scale=2; $cels + 273.15" | bc)"
  farn="$(echo "scale=2; (1.8 * $cels) + 32" | bc)"
  ;;
K) # Celsius = Kelvin - 273.15, then use Celsius -> Fahrenheit formula
  kelv="$temp"
  cels="$(echo "scale=2; $kelv - 273.15" | bc)"
  farn="$(echo "scale=2; (1.8 * $cels) + 32" | bc)"
  ;;
*) echo "Given temperature unit is not supported" && exit 1 ;;
esac

cat <<EOF >&2
Fahrenheit = $farn
Celsius    = $cels
kelvin     = $kelv
EOF
exit 0
