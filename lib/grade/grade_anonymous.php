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

    public function load_grade($user, $default=true) {
        if (empty($this->itemid) or empty($this->id)) {
            return array();
        }

        $grade = grade_anonymous_grade::fetch(array(
            'anonymous_itemid' => $this->id,
            'userid' => $user->id
        ));

        if (!$grade and $default) {
            $instance = new stdClass;

            $instance->anonymous_itemid = $this->id;
            $instance->userid = $user->id;

            $grade = new grade_anonymous_grade($instance, false);
        }

        if ($grade) {
            // TODO: rethink db... rawgrade plus itemid?
            $grade->anonymous_item = $this;
            $grade->grade_item = $this->load_item();
            $grade->itemid = $grade->grade_item->id;
            $grade->rawgrade = $grade->finalgrade;
        }

        return $grade;
    }

    public function check_completed($real_users) {
        $anon_users = $this->anonymous_users($real_users);

        $real_count = count($real_users);

        if (count($anon_users) != $real_count) {
            return false;
        }

        global $DB;

        $userids = implode(',', array_keys($real_users));
        $select = 'userid IN (' . $userids.') AND anonymous_itemid = :itemid';
        $params = array('itemid' => $this->id);

        $count = $DB->count_records_select('grade_anon_grades', $select, $params);

        return $real_count == $count;
    }

    public function is_completed() {
        return $this->complete;
    }

    public function set_completed($status = true) {
        $this->complete = $status;
        $this->update();

        if ($this->complete) {
            $grades = grade_anonymous_grade::fetch_all(array(
                'anonymous_itemid' => $this->id
            ));

            foreach ($grades as $grade) {
                $this->load_item()->update_final_grade(
                    $grade->userid, $grade->real_grade()
                );
            }
        } else {
            $grades = grade_grade::fetch_all(array('itemid' => $this->itemid));

            foreach ($grades as $grade) {
                $grade->delete();
            }
        }
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

        $sql = 'SELECT d.userid AS id, d.data FROM {user_info_data} d
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

class grade_anonymous_grade extends grade_object {
    public $table = 'grade_anon_grades';

    var $required_fields = array(
        'id', 'userid', 'anonymous_itemid', 'finalgrade', 'adjust_value'
    );

    var $anonymous_itemid;

    var $userid;

    var $finalgrade;

    var $adjust_value = 0.00000;

    var $anonymous_item;

    var $itemid;

    var $rawgrade;

    var $grade_item;

    public static function fetch($params) {
        return grade_object::fetch_helper(
            'grade_anon_grades', 'grade_anonymous_grade', $params
        );
    }

    public static function fetch_all($params) {
        return grade_object::fetch_all_helper(
            'grade_anon_grades', 'grade_anonymous_grade', $params
        );
    }

    public function load_item() {
        if (empty($this->anonymous_item)) {
            $params = array('id' => $this->anonymous_itemid);
            $this->anonymous_item = grade_anonymous::fetch($params);
        }

        return $this->anonymous_item;
    }

    public function load_grade_item() {
        if (empty($this->grade_item)) {
            $this->grade_item = $this->load_item()->load_item();
        }

        return $this->grade_item;
    }

    public function real_grade() {
        return $this->finalgrade + (float)$this->adjust_value;
    }
}
