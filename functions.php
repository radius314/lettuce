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

function executeTargetPreparedStatement($sql, $params) {
    $stmt = $GLOBALS['target_conn']->prepare($sql);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);
    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        die();
    }
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

function targetHasFormatIdWithLanguage($shared_id_bigint, $lang_enum) {
    $table_name = $GLOBALS["target_table_prefix"] . "comdef_formats";
    $query = "SELECT COUNT(*) as `count` FROM " . $table_name . " WHERE shared_id_bigint = '". $shared_id_bigint . "' AND lang_enum = '" . $lang_enum ."'";
    $result = executeTargetScalarValue($query);
    return (intval($result->count) > 0);
}

function reconcileFormatsForLanguage($lang_enum, $mapping=null) {
    if (is_null($mapping)) {
        $mapping = array();
    }

    $source_formats = getSourceFormats($lang_enum);
    $target_formats = getTargetFormats($lang_enum);

    foreach ($source_formats as $source_format) {
        if (!anySourceMeetingsUsingFormat($source_format->shared_id_bigint)) {
            continue;
        }
        $found_match = false;
        if (array_key_exists($source_format->shared_id_bigint, $mapping)) {
            $existingId = $mapping[$source_format->shared_id_bigint];
            $existingId = $existingId[0]["shared_id_bigint"];
            if (!targetHasFormatIdWithLanguage($existingId, $lang_enum)) {
                $insert_sql = $source_format->getInsertStatement($GLOBALS["target_table_prefix"], $existingId);
                array_push($mapping[$source_format->shared_id_bigint], array("shared_id_bigint" => $existingId, "lang_enum" => $lang_enum, "sql" => $insert_sql));
            }
            continue;
        }

        // Look for exact matches
        foreach ($target_formats as $target_format) {
            if ($source_format->is_exact_match($target_format)) {
                echo "Found exact format match: source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "' to target format '" . $target_format->key_string . ":" . $target_format->shared_id_bigint . "'.\n";
                $mapping[$source_format->shared_id_bigint] = array(array("shared_id_bigint" => $target_format->shared_id_bigint, "lang_enum" => $lang_enum));
                $found_match = true;
                break;
            }
        }
        if ($found_match) {
            continue;
        }

        // No exact matches, look for partial match
        foreach ($target_formats as $target_format) {
            if ($source_format->is_match($target_format)) {
                echo "Found partial format match: source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "' to target format '" . $target_format->key_string . ":" . $target_format->shared_id_bigint . "'.\n";
                $mapping[$source_format->shared_id_bigint] = array(array("shared_id_bigint" => $target_format->shared_id_bigint, "lang_enum" => $lang_enum));
                $found_match = true;
                break;
            }
        }
        if ($found_match)  {
            continue;
        }

        // Format doesn't exist, create a new one
        $max_id = 0;
        foreach ($target_formats as $target_format) {
            if ($target_format->shared_id_bigint > $max_id) {
                $max_id = $target_format->shared_id_bigint;
            }
        }
        $new_id = $max_id + 1;
        echo "No match found for source format '" . $source_format->key_string . ":" . $source_format->shared_id_bigint . "', creating target format '" . $source_format->key_string . ":" . $new_id . "'\n";
        $insert_sql = $source_format->getInsertStatement($GLOBALS["target_table_prefix"], $new_id);
        $mapping[$source_format->shared_id_bigint] = array(array("shared_id_bigint" => strval($new_id), "lang_enum" => $lang_enum, "sql" => $insert_sql));
    }

    return $mapping;
}

function getSourceLanguages() {
    $table_name = $GLOBALS["source_table_prefix"] . "comdef_formats";
    $sql = "SELECT DISTINCT lang_enum FROM " . $table_name;
    $result = executeSourceDbQuery($sql);
    $languages = array();
    while ($r = $result->fetch_assoc()) {
        array_push($languages, $r["lang_enum"]);
    }
    return $languages;
}
function reconcileFormats() {
    $languages = getSourceLanguages();
    unset($languages[array_search("en", $languages)]);
    $mapping = reconcileFormatsForLanguage("en");
    foreach ($languages as $language) {
        $mapping = reconcileFormatsForLanguage($language, $mapping);
    }
    $ret = array();
    foreach ($mapping as $source_id => $targets) {
        $ret[$source_id] = $targets[0]["shared_id_bigint"];
        foreach ($targets as $target) {
            if (array_key_exists("sql", $target)) {
                executeTargetPreparedStatement($target["sql"][0], $target["sql"][1]);
            }
        }
    }
    return $ret;
}
