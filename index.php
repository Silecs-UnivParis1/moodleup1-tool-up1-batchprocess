<?php

/**
 * Multi-criteria selection and batch processing for courses
 *
 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__DIR__))) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/batch_form.php');
require_once(__DIR__ . '/batch_lib.php');
require_once(__DIR__ . '/batch_libactions.php');
require_once(__DIR__ . '/locallib.php');
global $DB, $PAGE;

$actionchecks = optional_param('actioncheck', array(), PARAM_RAW);
$coursesid = optional_param_array('c', array(), PARAM_INT);  // which courses to act on
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 100, PARAM_INT); // how many per page

require_login(get_site());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/admin/tool/index.php');
$PAGE->set_title(get_string("coursebatchactions", 'tool_up1_batchprocess'));
$PAGE->set_heading(get_string("coursebatchactions", 'tool_up1_batchprocess'));

$preview = array();
$regexp = '';
$replace = '';
$confirm = false;
$msg = ''; //flash message for action diagnostics

foreach ($actionchecks as $action) {
    $courses = $DB->get_records_list('course', 'id', $coursesid);
    switch ($action) {
        case 'prefix':
            $prefix = optional_param('batchprefix', '', PARAM_RAW);
            $msg .= batchaction_prefix($courses, $prefix, false) . "<br />\n";
            break;

        case 'suffix':
            $suffix = optional_param('batchsuffix', '', PARAM_RAW);
            $msg .= batchaction_suffix($courses, $suffix, false) . "<br />\n";
            break;

        case 'regexp':
            $regexp = optional_param('batchregexp', '', PARAM_RAW);
            $replace = optional_param('batchreplace', '', PARAM_RAW);
            $confirm = optional_param('batchconfirm', '', PARAM_BOOL);
            if ($regexp) {
                if ($confirm) {
                    $msg .= batchaction_regexp($courses, $regexp, $replace, true) . "<br />\n";
                } else {
                    foreach ($courses as $course) {
                        $preview[$course->id] = preg_replace('/' . $regexp . '/', $replace, $course->fullname);
                    }
                }
            }
            break;

        case 'close':
            $msg .= batchaction_visibility($courses, 0, false) . "<br />\n";
            break;

        case 'open':
            $msg .= batchaction_visibility($courses, 1, false) . "<br />\n";
            break;

       case 'substitute':
           $rolefrom = optional_param('batchsubstfrom', '', PARAM_INT);
           $roleto = optional_param('batchsubstto', '', PARAM_INT);
           $msg .= batchaction_substitute($courses, $rolefrom, $roleto, false) . "<br />\n";
           break;

       case 'substitute2':
           $rolefrom = optional_param('batchsubst2from', '', PARAM_INT);
           $roleto = optional_param('batchsubst2to', '', PARAM_INT);
           $msg .= batchaction_substitute($courses, $rolefrom, $roleto, false) . "<br />\n";
           break;

        case 'archdate':
            $isodate = optional_param('batcharchdate', '', PARAM_RAW);
            $tsdate = isoDateToTs($isodate);
            //** @todo valider la date **
            $msg .= batchaction_archdate($courses, $tsdate, false) . "<br />\n";
            break;

        case 'disableenrols':
            $msg .= batchaction_disable_enrols($courses, false, array('manual'), false) . "<br />\n";
            break;

        case 'disablemanualenrols':
            $msg .= batchaction_disable_enrols($courses, false, array('manual'), true) . "<br />\n";
            break;

        case 'backup':
            $msg .= batchaction_backup($courses, false) . "<br />\n";
            break;
    }
} // foreach (actionchecks)


$searchconfig = array(
    'startdate' => true,
);

$form = new course_batch_search_form(null, $searchconfig);
$data = $form->get_data();
$totalcount = 0;
$courses = null;
if ($data) {
    $limit = $data->limitresults;
    $courses = get_courses_batch_search($data, "c.fullname ASC", $page, $limit, $totalcount);
} else if ($coursesid) {
    $courses = $DB->get_records_list('course', 'id', $coursesid);
}

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('coursebatchactions', '', array(), $CFG->wwwroot . '/admin/tool/up1_batchprocess/index.php');

$settingsnode = $PAGE->settingsnav->find_active_node();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("coursebatchactions", 'tool_up1_batchprocess'));

echo $OUTPUT->box_start('boxaligncenter generalbox');
echo $msg;
echo $OUTPUT->box_end();

if (empty($courses)) {
    if (is_array($courses)) {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
} else {
?>
    <form id="movecourses" action="index.php" method="post">
        <div class="generalbox boxaligncenter">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
            <table border="0" cellspacing="2" cellpadding="4" class="course-selection">
                <tr>
                    <th><input type="checkbox" name="course-selectall" id="course-selectall" value="0" /></th>
                    <th class="header" scope="col"><?php echo get_string('courses') . ' (' . count ($courses) . ')' ; ?></th>
                    <?php if ($preview) { ?>
                    <th class="header" scope="col"><?php echo get_string('preview'); ?></th>
                    <?php } ?>
                </tr>
                <?php
                foreach ($courses as $course) {
                    echo '<tr>';
                    echo '<td align="center">';
                    echo '<input type="checkbox" name="c[]" value="' . $course->id . '" class="course-select" />';
                    echo '</td>';
                    $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                    $coursename = get_course_display_name_for_list($course);
                    $linkcss = ( ($course->visible === 1) ? '' : ' class="dimmed" ');
                    echo '<td><a '.$linkcss.' href="' . $courseurl . '">'. format_string($coursename) .'</a></td>';
                    if ($preview && isset($preview[$course->id])) {
                        echo "<td>";
                        if ($course->fullname !== $preview[$course->id]) {
                            echo "<strong>" . format_string($preview[$course->id]) . "</strong>";
                        } else {
                            echo '<span class="dimmed_text">' . format_string($preview[$course->id]) . "</span>";
                        }
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </table>
            <fieldset><legend><?php echo get_string('actions'); ?></legend>
                <ul>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="close" />
                        Fermer
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="open" />
                        Ouvrir
                    </li>

                    <li style="margin-top:1ex">
                        <input type="checkbox" name="actioncheck[]" value="prefix">
                        Préfixer avec
                        <input type="text" name="batchprefix" size="50" value="<?php echo default_prefix(); ?> "/>
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="suffix">
                        Suffixer avec
                        <input type="text" name="batchsuffix" size="50" />
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="regexp" />
                        Renommer par Regexp
                        s/<input type="text" name="batchregexp" size="40" value="<?php echo htmlspecialchars($regexp); ?>" />/
                        <input type="text" name="batchreplace" size="40" value="<?php echo htmlspecialchars($replace); ?>" />/
                        <?php if (in_array('regexp', $actionchecks)) { ?>
                        <label>
                            <input type="checkbox" name="batchconfirm" value="1" />
                            <?php echo get_string('confirm'); ?>
                        </label>
                        <?php } ?>
                    </li>

                    <li style="margin-top:1ex">
                        <input type="checkbox" name="actioncheck[]" value="substitute" />
                        <?php
                        $roles = get_assignableroles();
                        $default = default_subst_roles();
                        echo "Substituer " . html_select('batchsubstfrom', $roles, $default['from']);
                        echo " par " . html_select('batchsubstto', $roles, $default['to']) ;
                        ?>
                    </li>
                    <li style="margin-top:1ex">
                        <input type="checkbox" name="actioncheck[]" value="substitute2" />
                        <?php
                        $default = default_subst_roles_student();
                        echo "Substituer " . html_select('batchsubst2from', $roles, $default['from']);
                        echo " par " . html_select('batchsubst2to', $roles, $default['to']) ;
                        ?>
                    </li>

                    <li>
                        <input type="checkbox" name="actioncheck[]" value="archdate">
                        Archiver en date du
                        <input type="text" value="<?php echo isoDate(); ?>" name="batcharchdate" />
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="disableenrols" />
                        Désactiver les inscriptions (sauf manuelles)
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="backup" />
                        <?php echo "Archiver dans " . get_config('backup', 'backup_auto_destination'); ?>
                    </li>
                    <li>
                        <input type="checkbox" name="actioncheck[]" value="disablemanualenrols" />
                        Désactiver les inscriptions manuelles
                    </li>
                </ul>
                <input type="submit" name="Exec" value="Exécuter" />
            </fieldset>
        </div>
    </form>
    <script type="text/javascript">
<?php
    include "batch_js.php";
?>
    </script>
<?php
}

$form->display();
echo $OUTPUT->footer();
