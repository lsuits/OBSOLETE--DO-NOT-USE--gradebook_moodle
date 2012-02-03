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

    public function format_link($screen, $itemid, $display = null) {
        $url = new moodle_url('/grade/report/quick_edit/index.php', array(
            'id' => $this->courseid,
            'item' => $screen,
            'itemid' => $itemid,
            'group' => $this->groupid
        ));

        if ($display) {
            return html_writer::link($url, $display);
        } else {
            return $url;
        }
    }

    public function format_range($item) {
        $decimals = $item->get_decimals();

        $min = format_float($item->grademin, $decimals);
        $max = format_float($item->grademax, $decimals);

        return "$min - $max";
    }

    public function format_override($item, $grade) {
        if ($item->itemtype == 'manual') {
            return get_string('notavailable', 'gradereport_quick_edit');
        }

        return $this->checkbox_attribute($grade, 'override', $grade->is_overridden());
    }

    public function format_exclude($item, $grade) {
        return $this->checkbox_attribute($grade, 'exclude', $grade->is_excluded());
    }

    public function fetch_grade_or_default($item, $user) {
        $grade = grade_grade::fetch(array(
            'itemid' => $item->id, 'userid' => $user->id
        ));

        if (!$grade) {
            $default = new stdClass;

            $default->userid = $user->id;
            $default->itemid = $item->id;
            $default->feedback = '';

            $grade = new grade_grade($default, false);
        }

        return $grade;
    }

    public function make_toggle($key) {
        $attrs = array('href' => '#');

        $all = html_writer::tag('a', get_string('all'), $attrs + array(
            'class' => 'include_all ' . $key
        ));

        $none = html_writer::tag('a', get_string('none'), $attrs + array(
            'class' => 'include_none ' . $key
        ));

        return html_writer::tag('span', "$all / $none", array(
            'class' => 'inclusion_links'
        ));
    }

    public function make_toggle_links($key) {
        return get_string($key, 'gradereport_quick_edit') . ' ' .
            $this->make_toggle($key);
    }

    private function checkbox_attribute($grade, $post_name, $is_checked) {
        $name = $post_name . '_' . $grade->itemid . '_' . $grade->userid;

        $attributes = array(
            'type' => 'checkbox',
            'name' => $name,
            'value' => 1
        );

        $hidden = array(
            'type' => 'hidden',
            'name' => 'old' . $name
        );

        if ($is_checked) {
            $attributes['checked'] = 'CHECKED';
            $hidden['value'] = 1;
        }

        return (
            html_writer::empty_tag('input', $attributes) .
            html_writer::empty_tag('input', $hidden)
        );
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

    public function heading() {
        return get_string('pluginname', 'gradereport_quick_edit');
    }

    public abstract function init();

    public abstract function html();
}
