<?php

///////////////////////////////////////////////////////////////////////////
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/grade/report/quick_edit/classes/lib.php');

class grade_report_quick_edit extends grade_report {

    public static function valid_screens() {
        $screendir = dirname(__FILE__) . '/screens';

        $is_valid = function($filename) use ($screendir) {
            if (preg_match('/^\./', $filename)){
                return false;
            }

            $file = $screendir . '/' . $filename;

            if (is_file($file)) {
                return false;
            }

            $plugin = $file . '/lib.php';
            return file_exists($plugin);
        };

        return array_filter(scandir($screendir), $is_valid);
    }

    public static function classname($screen) {
        $screendir = dirname(__FILE__) . '/screens/' . $screen;

        require_once $screendir . '/lib.php';

        return 'quick_edit_' . $screen;
    }

    public static function filters() {
        $classnames = array('grade_report_quick_edit', 'classname');
        $classes = array_map($classnames, self::valid_screens());

        $screens = array_filter($classes, function($screen) {
            return method_exists($screen, 'filter');
        });

        return function($item) use ($screens) {
            $reduced = function($in, $screen) use ($item) {
                return $in && $screen::filter($item);
            };

            return array_reduce($screens, $reduced, true);
        };
    }

    function process_data($data) {
        return $this->screen->process($data);
    }

    function process_action($target, $action) {
    }

    function _s($key, $a = null) {
        return get_string($key, 'gradereport_quick_edit', $a);
    }

    function __construct($courseid, $gpr, $context, $itemtype, $itemid, $groupid=null) {
        parent::__construct($courseid, $gpr, $context);

        $class = self::classname($itemtype);

        $this->screen = new $class($courseid, $itemid, $groupid);

        // Load custom or predifined js
        $this->screen->js();

        $base = '/grade/report/quick_edit/index.php';

        $id_params = array('id' => $courseid);

        $this->baseurl = new moodle_url($base, $id_params);

        $this->pbarurl = new moodle_url($base, $id_params + array(
            'item' => $itemtype,
            'itemid' => $itemid
        ));

        $this->setup_groups();
    }

    function output() {
        global $OUTPUT;
        return $OUTPUT->box($this->screen->html());
    }
}

function grade_report_quick_edit_profilereport($course, $user) {
    global $CFG, $OUTPUT;

    if (!function_exists('grade_report_user_profilereport')) {
        require_once $CFG->dirroot . '/grade/report/user/lib.php';
    }

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    $can_use = (
        has_capability('gradereport/quick_edit:view', $context) and
        has_capability('moodle/grade:viewall', $context) and
        has_capability('moodle/grade:edit', $context)
    );

    if (!$can_use) {
        grade_report_user_profilereport($course, $user);
    } else {
        $gpr = new grade_plugin_return(array(
            'type' => 'report',
            'plugin' => 'quick_edit',
            'courseid' => $course->id,
            'userid' => $user->id
        ));

        $report = new grade_report_quick_edit($course->id, $gpr, $context, 'user', $user->id);

        echo $OUTPUT->heading($report->screen->heading());
        echo $report->output();
    }
}
