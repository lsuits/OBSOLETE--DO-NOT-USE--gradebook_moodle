<?php

// TODO: custom form with group selector
class quick_edit_select extends quick_edit_screen {
    public function init() {
        global $DB;

        $params = array('courseid' => $this->courseid);

        $filter_items = grade_report_quick_edit::only_items();

        $this->grade_items = array_filter(grade_item::fetch_all($params), $filter_items);

        $this->item = $DB->get_record('course', array('id' => $this->courseid));
    }

    public function html() {
        global $OUTPUT;

        $map = function($item) { return $item->itemname; };

        $grade_options = array_map($map, $this->grade_items);

        $params = array(
            'id' => $this->courseid,
            'item' => 'grade',
            'group' => $this->groupid
        );

        $url = new moodle_url('/grade/report/quick_edit/index.php', $params);

        echo $OUTPUT->single_select($url, 'itemid', $grade_options, $this->itemid);
    }
}
