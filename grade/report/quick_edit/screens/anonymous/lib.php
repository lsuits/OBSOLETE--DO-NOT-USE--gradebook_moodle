<?php

require_once dirname(__FILE__) . '/uilib.php';

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
        $defaults = array('finalgrade');

        if ($this->item->is_completed()) {
            $defaults[] = 'adjust_value';
        }

        return $defaults;
    }

    public function additional_headers($line) {
        if ($this->item->is_completed()) {
            array_unshift($line, '');
            $line[1] = get_string('firstname') . ' / ' . get_string('lastname');
            $line[] = get_string('anonymousadjusts', 'grades');
        }

        return $line;
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

        if ($self_item_is_empty) {
            return;
        }

        $this->item = grade_anonymous::fetch(array('itemid' => $this->itemid));

        $this->students = get_role_users($roleids, $this->context, false, '',
            'u.lastname, u.firstname', null, $this->groupid);

        $this->items = $this->item->is_completed() ?
            $this->students :
            grade_anonymous::anonymous_users($this->students);
    }

    public function headers() {
        return $this->additional_headers(array(
            get_string('anonymous', 'grades'),
            get_string('range', 'grades'),
            get_string('grade', 'grades')
        ));
    }

    public function format_line($user) {
        global $OUTPUT;

        $grade = $this->fetch_grade_or_default($this->item, $user->id);

        if ($this->item->is_completed()) {
            $user->imagealt = fullname($user);
            $line = array(
                $OUTPUT->user_picture($user),
                $this->format_link('user', $user->id, $user->imagealt)
            );
        } else {
            $line = array($user->data);
        }

        $line[] = $this->item_range();

        return $this->format_definition($line, $grade);
    }

    public function item_range() {
        if (empty($this->range)) {
            $this->range = $this->factory()
                ->create('range')
                ->format($this->item->load_item());
        }

        return $this->range;
    }

    public function heading() {
        return $this->item->get_name();
    }

    public function fetch_grade_or_default($item, $userid) {
        return $this->item->load_grade($userid);
    }

    public function factory() {
        if (empty($this->_factory)) {
            $this->_factory = new anonymous_ui_factory();
        }

        return $this->_factory;
    }

    public function bulk_insert() {
        if (!$this->item->is_completed()) {
            return parent::bulk_insert();
        }

        return '';
    }

    public function process($data) {
        $warnings = parent::process($data);

        $anon = $this->item;

        if (empty($warnings) and !$anon->is_completed()) {
            $anon->set_completed($anon->check_completed($this->students));
        }

        return $warnings;
    }
}
