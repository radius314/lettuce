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

### users
$table_suffix = 'users';
$users_max_id = getTargetMaxId($table_suffix,'id_bigint');
$result = getAllSourceData($table_suffix);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["id_bigint"] + $users_max_id) . "," .
                      $r["user_level_tinyint"] . "," .
                      "'" . $r["name_string"] . "'," .
                      "'" . $r["description_string"] . "'," .
                      "'" . $r["email_address_string"] . "'," .
                      "'" . $r["login_string"] . "'," .
                      "'" . $r["password_string"] . "'," .
                      "'" . $r["last_access_datetime"] . "'," .
                      "'" . $r["lang_enum"] . "')";
        $insert_result = $target_conn->query($insert_sql);
        error_log($insert_result);
    }
} else {
    echo "0 results";
}

### service bodies
$table_suffix = 'service_bodies';
$service_bodies_max_id = getTargetMaxId($table_suffix, 'id_bigint');
$result = getAllSourceData($table_suffix);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["id_bigint"] + $service_bodies_max_id) . "," .
                      "'" . $r["name_string"] . "'," .
                      "'" . $r["description_string"] . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      "'" . $r["worldid_mixed"] . "'," .
                      "'" . $r["kml_file_uri_string"] . "'," .
                      ($r["principal_user_bigint"] + $users_max_id) . "," .
                      "'" . $r["editors_string"] . "'," .
                      "'" . $r["uri_string"] . "'," .
                      "'" . $r["sb_type"] . "'," .
                      ($r["sb_owner"] + $service_bodies_max_id) . "," .
                      $r["sb_owner_2"] . "," .
                      "'" . $r["sb_meeting_email"] . "')";
        $insert_result = $target_conn->query($insert_sql);
        error_log($insert_result);
    }
} else {
    echo "0 results";
}

### meetings
$table_suffix = 'meetings_main';
$meetings_main_max_id = getTargetMaxId($table_suffix, 'id_bigint');
$result = getAllSourceData($table_suffix);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["id_bigint"] + $meetings_main_max_id) . "," .
                      "'" . $r["worldid_mixed"] . "'," .
                      "NULL," .
                      ($r["service_body_bigint"] + $service_bodies_max_id) . "," .
                      $r["weekday_tinyint"] . "," .
                      "'" . $r["start_time"] . "'," .
                      "'" . $r["duration_time"] . "'," .
                      "'" . $r["formats"] . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      $r["longitude"] . "," .
                      $r["latitude"] . "," .
                      $r["published"] . "," .
                      "'" . $r["email_contact"] . "')";
        $insert_result = $target_conn->query($insert_sql);
        error_log($insert_result);
    }
} else {
    echo "0 results";
}

###meetings data
$table_suffix = 'meetings_data';
$result = getAllSourceData($table_suffix);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["meetingid_bigint"] + $meetings_main_max_id) . "," .
                      "'" . $r["key"] . "'," .
                      "'" . $r["field_prompt"] . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      $r["visibility"] . "," .
                      "'" . $r["data_string"] . "'," .
                      "NULL,NULL)";
        $insert_result = $target_conn->query($insert_sql);
        error_log($insert_result);
    }
} else {
    echo "0 results";
}

$source_conn->close();
$target_conn->close();


function getTargetMaxId($table_suffix, $id_field) {
    $max_id = 0;
    $result = $GLOBALS['target_conn']->query("SELECT " . $id_field . " FROM " . $GLOBALS['target_table_prefix']
                                             . "comdef_" . $table_suffix . " ORDER BY " . $id_field . " DESC LIMIT 1");
    while($row = $result->fetch_assoc()) {
        $max_id = $row[$id_field];
    }

    echo "max big int: " . $max_id . "\n\n";
    return $max_id;
}

function getAllSourceData($table_suffix) {
    return $GLOBALS['source_conn']->query("SELECT * FROM " . $GLOBALS['source_table_prefix'] . "comdef_" . $table_suffix);
}

function createMergeTable($table_suffix) {
    $GLOBALS['source_conn']->query("CREATE TABLE lettuce_merge_" . $table_suffix . " (`old_id bigint`, `new_id bigint`)");
}

function mergeTableInsert($table_suffix, $old_id, $new_id) {
    $merge_insert = "INSERT INTO lettuce_merge_" . $table_suffix . "(" . $old_id . "," . $new_id . ")";
    $GLOBALS['source_conn']->query($merge_insert);
}