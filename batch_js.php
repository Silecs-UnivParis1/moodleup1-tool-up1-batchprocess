//<![CDATA[
var mvForm = document.getElementById('movecourses');
try {
    mvForm.addEventListener("submit", confirmCourseRenaming, false);
    document.getElementById('course-selectall').addEventListener("click", toggleCourseSelection);
} catch(e) {
    mvForm.attachEvent("onsubmit", confirmCourseRenaming); // IE
    document.getElementById('course-selectall').attachEvent("onclick", toggleCourseSelection);
}
function confirmCourseRenaming(event) {
    var coursesCount = 0;
    var checkboxes = document.getElementsByClassName('course-select');
    for (var i=0; i<checkboxes.length; i++) {
        if (checkboxes[i].type == 'checkbox' && checkboxes[i].checked) {
            coursesCount++;
        }
    }
    if (!coursesCount) {
        alert("Aucun cours sélectionné.");
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false
        }
        return false;
    }
    if (event.relatedTarget.value === "regexp") { // do not warn when there is a confirmation step
        return true;
    }
    if (!confirm(coursesCount + " cours seront impactés.\nÊtes-vous certain de vouloir agir sur ces cours ?")) {
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false
        }
        return false;
    }
}
function toggleCourseSelection() {
    var current = document.getElementById('course-selectall');
    var checkboxes = document.getElementsByClassName('course-select');
    for (var i=0; i<checkboxes.length; i++) {
        if (checkboxes[i].type == 'checkbox') {
            checkboxes[i].checked = (current.value == '0');
        }
    }
    if (current.value == '0') {
        current.value = '1';
    } else {
        current.value = '0';
    }
}
<?php
if ($preview) {
    echo "toggleCourseSelection();\n";
}
?>
//]]>