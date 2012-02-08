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

require_once '../../../config.php';
require_once $CFG->dirroot.'/lib/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/quick_edit/lib.php';

$courseid = required_param('id', PARAM_INT);
$itemtype = optional_param('item', 'select', PARAM_TEXT);
$itemid = optional_param('itemid', null, PARAM_INT);
$groupid  = optional_param('group', null, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/quick_edit/index.php', array(
    'id' => $courseid,
    'item' => $itemtype,
    'itemid' => $itemid,
    'group' => $groupid
)));

$course_params = array('id' => $courseid);

if (!$course = $DB->get_record('course', $course_params)) {
    print_error('nocourseid');
}

if (!in_array($itemtype, grade_report_quick_edit::valid_screens())) {
    print_error('notvalid', 'gradereport_quick_edit', '', $itemtype);
}

require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $course->id);

// This is the normal requirements
require_capability('gradereport/quick_edit:view', $context);
require_capability('moodle/grade:viewall', $context);
require_capability('moodle/grade:edit', $context);
// End permission

$gpr = new grade_plugin_return(array(
    'type' => 'report', 'plugin' => 'quick_edit', 'courseid' => $courseid
));

grade_regrade_final_grades($courseid);

$report = new grade_report_quick_edit(
    $courseid, $gpr, $context, $itemtype, $itemid, $groupid
);

$reportname = $report->screen->heading();

$pluginname = get_string('pluginname', 'gradereport_quick_edit');

$report_url = new moodle_url('/grade/report/index.php', $course_params);
$edit_url = new moodle_url('/grade/report/quick_edit/index.php', $course_params);

$PAGE->navbar->add(get_string('gradeadministration', 'grades'));
$PAGE->navbar->add(get_string('pluginname', 'gradereport_grader'), $report_url);

if ($reportname != $pluginname) {
    $PAGE->navbar->add($pluginname, $edit_url);
    $PAGE->navbar->add($reportname);
} else {
    $PAGE->navbar->add($pluginname);
}

print_grade_page_head($course->id, 'report', 'quick_edit', $reportname);

if ($data = data_submitted()) {
    $warnings = $report->process_data($data);

    if (empty($warnings)) {
        echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
    } else {
        foreach ($warnings as $warning) {
            echo $OUTPUT->notification($warning);
        }
    }
}

echo $report->output();

echo $OUTPUT->footer();

?>
