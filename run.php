<?php
include 'functions.php';
include 'clean.php';

### users
$table_suffix = 'users';
$users_max_id = getTargetMaxId($table_suffix,'id_bigint');
$result = getAllSourceData($table_suffix);
$source_count = getSourceTableCounts($table_suffix);
$target_count = getTargetTableCounts($table_suffix);

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
        $insert_result = executeTargetDbQuery($insert_sql);
    }
} else {
    echo "0 results";
}

$final_count = getTargetTableCounts($table_suffix);
if ($final_count == ($source_count + $target_count)) {
    error_log($table_suffix . ' table all imported.');
} else {
    error_log($table_suffix . ' table rows missing (1 missing is ok typically because an admin user might have a duplicate name): ' . strval(($source_count + $target_count) - $final_count));
}

### service bodies
$table_suffix = 'service_bodies';
$service_bodies_max_id = getTargetMaxId($table_suffix, 'id_bigint');
$result = getAllSourceData($table_suffix);
$source_count = getSourceTableCounts($table_suffix);
$target_count = getTargetTableCounts($table_suffix);

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
                      "'" . getNewUsers($r["editors_string"], $users_max_id) . "'," .
                      "'" . $r["uri_string"] . "'," .
                      "'" . $r["sb_type"] . "'," .
                      ($r["sb_owner"] > 0 ? $r["sb_owner"] + $service_bodies_max_id : 0) . "," .
                      $r["sb_owner_2"] . "," .
                      "'" . $r["sb_meeting_email"] . "')";
        $insert_result = executeTargetDbQuery($insert_sql);
    }
} else {
    echo "0 results";
}

$final_count = getTargetTableCounts($table_suffix);
if ($final_count == ($source_count + $target_count)) {
    error_log($table_suffix . ' table all imported.');
} else {
    error_log($table_suffix . ' table rows missing: ' . strval(($source_count + $target_count) - $final_count));
}

###formats
$table_suffix = 'formats';
resetMergeTable($table_suffix);
$formats_max_id = getTargetMaxId($table_suffix, 'shared_id_bigint');
$source_formats = getAllSourceData($table_suffix);
$target_formats = getAllTargetData($table_suffix);
$source_count = getSourceTableCounts($table_suffix);
$target_count = getTargetTableCounts($table_suffix);
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
                          "'" . $GLOBALS['target_conn']->escape_string($r["name_string"]) . "'," .
                          "'" . $GLOBALS['target_conn']->escape_string($r["description_string"]) . "'," .
                          "'" . $r["format_type_enum"] . "')";
            $insert_result = executeTargetDbQuery($insert_sql);
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
$source_count = getSourceTableCounts($table_suffix);
$target_count = getTargetTableCounts($table_suffix);

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
        $insert_result = executeTargetDbQuery($insert_sql);
    }
} else {
    echo "0 results";
}

$final_count = getTargetTableCounts($table_suffix);
if ($final_count == ($source_count + $target_count)) {
    error_log($table_suffix . ' table all imported.');
} else {
    error_log($table_suffix . ' table rows missing: ' . strval(($source_count + $target_count) - $final_count));
}

###meetings data
$table_suffix = 'meetings_data';
#don't insert the data type stubs, will use the ones from the target
$result = getAllSourceData($table_suffix . " WHERE meetingid_bigint <> 0");
$source_count = getSourceTableCounts($table_suffix . " WHERE meetingid_bigint <> 0");
$target_count = getTargetTableCounts($table_suffix);

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["meetingid_bigint"] + $meetings_main_max_id) . "," .
                      "'" . $r["key"] . "'," .
                      "'" . $r["field_prompt"] . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      $r["visibility"] . "," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["data_string"]) . "'," .
                      "NULL,NULL)";
        $insert_result = executeTargetDbQuery($insert_sql);

    }
} else {
    echo "0 results";
}

$final_count = getTargetTableCounts($table_suffix);
if ($final_count == ($source_count + $target_count)) {
    error_log($table_suffix . ' table all imported.');
} else {
    error_log($table_suffix . ' table rows missing: ' . strval(($source_count + $target_count) - $final_count));
}

###data checks
$server_admin_count = executeTargetScalarValue('SELECT count(id_bigint) as admin_counts FROM ' . $target_table_prefix . 'comdef_users WHERE user_level_tinyint = 1;');
if ($server_admin_count->admin_counts > 1) {
    error_log("Too many (" . $server_admin_count->admin_counts . ") server admin accounts.  You should delete one: SELECT * FROM " . $target_table_prefix . 'comdef_users WHERE user_level_tinyint = 1;');
} else {
    error_log("Server Admins check is good.");
}
