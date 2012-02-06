<?php

class quick_edit_grade extends quick_edit_tablelike {

    private $requires_extra;

    private $structure;

    public function init() {
        global $DB;

        $params = array(
            'id' => $this->itemid,
            'courseid' => $this->courseid
        );

        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $this->item = grade_item::fetch($params);

        $this->items = get_role_users($roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid);

        $this->requires_extra = !$this->item->is_manual_item();

        $this->structure = new grade_structure();
        $this->structure->modinfo = get_fast_modinfo(
            $DB->get_record('course', array('id' => $this->courseid))
        );
    }

    public function headers() {
        $headers = array(
            '',
            get_string('firstname') . ' / ' . get_string('lastname'),
            get_string('range', 'grades'),
            get_string('grade', 'grades'),
            get_string('feedback', 'grades')
        );

        return $this->additional_headers($headers);
    }

    public function format_line($item) {
        global $OUTPUT;

        $grade = $this->fetch_grade_or_default($this->item, $item);

        $fullname = fullname($item);

        $item->imagealt = $fullname;

        $line = array(
            $OUTPUT->user_picture($item),
            $this->format_link('user', $item->id, $fullname),
            $this->item_range(),
            $this->factory()->create('finalgrade')->format($grade) .
            $this->structure->get_grade_analysis_icon($grade),
            $this->factory()->create('feedback')->format($grade)
        );

        return $this->additional_cells($line, $grade);
    }

    public function additional_cells($line, $grade) {
        if ($this->requires_extra) {
            $line[] = $this->factory()->create('override')->format($grade);
        }

        return $line;
    }

    public function additional_headers($headers) {
        if ($this->requires_extra) {
            $headers[] = $this->make_toggle_links('override');
        }

        return $headers;
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->factory()->create('range')->format($this->item);
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->itemname;
    }
}
