#!/bin/sh
# nicenumber--Given a number, shows it in comma-separated form. Expects DD
#   (decimal point delimiter) and TD (thousands delimiter) to be iinstantiated,
#   Instantiates nicenum or, if a second arg is specified, the output is
#   echoed to stdout.

nicenumber() {
    # Note that we assume that '.' is the decimal separator in the INPUT value 
    #   to this script. The decimal separator in the output value is '.' unless
    #   specified by the user with the -d flag.

    integer=$(echo "$1" | cut -d. -f1)
    decimal=$(echo "$1" | cut -d. -f2)
}

