<?php
include 'functions.php';
$users_max_id = getTargetMaxId('users','id_bigint');
$service_bodies_max_id = getTargetMaxId('service_bodies', 'id_bigint');
$formats_max_id = getTargetMaxId('formats', 'shared_id_bigint');
$meetings_main_max_id = getTargetMaxId('meetings_main', 'id_bigint');

$data = "<?php\n" .
        "include_once 'functions.php';\n" .
        "executeTargetDbQuery(\"DELETE FROM " . $GLOBALS['target_table_prefix'] . "comdef_users where id_bigint > " . $users_max_id . "\");\n" .
        "executeTargetDbQuery(\"DELETE FROM " . $GLOBALS['target_table_prefix'] . "comdef_service_bodies where id_bigint > " . $service_bodies_max_id . "\");\n" .
        "executeTargetDbQuery(\"DELETE FROM " . $GLOBALS['target_table_prefix'] . "comdef_formats where id_bigint > " . $formats_max_id . "\");\n" .
        "executeTargetDbQuery(\"DELETE FROM " . $GLOBALS['target_table_prefix'] . "comdef_meetings_main where id_bigint > " . $meetings_main_max_id . "\");\n" .
        "executeTargetDbQuery(\"DELETE FROM " . $GLOBALS['target_table_prefix'] . "comdef_meetings_data where id_bigint > " . $meetings_main_max_id . "\");\n";

file_put_contents ( 'clean.php', $data);
