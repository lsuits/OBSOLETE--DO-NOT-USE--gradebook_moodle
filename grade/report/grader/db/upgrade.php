<?php

function xmldb_gradereport_grader_upgrade($oldversion) {

    $upgrade = new gradereport_grader_upgrade(array(
        new grader_manual_items(),
        new grader_anonymous_grading()
    ));

    return $upgrade->from($oldversion);
}

abstract class gradereport_grader_upgrade_state {
    var $version;

    abstract function upgrade($db);

    function __invoke($db) {
        return $this->upgrade($db);
    }
}

class grader_anonymous_grading extends gradereport_grader_upgrade_state {
    var $version = 2012040413;

    function upgrade($db) {
        $dbman = $db->get_manager();

        // Define table grade_anonymous_items to be created
        $table = new xmldb_table('grade_anon_items');

        // Adding fields to table grade_anonymous_items
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('complete', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table grade_anonymous_items
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_gradeitemid', XMLDB_KEY_FOREIGN_UNIQUE, array('itemid'), 'grade_items', array('id'));

        // Conditionally launch create table for grade_anonymous_items
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table grade_anon_items_history to be created
        $table = new xmldb_table('grade_anon_items_history');

        // Adding fields to table grade_anon_items_history
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('oldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('loggeduser', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('complete', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table grade_anon_items_history
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table grade_anon_items_history
        $table->add_index('gradeanonhist_act_ix', XMLDB_INDEX_NOTUNIQUE, array('action'));
        $table->add_index('gradeanonhist_old_ix', XMLDB_INDEX_NOTUNIQUE, array('oldid'));
        $table->add_index('gradeanonhist_log_ix', XMLDB_INDEX_NOTUNIQUE, array('loggeduser'));
        $table->add_index('gradeanonhist_ite_ix', XMLDB_INDEX_NOTUNIQUE, array('itemid'));

        // Conditionally launch create table for grade_anon_items_history
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

         // Define table grade_anonymous_grades to be created
        $table = new xmldb_table('grade_anon_grades');

        // Adding fields to table grade_anonymous_grades
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('anonymous_itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('finalgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('adjust_value', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, '0.00000');

        // Adding keys to table grade_anonymous_grades
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_gradeitemid', XMLDB_KEY_FOREIGN, array('anonymous_itemid'), 'grade_anonymous_items', array('id'));
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for grade_anonymous_grades
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

         // Define table grade_anon_grades_history to be created
        $table = new xmldb_table('grade_anon_grades_history');

        // Adding fields to table grade_anon_grades_history
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('oldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('source', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('loggeduser', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('anonymous_itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('finalgrade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null);
        $table->add_field('adjust_value', XMLDB_TYPE_NUMBER, '10, 5', null, XMLDB_NOTNULL, null, '0.00000');

        // Adding keys to table grade_anon_grades_history
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table grade_anon_grades_history
        $table->add_index('gradeanongrahist_act_ix', XMLDB_INDEX_NOTUNIQUE, array('action'));
        $table->add_index('gradeanongrahist_old_ix', XMLDB_INDEX_NOTUNIQUE, array('oldid'));
        $table->add_index('gradeanongrahist_log_ix', XMLDB_INDEX_NOTUNIQUE, array('loggeduser'));
        $table->add_index('gradeanongrahist_ait_ix', XMLDB_INDEX_NOTUNIQUE, array('anonymous_itemid'));

        // Conditionally launch create table for grade_anon_grades_history
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        return true;
    }
}

class grader_manual_items extends gradereport_grader_upgrade_state {
    var $version = 2011120801;

    function upgrade($db) {
        $base_sql = "%s {grade_grades} gr, {grade_items} gi %s " .
            "WHERE gi.id = gr.itemid AND gi.itemtype = 'manual'";

        $sql = sprintf($base_sql, "SELECT COUNT(*) FROM", "");

        $count = $db->count_records_sql($sql);

        if (empty($count)) {
            return true;
        } else {
            $sql = sprintf($base_sql, "UPDATE", "SET gr.rawgrade = gr.finalgrade");

            return $db->execute($sql);
        }
    }
}

class gradereport_grader_upgrade {
    function __construct($upgrades) {
        $this->upgrades = $upgrades;
    }

    function from($oldversion) {
        global $DB;

        // Oldest upgrade first
        usort($this->upgrades, function($a, $b) {
            $diff = ($a->version < $b->version) ? -1 : 1;
            return ($a->version == $b->version) ? 0 : $diff;
        });

        $result = true;

        foreach ($this->upgrades as $upgrade) {
            if (!$result) continue;

            if ($oldversion < $upgrade->version and is_callable($upgrade)) {

                try {
                    $success = $upgrade($DB);

                    $result = ($result and $success);
                } catch (Exception $e) {
                    $result = false;
                }

                upgrade_plugin_savepoint($result, $upgrade->version, 'gradereport', 'grader');
            }
        }

        return $result;
    }
}
