<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A moodleform for editing grade letters
 *
 * @package   core_grades
 * @copyright 2007 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class edit_letter_form extends moodleform {

    public function definition() {
        global $DB;

        $mform =& $this->_form;
        $num   = $this->_customdata['num'];
        $admin = $this->_customdata['admin'];

        $mform->addElement('header', 'gradeletters', get_string('gradeletters', 'grades'));

        // Only show "override site defaults" checkbox if editing the course grade letters
        if (!$admin) {
            $mform->addElement('checkbox', 'override', get_string('overridesitedefaultgradedisplaytype', 'grades'));
            $mform->addHelpButton('override', 'overridesitedefaultgradedisplaytype', 'grades');
        }

        $gradeletter       = get_string('gradeletter', 'grades');
        $gradeboundary     = get_string('gradeboundary', 'grades');

        $unused_str = get_string('unused', 'grades');

        $percentages = array(-1 => $unused_str);
        for ($i=100; $i > -1; $i--) {
            $percentages[$i] = "$i %";
        }

        $custom = get_config('moodle', 'grade_letters_custom');
        $strict = get_config('moodle', 'grade_letters_strict');

        $default = get_config('moodle', 'grade_letters_names');

        if ($default and $scale = $DB->get_record('scale', array('id' => $default))) {
            $default_letters = $scale->scale;
        } else {
            $default_letters = get_string('lettersdefaultletters', 'grades');
        }

        $default_letters = array_reverse(explode(',', $default_letters));
        $letters = array('' => get_string('unused', 'grades')) +
            array_combine($default_letters, $default_letters);

        for($i=1; $i<$num+1; $i++) {
            $gradelettername = 'gradeletter'.$i;
            $gradeboundaryname = 'gradeboundary'.$i;

            if ($strict) {
                $mform->addElement('select', $gradelettername, $gradeletter." $i", $letters);
            } else {
                $mform->addElement('text', $gradelettername, $gradeletter." $i");
            }

            if ($i == 1) {
                $mform->addHelpButton($gradelettername, 'gradeletter', 'grades');
            }
            $mform->setType($gradelettername, PARAM_TEXT);

            if (!$admin) {
                $mform->disabledIf($gradelettername, 'override', 'notchecked');

                if ($custom) {
                    $mform->disabledIf($gradeboundaryname, $gradelettername, 'eq', '');
                } else {
                    $mform->disabledIf($gradelettername, $gradeboundaryname, 'eq', -1);
                }
            }

            if ($custom) {
                $mform->addElement('text', $gradeboundaryname, $gradeboundary." $i");

                $mform->addRule($gradeboundaryname, null, 'numeric', '', 'client');

                $mform->setType($gradeboundaryname, PARAM_FLOAT);
                $mform->setDefault($gradeboundaryname, '');
            } else {
                $mform->addElement('select', $gradeboundaryname, $gradeboundary." $i", $percentages);

                $mform->setType($gradeboundaryname, PARAM_INT);
                $mform->setDefault($gradeboundaryname, -1);
            }

            if ($i == 1) {
                $mform->addHelpButton($gradeboundaryname, 'gradeboundary', 'grades');
            }

            if (!$admin) {
                $mform->disabledIf($gradeboundaryname, 'override', 'notchecked');
            }
        }

        // hidden params
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(!$admin);
    }

}


