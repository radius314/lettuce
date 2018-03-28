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

function closeConnections() {
    $GLOBALS['source_conn']->close();
    echo 'Source database connection closed.';
    $GLOBALS['target_conn']->close();
    echo 'Target database connection closed.';
}

function getTargetMaxId($table_suffix, $id_field) {
    $max_id = 0;
    $result = executeTargetDbQuery("SELECT " . $id_field . " FROM " . $GLOBALS['target_table_prefix']
                                             . "comdef_" . $table_suffix . " ORDER BY " . $id_field . " DESC LIMIT 1");
    while($row = $result->fetch_assoc()) {
        $max_id = $row[$id_field];
    }

    echo "max big int: " . $max_id . "\n\n";
    return $max_id;
}

function getAllSourceData($table_suffix) {
    return executeSourceDbQuery("SELECT * FROM " . $GLOBALS['source_table_prefix'] . "comdef_" . $table_suffix);
}

function getAllTargetData($table_suffix) {
    return executeTargetDbQuery("SELECT * FROM " . $GLOBALS['target_table_prefix'] . "comdef_" . $table_suffix);
}

function resetMergeTable($table_suffix) {
    $delete_table_sql = "DROP TABLE lettuce_merge_" . $table_suffix;
    executeSourceDbQuery($delete_table_sql);
}

function createMergeTable($table_suffix) {
    $create_table_sql = "CREATE TABLE lettuce_merge_" . $table_suffix . " (old_id bigint, new_id bigint, notes varchar(255))";
    executeSourceDbQuery($create_table_sql);
}

function insertIntoMergeTable($table_suffix, $old_id, $new_id, $notes) {
    $merge_insert = "INSERT INTO lettuce_merge_" . $table_suffix . " VALUES (" . $old_id . "," . $new_id . ",'". $notes . "')";
    executeSourceDbQuery($merge_insert);
}

function executeSourceDbQuery($query) {
    return $GLOBALS['source_conn']->query($query);
}

function executeTargetDbQuery($query) {
    return $GLOBALS['target_conn']->query($query);
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
        $response = executeSourceDbQuery($format_lookup_sql);
        if ($response->num_rows > 0) {
            // output data of each row
            while ( $r = $response->fetch_assoc() ) {
                array_push($new_formats_array, $r["new_id"]);
            }
        }
    }

    return implode(",", $new_formats_array);
}

function getNewUsers($users, $users_max_id) {
    if (strlen($users) == 0) {
        return "";
    }
    $users_array = explode(",", $users);
    $new_users_array = array();
    foreach ($users_array as $users_item) {
        array_push($new_users_array, $users_item + $users_max_id);
    }

    return implode(",", $new_users_array);
}
