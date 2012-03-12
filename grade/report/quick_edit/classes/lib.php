<?php

require_once $CFG->dirroot . '/grade/report/quick_edit/classes/uilib.php';

interface selectable_items {
    public function description();

    public function options();

    public function item_type();
}

abstract class quick_edit_screen {
    var $courseid;

    var $itemid;

    var $groupid;

    var $context;

    function __construct($courseid, $itemid, $groupid = null) {
        global $DB;

        $this->courseid = $courseid;
        $this->itemid = $itemid;
        $this->groupid = $groupid;

        $this->context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        $this->course = $DB->get_record('course', array('id' => $courseid));

        $this->init(empty($itemid));
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

    public function fetch_grade_or_default($item, $userid) {
        $grade = grade_grade::fetch(array(
            'itemid' => $item->id, 'userid' => $userid
        ));

        if (!$grade) {
            $default = new stdClass;

            $default->userid = $userid;
            $default->itemid = $item->id;
            $default->feedback = '';

            $grade = new grade_grade($default, false);
        }

        $grade->grade_item = $item;

        return $grade;
    }

    public function make_toggle($key) {
        $attrs = array('href' => '#');

        $all = html_writer::tag('a', get_string('all'), $attrs + array(
            'class' => 'include all ' . $key
        ));

        $none = html_writer::tag('a', get_string('none'), $attrs + array(
            'class' => 'include none ' . $key
        ));

        return html_writer::tag('span', "$all / $none", array(
            'class' => 'inclusion_links'
        ));
    }

    public function make_toggle_links($key) {
        return get_string($key, 'gradereport_quick_edit') . ' ' .
            $this->make_toggle($key);
    }

    public function heading() {
        return get_string('pluginname', 'gradereport_quick_edit');
    }

    public abstract function init($self_item_is_empty = false);

    public abstract function html();

    public function js() {
        global $PAGE;

        $module = array(
            'name' => 'gradereport_quick_edit',
            'fullpath' => '/grade/report/quick_edit/js/quick_edit.js',
            'requires' => array('base', 'dom', 'event', 'event-simulate', 'io-base')
        );

        $PAGE->requires->js_init_call('M.gradereport_quick_edit.init', array(), false, $module);
    }

    public function factory() {
        if (empty($this->__factory)) {
            $this->__factory = new quick_edit_grade_ui_factory();
        }

        return $this->__factory;
    }

    public function process($data) {
        $warnings = array();

        foreach ($data as $varname => $throw) {
            if (preg_match("/(\w+)_(\d+)_(\d+)/", $varname, $matches)) {
                $itemid = $matches[2];
                $userid = $matches[3];
            } else {
                continue;
            }

            $fields = $this->definition();

            if (!in_array($matches[1], $fields)) {
                continue;
            }

            $grade_item = grade_item::fetch(array(
                'id' => $itemid, 'courseid' => $this->courseid
            ));

            if (!$grade_item) {
                continue;
            }

            $grade = $this->fetch_grade_or_default($grade_item, $userid);

            $element = $this->factory()->create($matches[1])->format($grade);

            $name = $element->get_name();
            $oldname = "old$name";

            $posted = $data->$name;
            $oldvalue = $data->$oldname;

            $format = $element->determine_format();

            if ($format->is_textbox() and trim($data->$name) === '') {
                $data->$name = null;
            }

            // Same value; skip
            if ($oldvalue == $posted) {
                continue;
            }

            $msg = $element->set($posted);

            // Optional type
            if (!empty($msg)) {
                $warnings[] = $msg;
            }
        }

        return $warnings;
    }

    public function definition() {
        return array();
    }

    public function display_group_selector() {
        return true;
    }
}

abstract class quick_edit_tablelike extends quick_edit_screen implements tabbable {
    var $items;

    public abstract function headers();

    public abstract function format_line($item);

    public function get_tabindex() {
        return (count($this->definition()) * $this->total) + $this->index;
    }

    // Special injection for bulk operations
    public function process($data) {
        $bulk = $this->factory()->create('bulk_insert')->format($this->item);

        // Bulk insert messages the data to be passed in
        // ie: for all grades of empty grades apply the specified value
        if ($bulk->is_applied($data)) {
            $filter = $bulk->get_type($data);
            $insert_value = $bulk->get_insert_value($data);

            foreach ($data as $varname => $value) {
                if (!preg_match('/^finalgrade_/', $varname)) {
                    continue;
                }

                $empties = ($filter == 'blanks' and trim($value) === '');

                if ($filter == 'all' or $empties) {
                    $data->$varname = $insert_value;
                }
            }
        }

        parent::process($data);
    }

    public function format_definition($line, $grade) {
        foreach ($this->definition() as $i => $field) {
            // Table tab index
            $tab = ($i * $this->total) + $this->index;

            $html = $this->factory()->create($field)->format($grade, $tab);

            if ($field == 'finalgrade') {
                $html .= $this->structure->get_grade_analysis_icon($grade);
            }

            $line[] = $html;
        }

        return $line;
    }

    public function html() {
        $table = new html_table();

        $table->head = $this->headers();

        $table->data = array();

        // To be used for extra formatting
        $this->index = 0;
        $this->total = count($this->items);

        foreach ($this->items as $item) {
            $this->index ++;
            $table->data[] = $this->format_line($item);
        }

        $button_attr = array('class' => 'quick_edit_buttons');
        $button_html = implode(' ', $this->buttons());

        $buttons = html_writer::tag('div', $button_html, $button_attr);

        return html_writer::tag('form',
            html_writer::table($table) . $this->bulk_insert() . $buttons,
            array('method' => 'POST')
        );
    }

    public function bulk_insert() {
        return html_writer::tag(
            'div',
            $this->factory()->create('bulk_insert')->format($this->item)->html(),
            array('quick_edit_bulk')
        );
    }

    public function buttons() {
        $save = html_writer::empty_tag('input', array(
            'type' => 'submit',
            'value' => get_string('update'),
            'tabindex' => $this->get_tabindex()
        ));

        return array($save);
    }
}
