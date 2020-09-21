<?php

/**
 * Multi-criteria selection and batch processing for courses

 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/*
 * returns a list of roles assignable in a course context, for substitution (dropdown list)
 */
function get_assignableroles() {
    global $DB;
    $assignableroles = array_values(get_roles_for_contextlevels(CONTEXT_COURSE));
    $sql = 'SELECT id, name FROM {role} WHERE id IN (' . implode(', ', $assignableroles) . ')';
    $menuroles = $DB->get_records_sql_menu($sql);
    return $menuroles;
}

function html_select($name, $array, $defaultkey) {
    $res = '<select name="' . $name . '">';
    foreach ($array as $key => $value) {
        $selec = ($key == $defaultkey ? ' selected="selected" ' : '');
        $res .= '<option value="' . $key . '" ' . $selec .  '>' . $value . '</option>';
    }
    $res .= '</select>';
    return $res;
}


/**
 * Some ad-hoc functions to ease and automatize the use of batchprocess - see
 * http://tickets.silecs.info/mantis/view.php?id=2399
 */

/**
 * @todo this should be based on the level-1 categories, but as we can't know for sure
 * which courses are selected, this is a wild guess.
 * Possible to make a better choice?
 */
function default_prefix() {
    $year = (integer) date('Y');
    $res = "Archive année " . (integer) ($year - 1) ."-". $year;
    return $res;
}

/**
 * get default substituion roles for an ad-hoc use (quick & dirty)
 * @return array('from' => X, 'to' => Y)
 */
function default_subst_roles() {
    global $DB;
    $res = array('from' => '1', 'to' => '1' );

    if ($DB->record_exists('role', array('shortname' => 'editingteacher'))) {
        $res['from'] = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
    }
    if ($DB->record_exists('role', array('shortname' => 'ens_epi_archive'))) {
        $res['to'] = $DB->get_field('role', 'id', array('shortname' => 'ens_epi_archive'));
    }
    return $res;
}

/**
 * get default substituion roles for an ad-hoc use (quick & dirty)
 * @return array('from' => X, 'to' => Y)
 */
function default_subst_roles_student() {
    global $DB;
    $res = array('from' => '1', 'to' => '1' );

    if ($DB->record_exists('role', array('shortname' => 'student'))) {
        $res['from'] = $DB->get_field('role', 'id', array('shortname' => 'student'));
    }
    if ($DB->record_exists('role', array('shortname' => 'student-epi-archived'))) {
        $res['to'] = $DB->get_field('role', 'id', array('shortname' => 'student-epi-archived'));
    }
    return $res;
}
