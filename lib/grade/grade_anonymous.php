<?php

class grade_anonymous extends grade_object {
    var $required_fields = array('id', 'itemid', 'complete');

    var $id;

    var $itemid;

    var $complete = false;

    var $grade_item;

    public $table = 'grade_anon_items';

    public static function fetch($params) {
        return grade_object::fetch_helper(
            'grade_anon_items', 'grade_anonymous', $params
        );
    }

    public static function fetch_all($params) {
        return grade_object::fetch_all_helper(
            'grade_anon_items', 'grade_anonymous', $params
        );
    }

    public function load_item() {
        if (empty($this->grade_item) and !empty($this->itemid)) {
            $this->grade_item = grade_item::fetch(array('id' => $this->itemid));
        }

        return $this->grade_item;
    }

    public function is_completed() {
        return $this->complete;
    }

    public static function anonymous_profile() {
        global $DB;

        $fields = $DB->get_records('user_info_field');

        if (empty($fields)) {
            debugging('No user profile fields to choose from.');
            return false;
        }

        $fieldid = get_config('moodle', 'grade_anonymous_field');

        if (empty($fieldid) or !isset($fields[$fieldid])) {
            debugging('Selected anonymous profile field does not exists.');
            return false;
        }

        return $fieldid;
    }

    public static function anonymous_users($real_users) {
        global $DB;

        $profileid = self::anonymous_profile();

        if (empty($profileid)) {
            return array();
        }

        $userids = implode(',', array_keys($real_users));

        $sql = 'SELECT d.userid, d.data FROM {user_info_data} d
            WHERE d.userid IN (' . $userids.')
              AND d.fieldid = :fieldid';

        $params = array('fieldid' => $profileid);
        $anonymous_users = $DB->get_records_sql($sql, $params);

        return $anonymous_users;
    }

    public static function is_supported($course) {
        // Enabled system wide?
        $enabled = (bool)get_config('moodle', 'grade_anonymous_grading');

        $cats = explode(',', get_config('moodle', 'grade_anonymous_cats'));

        $is_cat = (empty($cats) or in_array($course->category, $cats));

        return ($enabled and $is_cat);
    }

    public function delete($source = null) {
        if (parent::delete($source)) {
            return $this->load_item()->delete($source);
        }

        return false;
    }
}
