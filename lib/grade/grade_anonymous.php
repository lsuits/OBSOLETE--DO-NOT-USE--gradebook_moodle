<?php

class grade_anonymous extends grade_object {
    var $required_fields = array('id', 'itemid', 'complete');

    var $id;

    var $itemid;

    var $complete = false;

    public static $profileid;

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

    public function load_grade($userid, $default=true) {
        if (empty($this->itemid) or empty($this->id)) {
            return array();
        }

        $grade = grade_anonymous_grade::fetch(array(
            'anonymous_itemid' => $this->id,
            'userid' => $userid
        ));

        if (!$grade and $default) {
            $instance = new stdClass;

            $instance->anonymous_itemid = $this->id;
            $instance->userid = $userid;

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

    public function update_final_grade($userid, $finalgrade=false, $source=null, $feedback=false, $feedbackformat=FORMAT_MOODLE, $usermodified=null) {
        $grade = $this->load_grade($userid);

        if (!$this->is_completed()) {
            // Clients of API should be mindful of scales; empty scale is -1
            if ($grade->id and empty($finalgrade)) {
                return $grade->delete($source);
            }

            $grade->finalgrade = $this->bounded_grade($finalgrade);
            return $grade->id ? $grade->update($source) : $grade->insert($source);
        } else {
            $grade->adjust_value = $finalgrade ?
                $grade->bound_adjust_value($finalgrade) : 0;

            $grade->update($source);

            return $this->load_item()->update_final_grade(
                $userid, $this->bounded_grade($grade->real_grade()), $source,
                $feedback, $feedbackformat, $usermodified
            );
        }
    }

    public function check_completed($real_users) {
        global $DB;

        $anon_users = $this->anonymous_users($real_users);

        $real_count = count($real_users);

        if (count($anon_users) != $real_count) {
            return false;
        }

        $userids = implode(',', array_keys($real_users));
        $select = 'userid IN (' . $userids . ') AND anonymous_itemid = :itemid';
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
            $this->load_item()->delete_all_grades();
        }
    }

    public static function anonymous_profile() {
        global $DB;

        if (empty(self::$profileid)) {
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

            self::$profileid = $fieldid;
        }

        return self::$profileid;
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
        $params = array('anonymous_itemid' => $this->id);

        if ($grades = grade_anonymous_grade::fetch_all($params)) {
            foreach ($grades as $grade) {
                $grade->delete($source);
            }
        }
        return parent::delete($source);
    }

    public function __call($name, $args) {
        if (!method_exists($this->load_item(), $name)) {
            print_error('anonymousnomethod', 'grades', '', $name);
        }

        return call_user_func_array(array($this->load_item(), $name), $args);
    }

    public function __get($name) {
        if (isset($this->load_item()->$name)) {
            return $this->load_item()->$name;
        }

        return null;
    }
}

class grade_anonymous_grade extends grade_object {
    public $table = 'grade_anon_grades';

    private static $adjust_boundary;

    var $required_fields = array(
        'id', 'userid', 'anonymous_itemid', 'finalgrade', 'adjust_value'
    );

    var $anonymous_itemid;

    var $userid;

    var $finalgrade;

    var $adjust_value = 0;

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

    public function anonymous_number() {
        global $DB;

        $params = array(
            'userid' => $this->userid,
            'fieldid' => $this->load_item()->anonymous_profile()
        );

        return $DB->get_field('user_info_data', 'data', $params);
    }

    public function bound_adjust_value($value) {
        $max = abs($this->adjust_boundary());
        $min = -1 * $max;

        if ($value < $min) {
            return $min;
        } else if ($value > $max) {
            return $max;
        } else {
            return $value;
        }
    }

    public static function adjust_boundary() {
        if (empty(self::$adjust_boundary)) {
            self::$adjust_boundary =
                (float)get_config('moodle', 'grade_anonymous_adjusts');
        }

        return self::$adjust_boundary;
    }
}
