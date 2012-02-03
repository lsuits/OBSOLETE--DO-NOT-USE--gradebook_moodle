<?php

abstract class quick_edit_screen {
    var $courseid;

    var $itemid;

    var $groupid;

    var $context;

    function __construct($courseid, $itemid, $groupid = null) {
        $this->courseid = $courseid;
        $this->itemid = $itemid;
        $this->groupid = $groupid;

        $this->context = get_context_instance(CONTEXT_COURSE, $this->courseid);

        $this->init();
    }

    public abstract function init();

    public abstract function html();
}
