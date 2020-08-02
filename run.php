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
                      "'" . $GLOBALS['target_conn']->escape_string($r["name_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["description_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["email_address_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["login_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["password_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["last_access_datetime"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["lang_enum"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["owner_id_bigint"]) . "')";
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
$new_top_level_id = executeTargetScalarValue('SELECT id_bigint FROM ' . $target_table_prefix . 'comdef_' . $table_suffix . ' WHERE sb_owner = 0')->id_bigint;

if ($result->num_rows > 0) {
    // output data of each row
    while($r = $result->fetch_assoc()) {
        $insert_sql = "INSERT INTO " . $target_table_prefix . "comdef_" . $table_suffix . " VALUES (" .
                      ($r["id_bigint"] + $service_bodies_max_id) . "," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["name_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["description_string"]) . "'," .
                      "'" . $r["lang_enum"] . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["worldid_mixed"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["kml_file_uri_string"]) . "'," .
                      ($r["principal_user_bigint"] + $users_max_id) . "," .
                      "'" . getNewUsers($r["editors_string"], $users_max_id) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["uri_string"]) . "'," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["sb_type"]) . "'," .
                      ($r["sb_owner"] > 0 ? $r["sb_owner"] + $service_bodies_max_id : $new_top_level_id) . "," .
                      $r["sb_owner_2"] . "," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["sb_meeting_email"]) . "')";
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
$formats_max_id = getTargetMaxId($table_suffix, 'shared_id_bigint');
$source_formats = getSourceFormats("en");
$target_formats = getTargetFormats("en");

$oldFormatIdToNewFormatId = array();

if (count($source_formats) > 0) {
    foreach ($source_formats as $source_format) {
        if (!anySourceMeetingsUsingFormat($source_format->shared_id_bigint)) {
            echo "Skipping source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "' because it is not in use.\n";
            continue;
        }
        $found_match = false;
        if (array_key_exists($source_format->shared_id_bigint, $oldFormatIdToNewFormatId)) {
            $existingTargetFormatId = $source_formats[$source_format->shared_id_bigint];
            continue;
        }

        // Look for exact matches
        foreach ($target_formats as $target_format) {
            if ($source_format->is_exact_match($target_format)) {
                echo "Found exact format match: source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "' to target format '" . $target_format->key_string . ":" . $target_format->shared_id_bigint . "'.\n";
                $oldFormatIdToNewFormatId[$source_format->shared_id_bigint] = $target_format->shared_id_bigint;
                $found_match = true;
                break;
            }
        }
        if ($found_match) {
            continue;
        }

        // No exact matches, look for partial match
        // If the source format has a format_type_enum, description_string, or worldid_mixed set and the target does not,
        // we update the target with the source's values.
        foreach ($target_formats as $target_format) {
            if ($source_format->is_match($target_format)) {
                /*
                if ($source_format->format_type_enum && !$target_format->format_type_enum) {
                    $target_format->format_type_enum = $source_format->format_type_enum;
                }
                if ($source_format->description_string && !$target_format->description_string) {
                    $target_format->description_string = $source_format->description_string;
                }
                if ($source_format->worldid_mixed && !$target_format->worldid_mixed) {
                    $target_format->worldid_mixed = $source_format->worldid_mixed;
                }
                */
                echo "Found partial format match: source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "' to target format '" . $target_format->key_string . ":" . $target_format->shared_id_bigint . "'.\n";
                $oldFormatIdToNewFormatId[$source_format->shared_id_bigint] = $target_format->shared_id_bigint;
                $found_match = true;
                break;
            }
        }
        if ($found_match)  {
            continue;
        }

        // Format doesn't exist, create a new one
        $new_id = getNextTargetFormatId();
        echo "No match found for source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "', creating target format '" . $source_format->key_string . ":" . $new_id . "'\n";
        $insert_sql = $source_format->getInsertStatement($GLOBALS["target_table_prefix"], $new_id);
        executeTargetDbQuery($insert_sql);
        $oldFormatIdToNewFormatId[$source_format->shared_id_bigint] = strval($new_id);
    }
} else {
    echo "Error: Source database has no formats.";
    die();
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
        $new_formats = array();
        if ($r["formats"]) {
            $old_formats = explode(",", $r["formats"]);
            foreach ($old_formats as $format_id) {
                if (!array_key_exists($format_id, $oldFormatIdToNewFormatId)) {
                    echo "Error: Unknown format found";
                    die();
                }
                $new_format_id = $oldFormatIdToNewFormatId[$format_id];
                array_push($new_formats, $new_format_id);
            }
        }
        $new_formats = implode(",", $new_formats);
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
                      "'" . $GLOBALS['target_conn']->escape_string($r["email_contact"]) . "')";
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
                      (is_null($r["visibility"]) ? 0 : $r["visibility"]) . "," .
                      "'" . $GLOBALS['target_conn']->escape_string($r["data_string"]) . "'," .
                      "NULL,NULL)";
        $insert_result = executeTargetDbQuery($insert_sql);

    }
} else {
    echo "0 results";
}

###meetings data
$table_suffix = 'meetings_longdata';
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
            (is_null($r["visibility"]) ? 0 : $r["visibility"]) . "," .
            "'" . $GLOBALS['target_conn']->escape_string($r["data_longtext"]) . "'," .
            "'" . $GLOBALS['target_conn']->escape_string($r["data_blob"]) . "')";
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
    error_log("Too many (" . $server_admin_count->admin_counts . ") server admin accounts.  You should probably rename the newer one and make it the service body administrator (user_level_tinyint = 2): SELECT * FROM " . $target_table_prefix . 'comdef_users WHERE user_level_tinyint = 1;');
} else {
    error_log("Server Admins check is good.");
}
