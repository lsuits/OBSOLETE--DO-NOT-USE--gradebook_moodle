<?php

class quick_edit_user extends quick_edit_screen {

    private $categories = array();

    public function init() {
        global $DB;

        $this->user = $DB->get_record('user', array('id' => $this->itemid));

        $params = array('courseid' => $this->courseid);

        $filter_items = grade_report_quick_edit::only_items();

        $this->grade_items = array_filter(grade_item::fetch_all($params), $filter_items);
    }

    public function html() {
        $table = new html_table();

        $table->head = $this->headers();

        $table->data = array();

        foreach ($this->grade_items as $item) {
            $table->data[] = $this->format_line($item);
        }

        return html_writer::table($table);
    }

    public function headers() {
        return array(
            '',
            get_string('assessmentname', 'gradereport_quick_edit'),
            get_string('gradecategory', 'grades'),
            get_string('range', 'grades'),
            get_string('grade', 'grades'),
            get_string('feedback', 'grades'),
            get_string('override', 'gradereport_quick_edit'),
            get_string('exclude', 'gradereport_quick_edit')
        );
    }

    public function format_line($item) {
        global $OUTPUT;

        $grade = grade_grade::fetch(array(
            'itemid' => $item->id, 'userid' => $this->user->id
        ));

        return array(
            $OUTPUT->pix_icon('t/' . $item->itemtype . '_item', $item->itemname),
            $this->format_link('grade', $item->id, $item->itemname),
            $this->category($item)->get_name(),
            $this->format_range($item),
            $this->format_grade($grade, $item->get_decimals()),
            $this->format_feedback($grade),
            $this->format_override($item, $grade),
            $this->format_exclude($item, $grade)
        );
    }

    private function category($item) {
        if (!isset($this->categories[$item->categoryid])) {
            $category = $item->get_parent_category();

            $this->categories[$category->id] = $category;
        }

        return $this->categories[$item->categoryid];
    }

    public function heading() {
        return fullname($this->user);
    }
}
