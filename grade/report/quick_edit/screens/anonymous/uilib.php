<?php

class anonymous_ui_factory extends quick_edit_grade_ui_factory {
    public function create($type) {
        $attempt = 'anonymous_quick_edit_' . $type;

        if (class_exists($attempt)) {
            return $this->wrap($attempt);
        } else {
            return parent::create($type);
        }
    }
}

class anonymous_quick_edit_finalgrade extends quick_edit_finalgrade_ui {
    function determine_format() {
        if ($this->grade->load_item()->is_completed()) {
            return new quick_edit_empty_element($this->get_value());
        } else {
            return parent::determine_format();
        }
    }

    function set($value) {
        // Swap grade_items
        $moodle_grade_item = $this->grade->load_grade_item();

        $this->grade->grade_item = $this->grade->load_item();

        $msg = parent::set($value);

        $this->grade->grade_item = $moodle_grade_item;

        // Mask student
        if (!empty($msg) and !$this->grade->load_item()->is_completed()) {
            global $DB;

            $params = array('id' => $this->grade->userid);
            $user = $DB->get_record('user', $params, 'id, firstname, lastname');

            $number = $this->grade->anonymous_number();
            $msg = preg_replace('/' . fullname($user) . '/', $number, $msg);
        }

        return $msg;
    }
}

class anonymous_quick_edit_adjust_value extends quick_edit_finalgrade_ui {
    var $name = 'adjust_value';

    public function is_disabled() {
        $boundary = $this->grade->load_item()->adjust_boundary();
        return empty($boundary) ? true : parent::is_disabled();
    }

    public function adjust_type_name() {
        return "adjust_type_{$this->grade->itemid}_{$this->grade->userid}";
    }

    public function get_value() {
        return format_float(
            $this->grade->adjust_value,
            $this->grade->grade_item->get_decimals()
        );
    }

    public function determine_format() {
        return new quick_edit_text_attribute(
            $this->get_name(),
            $this->get_value(),
            $this->is_disabled(),
            $this->get_tabindex()
        );
    }

    public function set($value) {
        global $DB;

        $bounded = $this->grade->bound_adjust_value($value);

        $code = '';
        if ($bounded < $value) {
            $code = 'anonymousmorethanmax';
        } else if($bounded > $value) {
            $code = 'anonymouslessthanmin';
        }

        // Diff checker will fail on screen
        if ($code) {
            $params = array('id' => $this->grade->userid);
            $user = $DB->get_record('user', $params, 'id, firstname, lastname');

            $obj = new stdClass;
            $obj->username = fullname($user);
            $obj->itemname = $this->grade->load_item()->get_name();
            $obj->boundary = $this->grade->load_item()->adjust_boundary();
            $code = get_string($code, 'grades', $obj) . ' ';
        }

        $this->grade->load_item()->update_final_grade(
            $this->grade->userid, $bounded, 'quick_edit'
        );

        return $code;
    }
}
