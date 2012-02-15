<?php

abstract class quick_edit_ui_factory {
    public abstract function create($type);

    protected function wrap($class) {
        return new quick_edit_factory_class_wrap($class);
    }
}

class quick_edit_grade_ui_factory extends quick_edit_ui_factory {
    public function create($type) {
        return $this->wrap("quick_edit_{$type}_ui");
    }
}

class quick_edit_factory_class_wrap {
    function __construct($class) {
        $this->class = $class;
    }

    function format() {
        $args = func_get_args();

        $reflect = new ReflectionClass($this->class);
        return $reflect->newInstanceArgs($args);
    }
}

abstract class quick_edit_ui_element {
    var $name;
    var $value;

    function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }

    function is_checkbox() {
        return false;
    }

    function is_textbox() {
        return false;
    }

    function is_dropdown() {
        return false;
    }

    abstract function html();
}

class quick_edit_empty_element extends quick_edit_ui_element {
    function __construct($msg = null) {
        if (is_null($msg)) {
            $this->text = get_string('notavailable', 'gradereport_quick_edit');
        } else {
            $this->text = $msg;
        }
    }

    function html() {
        return $this->text;
    }
}

class quick_edit_text_attribute extends quick_edit_ui_element {
    var $is_disabled;
    var $tabindex;

    function __construct($name, $value, $is_disabled = false, $tabindex = null) {
        $this->is_disabled = $is_disabled;
        $this->tabindex = $tabindex;
        parent::__construct($name, $value);
    }

    function is_textbox() {
        return true;
    }

    function html() {
        $attributes = array(
            'type' => 'text',
            'name' => $this->name,
            'value' => $this->value
        );

        if (!empty($this->tabindex)) {
            $attributes['tabindex'] = $this->tabindex;
        }

        if ($this->is_disabled) {
            $attributes['disabled'] = 'DISABLED';
        }

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name,
            'value' => $this->value
        );

        return (
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }
}

class quick_edit_checkbox_attribute extends quick_edit_ui_element {
    var $is_checked;
    var $tabindex;

    function __construct($name, $is_checked = false, $tabindex = null) {
        $this->is_checked = $is_checked;
        $this->tabindex = $tabindex;
        parent::__construct($name, 1);
    }

    function is_checkbox() {
        return true;
    }

    function html() {
        $attributes = array(
            'type' => 'checkbox',
            'name' => $this->name,
            'value' => 1
        );

        $alt = array(
            'type' => 'hidden',
            'name' => $this->name,
            'value' => 0
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name
        );

        if (!empty($this->tabindex)) {
            $attributes['tabindex'] = $this->tabindex;
        }

        if ($this->is_checked) {
            $attributes['checked'] = 'CHECKED';
            $hidden['value'] = 1;
        }

        return (
            html_writer::empty_tag('input', $alt) .
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }
}

abstract class quick_edit_attribute_format {
    abstract function determine_format();

    function __toString() {
        return $this->determine_format()->html();
    }
}

abstract class quick_edit_grade_attribute_format extends quick_edit_attribute_format implements unique_name, tabbable {
    var $name;

    function __construct() {
        $args = func_get_args();

        $this->get_arg_or_nothing($args, 0, 'grade');
        $this->get_arg_or_nothing($args, 1, 'tabindex');
    }

    function get_name() {
        return "{$this->name}_{$this->grade->itemid}_{$this->grade->userid}";
    }

    function get_tabindex() {
        return isset($this->tabindex) ? $this->tabindex : null;
    }

    private function get_arg_or_nothing($args, $index, $field) {
        if (isset($args[$index])) {
            $this->$field = $args[$index];
        }
    }

    public abstract function set($value);
}

interface unique_name {
    function get_name();
}

interface unique_value {
    function get_value();
}

interface be_disabled {
    function is_disabled();
}

interface be_checked {
    function is_checked();
}

interface tabbable {
    function get_tabindex();
}

class quick_edit_finalgrade_ui extends quick_edit_grade_attribute_format implements unique_value, be_disabled {

    var $name = 'finalgrade';

