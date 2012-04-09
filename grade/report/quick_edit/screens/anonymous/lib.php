<?php

class quick_edit_anonymous extends quick_edit_tablelike
    implements selectable_items, item_filtering {

    private static $supported;

    public static function is_supported() {
        global $COURSE;

        if (is_null(self::$supported)) {
            self::$supported = grade_anonymous::is_supported($COURSE);
        }

        return self::$supported;
    }

    public function description() {
        return get_string('anonymousitem', 'grades');
    }

    public function options() {
        if (!self::is_supported()) {
            return array();
        }

        global $DB;

        $sql = 'SELECT gi.id, gi.itemname
            FROM {grade_items} gi, {grade_anon_items} anon
            WHERE gi.id = anon.itemid';

        return $DB->get_records_sql_menu($sql);
    }

    public function item_type() {
        return 'anonymous';
    }

    public function definition() {
        return array('finalgrade');
    }

    public function filter($item) {
        if (!self::is_supported()) {
            return true;
        }

        $anonid = grade_anonymous::fetch(array('itemid' => $item->id));

        return empty($anonid);
    }

    public function init($self_item_is_empty = false) {
        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        $students = get_role_users($roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid);

        $this->items = grade_anonymous::anonymous_users($students);

        if ($self_item_is_empty) {
            return;
        }

        $this->item = grade_anonymous::fetch(array('itemid' => $this->itemid));
    }

    public function headers() {
        return array(
            get_string('anonymous', 'grades'),
            get_string('range', 'grades')
        );
    }

    public function format_line($user) {
        return array(
            $user->data,
            $this->item_range()
        );
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->factory()
                ->create('range')->format($this->item->load_item());
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->load_item()->get_name();
    }
}
