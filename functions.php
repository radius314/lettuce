<?php
include('format.php');
$source_mysql_server = "127.0.0.1";
$source_mysql_username = "root";
$source_mysql_password = "bmlt_root_password";
$source_mysql_db = "source";
$source_mysql_port = 3306;
$source_table_prefix = "na_";

$target_mysql_server = "127.0.0.1";
$target_mysql_username = "root";
$target_mysql_password = "bmlt_root_password";
$target_mysql_db = "target";
$target_mysql_port = 3306;
$target_table_prefix = "sezf1_";

$source_conn = new mysqli($source_mysql_server, $source_mysql_username, $source_mysql_password, $source_mysql_db, $source_mysql_port);
if ($source_conn->connect_error) {
    die("Source connection failed: " . $source_conn->connect_error);
}
echo "Source database connection established.\n";

$target_conn = new mysqli($target_mysql_server, $target_mysql_username, $target_mysql_password, $target_mysql_db, $target_mysql_port);
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

function getTargetMaxId($table_suffix, $id_field, $print=true) {
    $max_id = 0;
    $result = executeTargetDbQuery("SELECT " . $id_field . " FROM " . $GLOBALS['target_table_prefix']
                                             . "comdef_" . $table_suffix . " ORDER BY " . $id_field . " DESC LIMIT 1");
    while($row = $result->fetch_assoc()) {
        $max_id = $row[$id_field];
    }

    if ($print) {
        echo $table_suffix . " max big int: " . $max_id . "\n\n";
    }
    return $max_id;
}

function getAllSourceData($table_suffix) {
    return executeSourceDbQuery("SELECT * FROM " . $GLOBALS['source_table_prefix'] . "comdef_" . $table_suffix);
}

function executeSourceScalarValue($query) {
    $result = executeSourceDbQuery($query);
    if ($result !== false) {
        return mysqli_fetch_object($result);
    }

    return null;
}

function getSourceTableCounts($table_suffix) {
    return intval(executeSourceScalarValue('SELECT count(*) AS count_size FROM ' . $GLOBALS['source_table_prefix'] . 'comdef_' . $table_suffix)->count_size);
}

function getTargetTableCounts($table_suffix) {
    return intval(executeTargetScalarValue('SELECT count(*) AS count_size FROM ' . $GLOBALS['target_table_prefix'] . 'comdef_' . $table_suffix)->count_size);
}

function executeTargetScalarValue($query) {
    $result = executeTargetDbQuery($query);
    if ($result !== false) {
         return mysqli_fetch_object($result);
    }

    return null;
}

function executeSourceDbQuery($query) {
    $result = $GLOBALS['source_conn']->query($query);
    if (!$result) {
        error_log("Failed row: " . $query);
    }
    return $result;
}

function executeTargetDbQuery($query) {
    $result = $GLOBALS['target_conn']->query($query);
    if (!$result) {
        error_log("Failed row: " . $query);
    }
    return $result;
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

function getFormats($conn, $table_name, $lang_enum) {
    $query  = "SELECT ";
    $query .= "shared_id_bigint, key_string, worldid_mixed, lang_enum, name_string, description_string, format_type_enum ";
    $query .= "FROM " . $table_name . " ";
    $query .= "WHERE lang_enum = '" . $lang_enum . "'";
    $result = $conn->query($query);
    if (!$result) {
        error_log("Failed row: " . $query);
    }
    $formats = array();
    while ($r = $result->fetch_assoc()) {
        $format = new Format(
            $r["shared_id_bigint"],
            $r["key_string"],
            $r["worldid_mixed"],
            $r["lang_enum"],
            $r["name_string"],
            $r["description_string"],
            $r["format_type_enum"]
        );
        array_push($formats, $format);
    }
    return $formats;
}

function getSourceFormats($lang_enum) {
    return getFormats($GLOBALS['source_conn'], $GLOBALS['source_table_prefix'] . "comdef_formats", $lang_enum);
}

function getTargetFormats($lang_enum) {
    return getFormats($GLOBALS['target_conn'], $GLOBALS['target_table_prefix'] . "comdef_formats", $lang_enum);
}

function getNextTargetFormatId() {
    $id = getTargetMaxId("formats", "shared_id_bigint", false);
    return $id + 1;
}

function anySourceMeetingsUsingFormat($id) {
    $table_name = $GLOBALS["source_table_prefix"] . "comdef_meetings_main";
    $query  = "SELECT COUNT(*) as `count` FROM " . $table_name . " ";
    $query .= "WHERE ";
    $query .= "formats = '" . $id . "' ";
    $query .= "OR ";
    $query .= "formats LIKE '" . $id . ",%' ";
    $query .= "OR ";
    $query .= "formats LIKE '%," . $id . "' ";
    $query .= "OR ";
    $query .= "formats LIKE '%," . $id . ",%'";
    $result = executeSourceScalarValue($query);
    return (intval($result->count) > 0);
}