#!/bin/bash

source_db_name="source_database"
source_table_name="source_table"
dest_db_name="dest_database"

column_mapping=("source_column1:dest_column1" "source_column2:dest_column2")

temp_dir=$(mktemp -d)

mysqldump --login-path=bnspmsh "$source_db_name" "$source_table_name" >"$temp_dir/source_table_dump.sql"

for mapping in "${column_mapping[@]}"; do
	IFS=":" read -r source_column dest_column <<<"$mapping"
	sed -i "s/\`$source_column\`/\`$dest_column\`/g" "$temp_dir/source_table_dump.sql"
done

mysql --login-path=bnspmsh "$dest_db_name" <"$temp_dir/source_table_dump.sql"

rm -rf "$temp_dir"

echo "Data transfer complete."
