#!/bin/bash

# Exit on error, undefined variables, and propagate pipe failures
set -euo pipefail

# Configuration
API_ENDPOINT="https://models.github.ai/inference/chat/completions"
API_TOKEN="${GITHUB_TOKEN:-}"
DEFAULT_OUTPUT_DIR="/home/rabeta/vaults/functional_doc"
OUTPUT_DIR="${DEFAULT_OUTPUT_DIR}"
MAX_FILES_PER_BATCH=5

# Function to display usage instructions
usage() {
  cat <<EOF
Usage: $(basename "$0") [options] <search_path>

Options:
  -t, --type <f|d>      Search for files (f) or directories (d)
  -n, --name <pattern>  Find files/dirs matching pattern
  -o, --output <dir>    Output directory for markdown files (default: ${DEFAULT_OUTPUT_DIR})
  -b, --batch-size <n>  Number of files to process in a single context (default: ${MAX_FILES_PER_BATCH})
  -h, --help            Display this help message

Example:
  $(basename "$0") -t f -n "*.php" -o ~/docs ./src

Requirements:
  - Set GITHUB_TOKEN environment variable with your GitHub personal access token
    - Your token needs appropriate permissions for GitHub Models API access
EOF
  exit 1
}

# Function to check if GitHub API token is available
check_api_token() {
  if [[ -z "$API_TOKEN" ]]; then
    echo "Error: GITHUB_TOKEN environment variable is not set" >&2
    echo "Please set it with: export GITHUB_TOKEN='your-github-token-here'" >&2
    exit 1
  fi
}

# Function to ensure output directory exists
ensure_output_dir() {
  if [[ ! -d "$OUTPUT_DIR" ]]; then
    mkdir -p "$OUTPUT_DIR" || {
      echo "Error: Could not create output directory: $OUTPUT_DIR" >&2
      exit 1
    }
  fi
}

# Function to sanitize filenames for safe usage as part of file paths
sanitize_filename() {
  local filename="$1"
  # Replace problematic characters with underscores
  echo "${filename//[^a-zA-Z0-9._-]/_}"
}

# Function to process a batch of files and generate documentation
process_file_batch() {
  local file_list=("$@")
  local file_contents=""
  local files_description=""
  local output_filename=""

  echo "Processing batch of ${#file_list[@]} files..."

  # Gather contents and build description
  for file in "${file_list[@]}"; do
    # Skip if not a file
    [[ ! -f "$file" ]] && continue

    # Extract relative path from search path for cleaner naming
    local relative_path="${file#"$search_path"/}"

    # Use the first file for naming the output
    if [[ -z "$output_filename" ]]; then
      local sanitized
      sanitized=$(sanitize_filename "$relative_path")
      output_filename="${sanitized%.*}_documentation.md"
    fi

    # Add file path as a header
    files_description+="## File: $relative_path\n\n"

    # Add file content
    file_contents+="File: $relative_path\n\n\`\`\`\n$(cat "$file")\n\`\`\`\n\n"
  done

  # Skip if no valid files
  [[ -z "$files_description" ]] && return

  echo "Generating documentation for batch with primary file: ${file_list[0]}"

  # Prepare prompt for context-aware explanation
  local prompt="I'm analyzing a legacy SugarCRM PHP application. Please provide a comprehensive functional explanation of the following code files, focusing on their purpose, functionality, and how they relate to each other. Format your response in Markdown with clear sections for each file and their relationships:\n\n${file_contents}"

  # Send to GitHub Inference API
  local response
  response=$(curl -s -X POST "$API_ENDPOINT" \
    -H "Authorization: Bearer $API_TOKEN" \
    -H "Accept: application/vnd.github+json" \
    -H "X-GitHub-Api-Version: 2022-11-28" \
    -H "Content-Type: application/json" \
    -d "{
      \"model\": \"openai/gpt-4.1\",
      \"messages\": [
        {\"role\": \"system\", \"content\": \"You are a senior SugarCRM and PHP developer documenting a complex legacy application. Focus on functional explanations including business logic, data flow, and relationships between files. Structure your response as a clear, comprehensive Markdown document that will be useful for developers who need to understand the system.\"}, 
        {\"role\": \"user\", \"content\": $(printf '%s' "$prompt" | jq -s -R .)}
      ],
      \"temperature\": 0.5
    }") || {
    echo "API request failed" >&2
    return 1
  }

  # Extract explanation from response
  local explanation
  explanation=$(echo "$response" | jq -r '.choices[0].message.content // "No explanation available"')

  # Create markdown file with formatted content
  local output_path="${OUTPUT_DIR}/${output_filename}"

  {
    echo "# Functional Documentation"
    echo ""
    echo "## Files Analyzed"
    echo ""
    for file in "${file_list[@]}"; do
      [[ -f "$file" ]] && echo "- \`${file#"$search_path"/}\`"
    done
    echo ""
    echo "$explanation"
    echo ""
    echo "---"
    echo "Generated on $(date '+%Y-%m-%d %H:%M:%S')"
  } >"$output_path"

  echo "Documentation saved to: $output_path"
  echo "----------------------------------------"
}

# Parse command line arguments
search_path=""
search_type="f" # Default to files
name_pattern=""
batch_size=$MAX_FILES_PER_BATCH

while [[ $# -gt 0 ]]; do
  case "$1" in
  -t | --type)
    search_type="$2"
    shift 2
    ;;
  -n | --name)
    name_pattern="$2"
    shift 2
    ;;
  -o | --output)
    OUTPUT_DIR="$2"
    shift 2
    ;;
  -b | --batch-size)
    batch_size="$2"
    shift 2
    ;;
  -h | --help)
    usage
    ;;
  *)
    if [[ -z "$search_path" ]]; then
      search_path="$1"
    else
      echo "Error: Unexpected argument: $1" >&2
      usage
    fi
    shift
    ;;
  esac
done

# Validate arguments
[ -z "$search_path" ] && {
  echo "Error: Search path is required" >&2
  usage
}

[ ! -d "$search_path" ] && {
  echo "Error: '$search_path' is not a valid directory" >&2
  exit 1
}

# Check if GitHub token is available
check_api_token

# Ensure output directory exists
ensure_output_dir

# Construct find command based on arguments
find_cmd="find \"$search_path\" -type"
if [[ "$search_type" == "f" ]]; then
  find_cmd+=" f"
elif [[ "$search_type" == "d" ]]; then
  find_cmd+=" d"
else
  find_cmd+=" f" # Default to files
fi

[ -n "$name_pattern" ] && find_cmd+=" -name \"$name_pattern\""

# Execute find command and collect files
mapfile -t all_files < <(eval "$find_cmd")

# Skip if no files found
if [[ "${#all_files[@]}" -eq 0 ]]; then
  echo "No files found matching criteria."
  exit 0
fi

echo "Found ${#all_files[@]} files/directories matching criteria."

# Process files in batches to maintain context
if [[ "$search_type" == "f" ]]; then
  for ((i = 0; i < ${#all_files[@]}; i += batch_size)); do
    # Get batch of files
    batch=("${all_files[@]:i:batch_size}")
    process_file_batch "${batch[@]}"
  done
elif [[ "$search_type" == "d" ]]; then
  # For directories, just list them
  for dir in "${all_files[@]}"; do
    echo "Directory: $dir (skipping explanation)"
  done
fi

echo "Processing complete. Documentation saved to ${OUTPUT_DIR}"
