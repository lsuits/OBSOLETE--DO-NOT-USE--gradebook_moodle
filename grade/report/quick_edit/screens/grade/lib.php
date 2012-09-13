<?php

class quick_edit_grade extends quick_edit_tablelike
    implements selectable_items, item_filtering {

    private $requires_extra = false;

    private $requires_paging = false;

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

    public function original_definition() {
        $def = array('finalgrade', 'feedback');

        if ($this->requires_extra) {
            $def[] = 'override';
        }

        $def[] = 'exclude';

        return $def;
    }

    public function init($self_item_is_empty = false) {
        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $this->items = get_role_users(
            $roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid
        );

        if ($self_item_is_empty) {
            return;
        }

        // Only page when necessary
        if (count($this->items) > $this->perpage) {
            $this->requires_paging = true;

            $this->all_items = $this->items;

            $this->items = get_role_users(
                $roleids, $this->context, false, '',
                'u.lastname, u.firstname', null, $this->groupid,
                $this->perpage * $this->page, $this->perpage
            );
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

        $this->setup_structure();

        $this->set_definition($this->original_definition());
        $this->set_headers($this->original_headers());
    }

    public function original_headers() {
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

        $headers[] = $this->make_toggle_links('exclude');

        return $headers;
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->factory()->create('range')->format($this->item);
        }

        return $this->range;
    }

    public function supports_paging() {
        return $this->requires_paging;
    }

    public function pager() {
        global $OUTPUT;

        return $OUTPUT->paging_bar(
            count($this->all_items), $this->page, $this->perpage,
            new moodle_url('/grade/report/quick_edit/index.php', array(
                'perpage' => $this->perpage,
                'id' => $this->courseid,
                'groupid' => $this->groupid,
                'itemid' => $this->itemid,
                'item' => 'grade'
            ))
        );
    }

    public function heading() {
        return $this->item->get_name();
    }
}
