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

    function format($what) {
        return new $this->class($what);
    }
}

abstract class quick_edit_ui_element {
    var $name;
    var $value;

    function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
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

    function __construct($name, $value, $is_disabled = false) {
        $this->is_disabled = $is_disabled;
        parent::__construct($name, $value);
    }

    function html() {
        $attributes = array(
            'type' => 'text',
            'name' => $this->name,
            'value' => $this->value
        );

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

    function __construct($name, $is_checked = false) {
        $this->is_checked = $is_checked;
        parent::__construct($name, 1);
    }

    function html() {
        $attributes = array(
            'type' => 'checkbox',
            'name' => $this->name,
            'value' => 1
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $this->name
        );

        if ($this->is_checked) {
            $attributes['checked'] = 'CHECKED';
            $hidden['value'] = 1;
        }

        return (
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

abstract class quick_edit_grade_attribute_format extends quick_edit_attribute_format implements unique_name {
    var $name;

    function __construct($grade) {
        $this->grade = $grade;
    }

    function get_name() {
        return "{$this->name}_{$this->grade->itemid}_{$this->grade->userid}";
    }
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
            $this->is_disabled()
        );
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
            $this->is_disabled()
        );
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
