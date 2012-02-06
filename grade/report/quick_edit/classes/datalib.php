<?php

abstract class post_processor {
    function __construct($courseid) {
        $this->courseid = $courseid;
    }

    abstract function handle($data);
}

class quick_edit_grade_processor extends post_processor {
    function get_fields() {
        return array(
            'finalgrade', 'feedback', 'override', 'exclude'
        );
    }

    function handle($data) {
        foreach ($data as $varname => $throw) {
            if (preg_match("/(\w+)_(\d+)_(\d+)/", $varname, $matches)) {
                $itemid = $matches[2];
                $userid = $matches[3];
            } else {
                continue;
            }

            $grade_item = grade_item::fetch(array(
                'id' => $itemid, 'courseid' => $this->courseid
            ));

            if (!$grade_item) {
                continue;
            }

            $fields = $this->get_fields();

            $warnings = array();
            foreach ($fields as $field) {

                $name = "{$field}_{$itemid}_{$userid}";
                $oldname = "old$name";

                // Probably not supported
                if (empty($data->$name) and empty($data->$oldname)) {
                    continue;
                }

                // Probably a checkbox
                if (!isset($data->$name) and isset($data->$oldname)) {
                    $data->$name = 0;
                }

                $posted = $data->$name;
                $oldvalue = $data->$oldname;

                // Same value; skip
                if ($oldvalue == $posted) {
                    continue;
                }

                $func = 'set_' . $field;

                $msg = $this->{$func}($grade_item, $userid, $posted);

                // Optional type
                if (!empty($msg)) {
                    $warnings[] = $msg;
                }
            }
        }

        return $warnings;
    }

    function set_finalgrade($grade_item, $userid, $value) {
        global $DB;

        $feedback = false;
        $feedbackformat = false;
        if ($grade_item->gradetype == GRADE_TYPE_SCALE) {
            if ($posted == -1) {
                $finalgrade = null;
            } else {
                $finalgrade = $posted;
            }
        } else {
            $finalgrade = unformat_float($posted);
        }

        $errorstr = '';
        if (is_null($finalgrade)) {
            // ok
        } else {
            $bounded = $grade_item->bounded_grade($finalgrade);
            if ($bounded > $finalgrade) {
                $errorstr = 'lessthanmin';
            } else if ($bounded < $finalgrade) {
                $errorstr = 'morethanmax';
            }
        }

        if ($errorstr) {
            $user = $DB->get_record('user', array('id' => $userid), 'id, firstname, lastname');
            $gradestr = new stdClass;
            $gradestr->username = fullname($user);
            $gradestr->itemname = $grade_item->get_name();

            return get_string($errorstr, 'grades', $gradestr);
        }

        $grade_item->update_final_grade($userid, $finalgrade, 'quick_edit', $feedback, FORMAT_MOODLE);
        return false;
    }

    function set_feedback($grade_item, $userid, $value) {
        $finalgrade = false;
        $trimmed = trim($value);
        if (empty($trimmed)) {
            $feedback = NULL;
        } else {
            $feedback = $value;
        }

        $grade_item->update_final_grade($userid, $finalgrade, 'quick_edit', $feedback, FORMAT_MOODLE);
    }

    function set_override($grade_item, $userid, $value) {
        $grade = grade_grade::fetch(array(
            'itemid' => $grade_item->id, 'userid' => $userid
        ));

        if (empty($grade)) {
            return false;
        }

        $state = $value == 0 ? false : true;

        return !$grade->set_overridden($state);
    }

    function set_exclude($grade_item, $userid, $value) {
        $grade_params = array(
            'itemid' => $grade_item->id, 'userid' => $userid
        );

        $grade = grade_grade::fetch($grade_params);

        if (empty($grade)) {
            if (empty($value)) {
                return false;
            }

            // Fill in arbitrary grade to be excluded
            $grade_item->update_final_grade($userid, null, 'quick_edit', null, FORMAT_MOODLE);

            $grade = grade_grade::fetch($grade_params);
        }

        $state = $value == 0 ? false : true;

        return !$grade->set_excluded($state);
    }
}
