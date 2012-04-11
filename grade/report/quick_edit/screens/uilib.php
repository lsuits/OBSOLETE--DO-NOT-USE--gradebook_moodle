<?php

class anonymous_quick_edit_finalgrade extends quick_edit_finalgrade_ui {
    function is_disabled() {
        return $this->grade->load_item()->is_completed() ? true :
            parent::is_disabled();
    }

    function set($value) {
        $msg = parent::set($value);

        // Mask student
        if (!empty($msg) and !$this->grade->load_item()->is_completed()) {
            global $DB;
            $user = $DB->get_record('user', array('id' => $grade->userid), 'id, firstname, lastname');

            $msg = preg_replace('/' . fullname($user) . '/', $grade->anonymous_number(), $msg);
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
        $current_value = $this->get_value();
        $submitted_value = $value * required_param($this->adjust_type_name(), PARAM_INT);

        if ($current_value != $submitted_value) {
            return parent::set($submitted_value);
        }

        return '';
    }
}
