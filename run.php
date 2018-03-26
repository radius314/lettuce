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
    }
} else {
    echo "0 results";
}

###formats
$table_suffix = 'formats';
resetMergeTable($table_suffix);
$formats_max_id = getTargetMaxId($table_suffix, 'shared_id_bigint');
$source_formats = getAllSourceData($table_suffix);
$target_formats = getAllTargetData($table_suffix);
createMergeTable($table_suffix);

if ($source_formats->num_rows > 0) {
    // output data of each row
    while ( $r = $source_formats->fetch_assoc() ) {
        $formatId = formatExists($target_formats, $r);
        $notes = "exact match";
        if ($formatId == 0) {
            $notes = "unique format";
            $formatId = $r["shared_id_bigint"] + $formats_max_id;
            $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                          ($r["shared_id_bigint"] + $formats_max_id) . "," .
                          "'" . $r["key_string"] . "'," .
                          "NULL," .
                          "'" . $r["worldid_mixed"] . "'," .
                          "'" . $r["lang_enum"] . "'," .
                          "'" . $r["name_string"] . "'," .
                          "'" . $r["description_string"] . "'," .
                          "'" . $r["format_type_enum"] . "')";
            $insert_result = $target_conn->query($insert_sql);
        }

        if ($r["lang_enum"] == "en") {
            insertIntoMergeTable($table_suffix, $r['shared_id_bigint'], $formatId, $notes);
        }
    }
} else {
    echo "0 results";
}

### meetings
$table_suffix = 'meetings_main';
$meetings_main_max_id = getTargetMaxId($table_suffix, 'id_bigint');
$results = getAllSourceData($table_suffix);

if ($results->num_rows > 0) {
    // output data of each row
    while($r = $results->fetch_assoc()) {
        $new_formats = getNewFormats($r["formats"]);
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["id_bigint"] + $meetings_main_max_id) . "," .
                      "'" . $r["worldid_mixed"] . "'," .
                      "NULL," .
                      ($r["service_body_bigint"] + $service_bodies_max_id) . "," .
                      $r["weekday_tinyint"] . "," .
                      "'" . $r["start_time"] . "'," .
                      "'" . $r["duration_time"] . "'," .
                      "'" . $new_formats . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      $r["longitude"] . "," .
                      $r["latitude"] . "," .
                      $r["published"] . "," .
                      "'" . $r["email_contact"] . "')";
        $insert_result = $target_conn->query($insert_sql);
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

function getAllTargetData($table_suffix) {
    return $GLOBALS['target_conn']->query("SELECT * FROM " . $GLOBALS['target_table_prefix'] . "comdef_" . $table_suffix);
}

function resetMergeTable($table_suffix) {
    $delete_table_sql = "DROP TABLE lettuce_merge_" . $table_suffix;
    $GLOBALS['source_conn']->query($delete_table_sql);
}

function createMergeTable($table_suffix) {
    $create_table_sql = "CREATE TABLE lettuce_merge_" . $table_suffix . " (old_id bigint, new_id bigint, notes varchar(255))";
    $GLOBALS['source_conn']->query($create_table_sql);
}

function insertIntoMergeTable($table_suffix, $old_id, $new_id, $notes) {
    $merge_insert = "INSERT INTO lettuce_merge_" . $table_suffix . " VALUES (" . $old_id . "," . $new_id . ",'". $notes . "')";
    $GLOBALS['source_conn']->query($merge_insert);
}

function formatExists($target_formats, $format_data) {
    $target_formats->data_seek(0);
    if ($target_formats->num_rows > 0) {
        // output data of each row
        while ( $r = $target_formats->fetch_assoc() ) {
            if ($format_data['key_string'] == $r['key_string'] &&
                $format_data['worldid_mixed'] == $r['worldid_mixed'] &&
                $format_data['lang_enum'] == $r['lang_enum'] &&
                $format_data['name_string'] == $r['name_string'] &&
                $format_data['description_string'] == $r['description_string'] &&
                $format_data['format_type_enum'] == $r['format_type_enum']) {
                return $r['shared_id_bigint'];
            }
        }

        return 0;
    }
}

function getNewFormats($formats) {
    if (strlen($formats) == 0) {
        return "";
    }
    $formats_array = explode(",", $formats);
    $new_formats_array = array();
    foreach ($formats_array as $format_item) {
        $format_lookup_sql = "SELECT new_id FROM lettuce_merge_formats WHERE old_id = " . $format_item;
        $response = $GLOBALS['source_conn']->query($format_lookup_sql);
        if ($response->num_rows > 0) {
            // output data of each row
            while ( $r = $response->fetch_assoc() ) {
                array_push($new_formats_array, $r["new_id"]);
            }
        }
    }

    return implode(",", $new_formats_array);
}