    function get_value() {
        return $this->grade->finalgrade ?
            format_float($this->grade->finalgrade, $this->grade->grade_item->get_decimals()) :
            '';
    }

    function is_disabled() {
        return (
            $this->grade->grade_item->is_overridable_item() and
            !$this->grade->is_overridden()
        );
    }

    function determine_format() {
        return new quick_edit_text_attribute(
            $this->get_name(),
            $this->get_value(),
            $this->is_disabled(),
            $this->get_tabindex()
        );
    }

    function set($value) {
        global $DB;

        $userid = $this->grade->userid;
        $grade_item = $this->grade->grade_item;

        $feedback = false;
        $feedbackformat = false;
        if ($grade_item->gradetype == GRADE_TYPE_SCALE) {
            if ($value == -1) {
                $finalgrade = null;
            } else {
                $finalgrade = $value;
            }
        } else {
            $finalgrade = unformat_float($value);
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
            $gradestr->itemname = $this->grade->grade_item->get_name();

            $errorstr = get_string($errorstr, 'grades', $gradestr);
        }

        $grade_item->update_final_grade($userid, $finalgrade, 'quick_edit', $feedback, FORMAT_MOODLE);
        return $errorstr;
    }
}

class quick_edit_feedback_ui extends quick_edit_grade_attribute_format implements unique_value, be_disabled {

    var $name = 'feedback';

    function get_value() {
        return $this->grade->feedback ?
            format_text($this->grade->feedback, $this->grade->feedbackformat) :
            '';
    }

    function is_disabled() {
        return (
            $this->grade->grade_item->is_overridable_item_feedback() and
            !$this->grade->is_overridden()
        );
    }

    function determine_format() {
        return new quick_edit_text_attribute(
            $this->get_name(),
            $this->get_value(),
            $this->is_disabled(),
            $this->get_tabindex()
        );
    }

    function set($value) {
        $finalgrade = false;
        $trimmed = trim($value);
        if (empty($trimmed)) {
            $feedback = NULL;
        } else {
            $feedback = $value;
        }

        $this->grade->grade_item->update_final_grade(
            $this->grade->userid, $finalgrade, 'quick_edit',
            $feedback, FORMAT_MOODLE
        );
        return false;
    }
}

class quick_edit_override_ui extends quick_edit_grade_attribute_format implements be_checked {
    var $name = 'override';

    function is_checked() {
        return $this->grade->is_overridden();
    }

    function determine_format() {
        if (!$this->grade->grade_item->is_overridable_item()) {
            return new quick_edit_empty_element();
        }

        return new quick_edit_checkbox_attribute(
            $this->get_name(),
            $this->is_checked()
        );
    }

    function set($value) {
        if (empty($this->grade->id)) {
            return false;
        }

        $state = $value == 0 ? false : true;

        $this->grade->set_overridden($state);
        $this->grade->grade_item->get_parent_category()->force_regrading();
        return false;
    }
}

class quick_edit_exclude_ui extends quick_edit_grade_attribute_format implements be_checked {
    var $name = 'exclude';

    function is_checked() {
        return $this->grade->is_excluded();
    }

    function determine_format() {
        return new quick_edit_checkbox_attribute(
            $this->get_name(),
            $this->is_checked()
        );
    }

    function set($value) {
        if (empty($this->grade->id)) {
            if (empty($value)) {
                return false;
            }

            // Fill in arbitrary grade to be excluded
            $this->grade->grade_item->update_final_grade(
                $this->grade->userid, 0, 'quick_edit', null, FORMAT_MOODLE
            );

            $this->grade = grade_grade::fetch($grade_params);
        }

        $state = $value == 0 ? false : true;

        $this->grade->set_excluded($state);

        $this->grade->grade_item->get_parent_category()->force_regrading();
        return false;
    }
}

class quick_edit_range_ui extends quick_edit_attribute_format {
    function __construct($item) {
        $this->item = $item;
    }

    function determine_format() {
        $decimals = $this->item->get_decimals();

        $min = format_float($this->item->grademin, $decimals);
        $max = format_float($this->item->grademax, $decimals);

        return new quick_edit_empty_element("$min - $max");
    }
}
