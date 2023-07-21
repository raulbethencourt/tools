#!/bin/sh
# nicenumber--Given a number, shows it in comma-separated form. Expects DD
#   (decimal point delimiter) and TD (thousands delimiter) to be iinstantiated,
#   Instantiates nicenum or, if a second arg is specified, the output is
#   echoed to stdout.

nicenumber() {
	# Note that we assume that '.' is the decimal separator in the INPUT value
	#   to this script. The decimal separator in the output value is '.' unless
	#   specified by the user with the -d flag.

	integer=$(echo "$1" | cut -d. -f1) # Left of the decimal
	decimal=$(echo "$1" | cut -d. -f2) # Right of the decimal

	# Check if number has more than the integer part.
	if [ "$decimal" != "$1" ]; then
		# There's a fractional part, so let's include it.
		result="${DD:= '.'}$decimal"
	fi

    thousands=$integer

    while [ $thousands -gt 999 ]; do
        remainder=$(($thousands % 1000))

        # TODO: continue

        echo "$remainder"
    done

    return
}

nicenumber 2000000.870
