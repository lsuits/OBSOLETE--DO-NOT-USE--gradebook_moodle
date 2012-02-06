<?php

// TODO: custom form with group selector
class quick_edit_select extends quick_edit_screen {
    public function init($self_item_is_empty = false) {
        global $DB;

        $this->item = $DB->get_record('course', array('id' => $this->courseid));
    }

    public function html() {
        global $OUTPUT;

        $html = '';

        $types = grade_report_quick_edit::valid_screens();

        foreach ($types as $type) {
            if ($type == 'select') continue;

            $class = grade_report_quick_edit::classname($type);

            $screen = new $class($this->courseid, null, $this->groupid);

            if (!method_exists($screen, 'options')) {
                continue;
            }

            $params = array(
                'id' => $this->courseid,
                'item' => $screen->item_type(),
                'group' => $this->groupid
            );

            $url = new moodle_url('/grade/report/quick_edit/index.php', $params);

            $html .= $OUTPUT->heading($screen->description());

            $html .= $OUTPUT->single_select($url, 'itemid', $screen->options());
        }

        return $html;
    }
}
