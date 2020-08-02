<?php
class Format {
    public $shared_id_bigint;
    public $key_string;
    public $worldid_mixed;
    public $lang_enum;
    public $name_string;
    public $description_string;
    public $format_type_enum;

    public function __construct($shared_id_bigint, $key_string, $worldid_mixed, $lang_enum, $name_string, $description_string, $format_type_enum) {
        $this->shared_id_bigint = $shared_id_bigint;
        $this->key_string = $key_string;
        $this->worldid_mixed = $worldid_mixed;
        $this->lang_enum = $lang_enum;
        $this->name_string = $name_string;
        $this->description_string = $description_string;
        $this->format_type_enum = $format_type_enum;
    }

    public function is_exact_match($other) {
        if ($this->key_string != $other->key_string) {
            return false;
        }
        if ($this->name_string != $other->name_string) {
            return false;
        }
        if ($this->lang_enum != $other->lang_enum) {
            return false;
        }
        if ($this->format_type_enum != $other->format_type_enum) {
            return false;
        }
        if ($this->description_string != $other->description_string) {
            return false;
        }
        if ($this->worldid_mixed != $other->worldid_mixed) {
            return false;
        }
        return true;
    }

    public function is_match($other) {
        if ($this->is_exact_match($other)) {
            return true;
        }
        if (strtoupper($this->key_string) != strtoupper($other->key_string)) {
            return false;
        }
        if (strtoupper($this->name_string) != strtoupper($other->name_string)) {
            return false;
        }
        if ($this->lang_enum != $other->lang_enum) {
            return false;
        }
        return true;
    }

    public function getInsertStatement($table_prefix, $next_id) {
        $table_name = $table_prefix . "comdef_formats";
        $sql  = "INSERT INTO " . $table_name . " ";
        $sql .= "(shared_id_bigint, key_string, worldid_mixed, lang_enum, name_string, description_string, format_type_enum) ";
        $sql .= "VALUES (";
        $sql .= "'" . strval($next_id) . "', ";
        $sql .= "'" . strval($this->key_string) . "', ";
        $sql .= "'" . strval($this->worldid_mixed) . "', ";
        $sql .= "'" . strval($this->lang_enum) . "', ";
        $sql .= "'" . strval($this->name_string) . "', ";
        $sql .= "'" . strval($this->description_string) . "', ";
        $sql .= "'" . strval($this->format_type_enum) . "' ";
        $sql .= ")";
        return $sql;
    }
}
