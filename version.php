<?php

/**
 * Multi-criteria selection and batch processing for courses
 *
 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2014041603;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'tool_up1_batchprocess'; // Full name of the plugin (used for diagnostics)

$plugin->cron      = 0;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'TODO';

$plugin->dependencies = array(
    'local_up1_metadata' => 2013090900,
);

/** WARNING modifications into core :
 *  settings/courses.php +55  to add a link into the menu "Site administration / Courses"
 */
