#!/bin/bash

SRC_DB_NAME="source_db_name"
DEST_DB_NAME="destination_db_name"

# Mapping of source columns to destination columns
COLUMN_MAPPING=(
	"src_column1:dest_column1"
	"src_column2:dest_column2"
	# Add more mappings as needed
)

# Location to store error log
ERROR_LOG="error.log"

# Tables to copy
TABLES=(
	"table1"
	"table2"
	# Add more tables as needed
)

# Loop through each table
for TABLE in "${TABLES[@]}"; do
	# Clear the error log for each table
	>"$ERROR_LOG"

	# Generate the column mapping for the current table
	MAPPING=""
	for COLUMN_PAIR in "${COLUMN_MAPPING[@]}"; do
		# Split the mapping into source and destination columns
		IFS=":" read -r SRC_COLUMN DEST_COLUMN <<<"$COLUMN_PAIR"
		MAPPING="$MAPPING, $SRC_COLUMN AS $DEST_COLUMN"
	done

	# Remove the leading comma and space
	MAPPING="${MAPPING#, }"

	# MySQL query to select and insert data with column mapping
	QUERY="INSERT INTO $DEST_DB_NAME.$TABLE SELECT $MAPPING FROM $SRC_DB_NAME.$TABLE"

	# Execute the query
	mysql --ogin-path=bnspmsh -e "$QUERY" || echo "Error copying data for table $TABLE" >>"$ERROR_LOG"
done

# Copy the rest of the database to the desired location
# Replace /path/to/backup with the actual backup location
mysqldump --login-path=bnspmsh "$SRC_DB_NAME" >/path/to/backup/"$SRC_DB_NAME"_backup.sql || echo "Error creating database backup" >>"$ERROR_LOG"
