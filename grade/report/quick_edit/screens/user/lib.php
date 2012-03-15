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

    public function display_group_selector() {
        return false;
    }

    public function init($self_item_is_empty = false) {
        global $DB;

        if (!$self_item_is_empty) {
            $this->item = $DB->get_record('user', array('id' => $this->itemid));
        }

        $params = array('courseid' => $this->courseid);

        $items = grade_item::fetch_all($params);
        uasort($items, function($itema, $itemb) {
            return $itema->sortorder > $itemb->sortorder;
        });

        $allow_cats = (bool) get_config('moodle', 'grade_overridecat');

        if ($allow_cats) {
            $this->items = $items;
        } else {
            $filter_items = grade_report_quick_edit::only_items();

            $this->items = array_filter($items, $filter_items);
        }
        unset($items);

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

        $grade = $this->fetch_grade_or_default($item, $this->item->id);

        $line = array(
            $this->format_icon($item),
            $this->format_link('grade', $item->id, $item->get_name()),
            $this->category($item),
            $this->factory()->create('range')->format($item)
        );

        return $this->format_definition($line, $grade);
    }

    private function format_icon($item) {
        $element = array('type' => 'item', 'object' => $item);

        return $this->structure->get_element_icon($element);
    }

    private function category($item) {
        if (empty($item->categoryid)) {

            if ($item->itemtype == 'course') {
                return $this->course->fullname;
            }

            global $DB;

            $params = array('id' => $item->iteminstance);
            $elem = $DB->get_record('grade_categories', $params);

            return $elem->fullname;
        }

        if (!isset($this->categories[$item->categoryid])) {
            $category = $item->get_parent_category();

            $this->categories[$category->id] = $category;
        }

        return $this->categories[$item->categoryid]->get_name();
    }

    public function heading() {
        return fullname($this->item);
    }
}
