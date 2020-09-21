<?php

/**
 * Multi-criteria selection and batch processing for courses
 *
 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @todo turn this lib into a class, and factorize the parameters $courses, $redirect and global $redirectUrl (GA 2015-09-10)
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
$redirectUrl = $CFG->wwwroot . '/admin/tool/up1_batchprocess/index.php';

/**
 * prefixes each course with a given string
 * @param array $courses
 * @param string $prefix
 * @param bool $redirect
 */
function batchaction_prefix($courses, $prefix, $redirect) {
global $redirectUrl, $DB, $CFG;

    if ($prefix) {
        foreach ($courses as $course) {
        $course->fullname = $prefix . $course->fullname;
        // $course->shortname = $prefix . $course->shortname;
        $DB->update_record('course', $course);
     }
    $msg = count($courses) . " cours préfixés.";
    if ($redirect) {
        redirect($redirectUrl);
        exit();
        }
    }
    return $msg;
}

/**
 * suffixes each course with a given string
 * @param array $courses
 * @param string $suffix
 * @param bool $redirect
 */
function batchaction_suffix($courses, $suffix, $redirect) {
global $redirectUrl, $DB, $CFG;

    if ($suffix) {
        foreach ($courses as $course) {
        $course->fullname = $course->fullname . $suffix;
        // $course->shortname = $course->shortname . $suffix;
        $DB->update_record('course', $course);
     }
    $msg = count($courses) . " cours suffixés.";
    if ($redirect) {
        redirect($redirectUrl);
        exit();
        }
    }
    return $msg;
}

/**
 * search-and-replace a given regexp  in each course
 * @param array $courses
 * @param string $regexp
 * @param string $replace
 * @param bool $redirect
 */
function batchaction_regexp($courses, $regexp, $replace, $redirect) {
global $redirectUrl, $DB, $CFG;

    foreach ($courses as $course) {
        $course->fullname = preg_replace('/' . $regexp . '/', $replace, $course->fullname);
        // $course->shortname = preg_replace('/' . $regexp . '/', $replace, $course->shortname);
        $DB->update_record('course', $course);
    }
    $msg = count($courses) . " renommés par expression rationnelle.";
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}

/**
 * open (visible:=1) or close (visible:=0) each course
 * @param array $courses
 * @param bool $redirect
 */
function batchaction_visibility($courses, $visible, $redirect) {
global $redirectUrl, $DB, $CFG;

$visibility = array(0 => 'fermé', 1 => 'ouvert');
    foreach ($courses as $course) {
        $course->visible = $visible;
        $DB->update_record('course', $course);
    }
    $msg = "Mise à jour de " . count($courses) . " cours en <b>" . $visibility[$visible] . "</b>." ;
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}


/**
 * substitute roles for all users of $rolefrom to $roleto in each course
 * @param array $courses of (DB) objects course
 * @param int $rolefrom
 * @param int $roleto
 * @param bool $redirect
 */
function batchaction_substitute($courses, $rolefrom, $roleto, $redirect) {
global $redirectUrl, $DB, $CFG, $USER;

    $modifiedroles = 0;
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        $cnt = $DB->count_records('role_assignments', array('roleid' => $rolefrom, 'contextid' => $context->id));
        $sql = "UPDATE {role_assignments} SET roleid=?, timemodified=UNIX_TIMESTAMP(), modifierid=? "
             . "WHERE roleid=? AND contextid=?";
        $ret = $DB->execute($sql, array($roleto, $USER->id, $rolefrom, $context->id));
        if ($ret) {
            $modifiedroles += $cnt;
        }
    }
    $msg = "$modifiedroles substitutions dans " . count($courses) . " cours." ;
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}


/**
 * update the custominfo field "datearchivage" for each course
 * @param array $courses of (DB) objects course
 * @param int $tsdate
 * @param bool $redirect
 */
function batchaction_archdate($courses, $tsdate, $redirect) {
global $redirectUrl, $DB, $CFG;

    foreach ($courses as $course) {
        up1_meta_set_data($course->id, 'datearchivage', $tsdate);
    }
    $msg = "Mise à jour de " . count($courses) . " cours." ;
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}

/**
 * disable each enrolment method (except 'manual') of each course
 * @param array $courses of (DB) objects course
 * @param bool $redirect
 * @param array $excepts list of enrolment methods
 * @param bool if TRUE disable ONLY $excepts methods ; if FALSE disable ALL methods except the $excepts
 * @return string diagnostic message
 */
function batchaction_disable_enrols($courses, $redirect, $excepts = array('manual'), $only = FALSE) {
global $redirectUrl, $DB, $CFG;

    $cnt = 0;
    $plugins = enrol_get_plugins(false);
    // code dérivé de  moodle/enrol/instances.php l.143  (action=='disable')
    foreach ($courses as $course) {
        $instances = enrol_get_instances($course->id, false); // records of table "enrol"
        foreach ($instances as $instanceid => $instance) {
            $plugin = $plugins[$instance->enrol];
            if ( $instance->status != ENROL_INSTANCE_DISABLED 
                 && (in_array($instance->enrol, $excepts) == $only) ) {
                $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
                $cnt++;
            }
        }
    }
    $msg = "Désactivation de $cnt méthodes d'inscription dans " . count($courses) . " cours.";
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}

/**
 * backup all courses in predefined directory (backup_auto_destination)
 * @param array $courses of (DB) objects course
 * @param bool $redirect
 */
function batchaction_backup($courses, $redirect) {
global $redirectUrl, $CFG, $USER;

    $dir = get_config('backup', 'backup_auto_destination');
    $cnt = 0;
    foreach ($courses as $course) {
        // code from /admin/tool/backup.php (excerp from Moodle 2.7)
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
                            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $USER->id);
        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
        $bc->get_plan()->get_setting('filename')->set_value($filename);
        // Execution.
        $bc->finish_ui();
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination']; // May be empty if file already moved to target location.

        // If OK, store backup in $dir
        if ($file) {
            if ($file->copy_content_to($dir.'/'.$filename)) {
                $file->delete();
            } else {
                echo "Destination directory does not exist or is not writable. Leaving the backup in the course backup file area.";
            }
            $cnt++;
        }
        $bc->destroy();
    }
    $msg = "Création de $cnt archives de cours (mbz) dans $dir.";
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
    return $msg;
}