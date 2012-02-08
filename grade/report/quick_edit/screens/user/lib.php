<?php

class quick_edit_user extends quick_edit_tablelike implements selectable_items {

    private $categories = array();

    var $structure;

    public function description() {
        return get_string('gradeitems', 'grades');;
    }

    public function options() {
        return array_map(function($item) { return $item->get_name(); }, $this->items);
    }

    public function item_type() {
        return 'grade';
    }

    public function definition() {
        return array(
            'finalgrade', 'feedback', 'override', 'exclude'
        );
    }

    public function init($self_item_is_empty = false) {
        global $DB;

        if (!$self_item_is_empty) {
            $this->user = $DB->get_record('user', array('id' => $this->itemid));
        }

        $params = array('courseid' => $this->courseid);

        $filter_items = grade_report_quick_edit::only_items();

        $this->items= array_filter(grade_item::fetch_all($params), $filter_items);

        $this->structure = new grade_structure();
        $this->structure->modinfo = get_fast_modinfo(
            $DB->get_record('course', array('id' => $this->courseid))
        );
    }

    public function headers() {
        return array(
            '',
            get_string('assessmentname', 'gradereport_quick_edit'),
            get_string('gradecategory', 'grades'),
            get_string('range', 'grades'),
            get_string('grade', 'grades'),
            get_string('feedback', 'grades'),
            $this->make_toggle_links('override'),
            $this->make_toggle_links('exclude')
        );
    }

    public function format_line($item) {
        global $OUTPUT;

        $grade = $this->fetch_grade_or_default($item, $this->user->id);

        $line = array(
            $this->format_icon($item),
            $this->format_link('grade', $item->id, $item->itemname),
            $this->category($item)->get_name(),
            $this->factory()->create('range')->format($item)
        );

        return $this->format_definition($line, $grade);
    }

    private function format_icon($item) {
        $element = array('type' => 'item', 'object' => $item);

        return $this->structure->get_element_icon($element);
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
