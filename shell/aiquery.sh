#!/bin/bash

# shellcheck disable=SC1091
. "$SCRIPTSPATH"/library.sh && initANSI # Get colors.

# Exit on error, undefined variables, and propagate pipe failures
set -euo pipefail

# Configuration
API_ENDPOINT="https://models.github.ai/inference/chat/completions"
API_TOKEN="${GITHUB_TOKEN:-}"

usage() {
  cat <<EOF >&2
Usage: $(basename "$0") [options] <search_path>

Options:
  -p, --prompt <text>  Prompt to send to the ai
  -h, --help           Display this help message

Example:
  $(basename "$0") -p 'i want to create an script to query an ai'

Requirements:
  - Set GITHUB_TOKEN environment variable with your GitHub personal access token
    - Your token needs appropriate permissions for GitHub Models API access
EOF
  exit 1
}

# Function to check if GitHub API token is available
check_api_token() {
  [[ -z "$API_TOKEN" ]] && {
    error_exit "GITHUB_TOKEN environment variable is not set.\n" \
      "Please set it with: export GITHUB_TOKEN='your-github-token-here'" 1
  }
  return 0
}
