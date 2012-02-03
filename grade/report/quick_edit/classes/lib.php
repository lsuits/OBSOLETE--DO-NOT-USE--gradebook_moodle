<?php

abstract class quick_edit_screen {
    var $courseid;

    var $itemid;

    var $groupid;

    var $context;

    function __construct($courseid, $itemid, $groupid = null) {
        $this->courseid = $courseid;
        $this->itemid = $itemid;
        $this->groupid = $groupid;

        $this->context = get_context_instance(CONTEXT_COURSE, $this->courseid);

        $this->init();
    }

    public function format_range($item) {
        $decimals = $item->get_decimals();

        $min = format_float($item->grademin, $decimals);
        $max = format_float($item->grademax, $decimals);

        return "$min - $max";
    }

    public function format_grade($grade, $decimals) {
        $name = 'grade_' . $grade->itemid . '_' . $grade->userid;

        $finalgrade = $grade->finalgrade ?
            format_float($grade->finalgrade, $decimals) :
            '';

        $attributes = array(
            'type' => 'text',
            'name' => $name,
            'value' => $finalgrade
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $name,
            'value' => $finalgrade
        );

        return (
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }

    public function format_feedback($grade) {
        $name = 'feedback_' . $grade->itemid . '_' . $grade->userid;

        $feedback = $grade->feedback ?
            format_text($grade->feedback, $grade->feedbackformat) :
            '';

        $attributes = array(
            'type' => 'text',
            'name' => $name,
            'value' => $feedback
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $name,
            'value' => $feedback
        );

        return (
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
    }

    public abstract function heading();

    public abstract function init();

    public abstract function html();
}
