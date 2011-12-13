<?php

function xmldb_gradereport_grader_upgrade($oldversion) {

    $upgrade = new gradereport_grader_upgrade(array(
        new grader_manual_items()
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
