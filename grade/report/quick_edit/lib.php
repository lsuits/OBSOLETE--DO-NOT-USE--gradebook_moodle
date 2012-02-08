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

    public static function only_items() {
        return function($item) {
            return $item->itemtype != 'course' and $item->itemtype != 'category';
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
    }

    function output() {
        global $OUTPUT;
        return $OUTPUT->box($this->screen->html());
    }
}
