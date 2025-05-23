#!/bin/sh

searchr=$(
  tldr --list |
    sed 's/,/\n/g' |
    fzf-tmux -p 80%,60% -i --bind=tab:up --bind=btab:down \
      --bind=ctrl-g:first \
      --preview "tldr -t ocean {1} " --preview-window=right,70%
)

if [ -n "$searchr" ]; then
  tldr "$searchr" && exit 0
else
  echo "Nothing found" >&2 && exit 1
fi
