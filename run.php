<?php
$source_mysql_server = "127.0.0.1";
$source_mysql_username = "root";
$source_mysql_password = "bmlt_root_password";
$source_mysql_db = "source";
$source_table_prefix = "na_";

$target_mysql_server = "127.0.0.1";
$target_mysql_username = "root";
$target_mysql_password = "bmlt_root_password";
$target_mysql_db = "bmlt";
$target_table_prefix = "na_";

$source_conn = new mysqli($source_mysql_server, $source_mysql_username, $source_mysql_password, $source_mysql_db);
if ($source_conn->connect_error) {
    die("Source connection failed: " . $source_conn->connect_error);
} 
echo "Source database connection established.\n";

$target_conn = new mysqli($target_mysql_server, $target_mysql_username, $target_mysql_password, $target_mysql_db);
if ($target_conn->connect_error) {
    die("Target connection failed: " . $target_conn->connect_error);
}
echo "Target database connection established.\n";

### service bodies
$max_service_body_bigint = 0;
$sql = "SELECT id_bigint FROM " . $target_table_prefix . "comdef_service_bodies ORDER BY id_bigint DESC LIMIT 1";
$result = $target_conn->query($sql);
while($row = $result->fetch_assoc()) {
    $max_service_body_bigint = $row["id_bigint"];
}

echo "max big int: " . $max_service_body_bigint . "\n\n";

$sql = "SELECT * FROM " . $source_table_prefix . "comdef_service_bodies";
$result = $source_conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_service_bodies VALUES (" .
                      ($r["id_bigint"] + $max_service_body_bigint) . "," .
                      "'" . $r["name_string"] . "'," .
                      "'" . $r["description_string"] . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      "'" . $r["worldid_mixed"] . "'," .
                      "'" . $r["kml_file_uri_string"] . "'," .
                      $r["principal_user_bigint"] . "," .
                      "'" . $r["editors_string"] . "'," .
                      "'" . $r["uri_string"] . "'," .
                      "'" . $r["sb_type"] . "'," .
                      ($r["sb_owner"] + $max_service_body_bigint) . "," .
                      $r["sb_owner_2"] . "," .
                      "'" . $r["sb_meeting_email"] . "')";
        $insert_result = $target_conn->query($insert_sql);
        error_log($insert_result);
    }
} else {
    echo "0 results";
}
$source_conn->close();
$target_conn->close();