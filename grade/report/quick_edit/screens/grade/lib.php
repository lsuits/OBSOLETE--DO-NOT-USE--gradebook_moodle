<?php

class quick_edit_grade extends quick_edit_screen {

    public function init() {
        $add = $this->itemid ?
            array('id' => $this->itemid) :
            array('itemtype' => 'manual');

        $params = array('courseid' => $this->courseid) + $add;

        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $this->item = grade_item::fetch($params);

        $this->users = get_role_users($roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid);
    }

    public function html() {
        $table = new html_table();

        $table->head = $this->headers();

        $table->data = array();

        foreach ($this->users as $user) {
            $table->data[] = $this->format_line($user);
        }

        return html_writer::table($table);
    }

    public function headers() {
        return array(
            '',
            get_string('firstname') . ' / ' . get_string('lastname'),
            get_string('range', 'grades'),
            get_string('grade', 'grades'),
            get_string('feedback', 'grades')
        );
    }

    public function format_line($user) {
        global $OUTPUT;

        $grade = grade_grade::fetch(array(
            'itemid' => $this->item->id, 'userid' => $user->id
        ));

        $fullname = fullname($user);

        $user->imagealt = $fullname;

        return array(
            $OUTPUT->user_picture($user),
            $fullname,
            $this->item_range(),
            $this->format_grade($grade, $this->item->get_decimals()),
            $this->format_feedback($grade)
        );
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->format_range($this->item);
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->itemname;
    }
}
