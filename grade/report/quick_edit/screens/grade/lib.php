<?php

class quick_edit_grade extends quick_edit_screen {

    private $requires_extra;

    public function init() {
        $params = array(
            'id' => $this->itemid,
            'courseid' => $this->courseid
        );

        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $this->item = grade_item::fetch($params);

        $this->users = get_role_users($roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid);

        $this->requires_extra = !$this->item->is_manual_item();
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
        $headers = array(
            '',
            get_string('firstname') . ' / ' . get_string('lastname'),
            get_string('range', 'grades'),
            get_string('grade', 'grades'),
            get_string('feedback', 'grades')
        );

        return $this->additional_headers($headers);
    }

    public function format_line($user) {
        global $OUTPUT;

        $grade = $this->fetch_grade_or_default($this->item, $user);

        $fullname = fullname($user);

        $user->imagealt = $fullname;

        $line = array(
            $OUTPUT->user_picture($user),
            $this->format_link('user', $user->id, $fullname),
            $this->item_range(),
            $this->format_grade($grade),
            $this->format_feedback($grade)
        );

        return $this->additional_cells($line, $grade);
    }

    public function additional_cells($line, $grade) {
        if ($this->requires_extra) {
            $line[] = $this->format_override($grade);
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
            $this->range = $this->format_range($this->item);
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->itemname;
    }
}
