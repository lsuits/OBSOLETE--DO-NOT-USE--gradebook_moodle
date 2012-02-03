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
    }
}
