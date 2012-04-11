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

class quick_edit_adjust_value_attribute extends quick_edit_text_attribute {
    function __construct($type_name, $value_name, $adjust_value, $is_disabled = false, $tabindex = null) {
        $this->type_name = $type_name;
        $this->adjust_value = $adjust_value;
        $abs = preg_replace('/^\-/', '', $adjust_value);
        parent::__construct($value_name, $abs, $is_disabled, $tabindex);
    }

    function html() {
        $dropdown = new quick_edit_dropdown_attribute(
            $this->type_name,
            array(-1 => '-', 1 => '+'),
            $this->adjust_value < 0 ? -1 : 1,
            $this->is_disabled,
            $this->tabindex
        );

        $text = new quick_edit_text_attribute(
            $this->name,
            $this->value,
            $this->is_disabled,
            $this->tabindex
        );

        return sprintf("%s %s", $dropdown->html(), $text->html());
    }
}

class anonymous_quick_edit_adjust_value extends quick_edit_finalgrade_ui {
    var $name = 'adjust_value';

    public function is_disabled() {
        $boundary = $this->grade->adjust_boundary();
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
        return new quick_edit_adjust_value_attribute(
            $this->adjust_type_name(),
            $this->get_name(),
            $this->get_value(),
            $this->is_disabled(),
            $this->get_tabindex()
        );
    }

    public function set($value) {
        global $DB;

        $current_value = $this->get_value();
        $submitted_value = $value * required_param($this->adjust_type_name(), PARAM_INT);

        $bounded = $this->grade->bound_adjust_value($submitted_value);

        $code = '';
        if ($bounded < $submitted_value) {
            $code = 'anonymousmorethanmax';
        } else if($bounded > $submitted_value) {
            $code = 'anonymouslessthanmin';
        }

        // Diff checker will fail on screen
        if ($code) {
            $params = array('id' => $this->grade->userid);
            $user = $DB->get_record('user', $params, 'id, firstname, lastname');

            $obj = new stdClass;
            $obj->username = fullname($user);
            $obj->itemname = $this->grade->load_item()->get_name();
            $obj->boundary = $this->grade->adjust_boundary();
            $code = get_string($code, 'grades', $obj) . ' ';
        }

        if ($current_value != $bounded) {
            $moodle_grade_item = $this->grade->grade_item;
            $this->grade->grade_item = $this->grade->load_item();
            $code .= parent::set($bounded);
            $this->grade->grade_item = $moodle_grade_item;
        }

        return $code;
    }
}
