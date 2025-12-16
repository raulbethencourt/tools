#!/bin/bash

languages=$(echo "bash rust php lua nodejs typescript" | tr ' ' '\n')
core_utils=$(echo "read xargs find fd mv sed awk rg grep tail df" | tr ' ' '\n')

selected=$(print "$languages\n$core_utils" | fzf)
read -r "query?query: " query

if print "$languages" | grep -qs "$selected"; then
  tmux neww zsh -c "curl cht.sh/$selected/$(echo "$query" | tr ' ' '+') & while [ : ]; do sleep 1; done"
else
  tmux neww zsh -c "curl cht.sh/$selected~$query & while [ : ]; do sleep 1; done"
fi
