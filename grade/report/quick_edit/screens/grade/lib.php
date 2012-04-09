<?php

class quick_edit_grade extends quick_edit_tablelike
    implements selectable_items, item_filtering {

    private $requires_extra;

    var $structure;

    private static $allow_categories;

    public static function allow_categories() {
        if (is_null(self::$allow_categories)) {
            self::$allow_categories = get_config('moodle', 'grade_overridecat');
        }

        return self::$allow_categories;
    }

    public function filter($item) {
        return (
            self::allow_categories() or !(
                $item->is_course_item() or $item->is_category_item()
            )
        );
    }

    public function description() {
        return get_string('users');
    }

    public function options() {
        return array_map(function($user) { return fullname($user); }, $this->items);
    }

    public function item_type() {
        return 'user';
    }

    public function definition() {
        $def = array('finalgrade', 'feedback');

        if ($this->requires_extra) {
            $def[] = 'override';
        }

        return $def;
    }

    public function init($self_item_is_empty = false) {
        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $this->items = get_role_users($roleids, $this->context, false, '',
                'u.lastname, u.firstname', null, $this->groupid);

        if ($self_item_is_empty) {
            return;
        }

        global $DB;

        $params = array(
            'id' => $this->itemid,
            'courseid' => $this->courseid
        );

        $this->item = grade_item::fetch($params);

        $filter_fun = grade_report_quick_edit::filters();

        $allowed = $filter_fun($this->item);

        if (empty($allowed)) {
            print_error('not_allowed', 'gradereport_quick_edit');
        }

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

        $grade = $this->fetch_grade_or_default($this->item, $item->id);

        $fullname = fullname($item);

        $item->imagealt = $fullname;

        $line = array(
            $OUTPUT->user_picture($item),
            $this->format_link('user', $item->id, $fullname),
            $this->item_range()
        );

        return $this->format_definition($line, $grade);
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
        return $this->item->get_name();
    }
}
