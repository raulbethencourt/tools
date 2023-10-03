#!/bin/sh

TABLE1="$1"
TABLE2="$2"
DB_NAME="$3"

mysqlCmd=$(mysql --login-path=bnspmsh) <<EOF
-- Disable foreign key checks temporarily to avoid integrity constraints
SET FOREIGN_KEY_CHECKS=0;

-- Get column names from TABLE1
SET @table1_columns = (
    SELECT GROUP_CONCAT(COLUMN_NAME)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '$DB_NAME' AND TABLE_NAME = '$TABLE1'
);

-- Get column names from TABLE2
SET @table2_columns = (
    SELECT GROUP_CONCAT(COLUMN_NAME)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = '$DB_NAME' AND TABLE_NAME = '$TABLE2'
);

-- Loop through TABLE2 columns and add missing columns to TABLE1
SET @table2_columns_list = CONCAT_WS(',', @table2_columns);
SET @sql = CONCAT(
    'ALTER TABLE ', '$TABLE1', ' ADD COLUMN IF NOT EXISTS (', @table2_columns_list, ');'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Merge data from TABLE2 into TABLE1
INSERT INTO $TABLE1 SELECT * FROM $TABLE2;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Exit MySQL
EOF

if $mysqlCmd; then
	echo "Data merged successfully."
	exit 1
else
	echo "Error: Data merge failed."
	exit 1
fi
