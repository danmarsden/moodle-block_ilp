<?php

/**
 * Generate the NMIT report entry report
 *
 * @author  Darryl Hamilton
 * @copyright 2013 Catalyst IT Ltd
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require('../../../config.php');
global $CFG, $USER, $DB, $PARSER;

// Meta includes
require_once($CFG->dirroot . '/blocks/ilp/admin_actions_includes.php');

// include the report form class
require_once($CFG->dirroot . '/blocks/ilp/classes/forms/generate_reports_mform.php');

// Breadcrumb navigation
$PAGE->navbar->add(get_string('administrationsite'), null, 'title');
$PAGE->navbar->add(get_string('plugins', 'admin'), null, 'title');

$PAGE->navbar->add(get_string('blocks'), null, 'title');

//block name
$url = $CFG->wwwroot . "/admin/settings.php?section=blocksettingilp";
$PAGE->navbar->add(get_string('blockname', 'block_ilp'), $url, 'title');

// get string for generate report
$pagetitle = get_string('generatereports', 'block_ilp');
$PAGE->navbar->add($pagetitle, null, 'title');

// setup the page title and heading
$dbc  = new ilp_db();
$SITE = $dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname . " : " . get_string('blockname', 'block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/generate_reports.php', $PARSER->get_params());

// instantiate a new form object
$mform = new generate_reports_mform();

if ($mform->is_submitted()) {
    $formdata = $mform->get_data();

    // Set HTTP headers to instruct the browser to download the content as a file.
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=student_report_entries.csv');

    // IE requires some further massaging
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
    } else {
        header('Pragma: no-cache');
    }

    $sql    = "
    select
        e.id,
        e.report_id,
        e.user_id,
        u.firstname as student_firstname,
        u.lastname as student_lastname,
        u.username as student_username,
        u.idnumber as student_idnumber,
        usi.value as student_status,
        r.name as report_type,
        bu.firstname as set_by_firstname,
        bu.lastname as set_by_lastname,
        bu.username as set_by_username,
        bu.idnumber as set_by_idnumber,
        to_timestamp(to_timestamp(e.timecreated)::text, 'YYYY-MM-DD HH24:MI:SS')::timestamp without time zone as date_added
    from {block_ilp_report} as r
    inner join {block_ilp_entry} as e on (e.report_id = r.id)
    inner join {user} as u on (u.id = e.user_id)
    inner join {user} as bu on (bu.id = e.creator_id)
    inner join {block_ilp_user_status} as us on (us.user_id = u.id)
    inner join {block_ilp_plu_sts_items} as usi on (usi.id = us.parent_id)
    where r.deleted = 0
    and (e.timecreated between :start_date and :end_date)
    order by e.user_id, r.name";
    $result = $DB->get_records_sql($sql, array(':start_date' => $formdata->start_date, ':end_date' => $formdata->end_date));

//  $report_fields = array(
//    3 => array(
//      'support_services' => 24,
//      'completion_date'  => 9,
//      'course'           => 7,
//      'progress'         => 10,
//    ),
//    4 => array(
//      'course'           => 11,
//      'review_date'      => 17,
//      'status'           => 16,
//      'support_services' => 15,
//    ),
//    5 => array(
//      'course'           => 18,
//    ),
//  );

    $report_fields = array(
        1 => array(
            'support_services' => 18,
            'completion_date'  => 2,
            'course'           => 17,
            'progress'         => 5,
        ),
        2 => array(
            'course'           => 19,
            'review_date'      => 8,
            'status'           => 10,
            'support_services' => 22,
        ),
        5 => array(
            'course' => 23,
        ),
    );

    $csv         = array();
    $base_fields = array(
        'student_firstname' => 'Student First Name',
        'student_lastname'  => 'Student Last Name',
        'student_username'  => 'Student User Name',
        'student_idnumber'  => 'Student ID Number',
        'student_status'    => 'Student Status',
        'report_type'       => 'Report Type',
        'set_by_firstname'  => 'Set By First Name',
        'set_by_lastname'   => 'Set By Last Name',
        'set_by_username'   => 'Set By User Name',
        'set_by_idnumber'   => 'Set By ID Number',
        'date_added'        => 'Date Added',
        'course_fullname'   => 'Course Full Name',
        'course_shortname'  => 'Course Short Name',
        'review_date'       => 'Review Date',
        'completion_date'   => 'Completion Date',
        'item_status'       => 'Item Status',
        'item_progress'     => 'Item Progress',
        'support_services'  => 'Support Services',
    );

    $field_value_is_array = array(
        'course', 'progress', 'status'
    );

    print(_array_to_CSV(array_values($base_fields)));

    foreach ($result as $entry) {
        foreach (array_keys($base_fields) as $base_field) {
            if (property_exists($entry, $base_field)) {
                $csv[$base_field] = $entry->$base_field;
            } else {
                $csv[$base_field] = null;
            }
        }

        $entry_info = $dbc->get_entry_by_id($entry->id);
        $fields     = $dbc->get_report_fields($entry->report_id);

        if (isset($report_fields[$entry->report_id])) {
            foreach ($report_fields[$entry->report_id] as $field_type => $field_id) {
                $field        = $fields[$field_id];
                $pluginrecord = $dbc->get_plugin_by_id($field->plugin_id);

                $classname = $pluginrecord->name;

                include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                //instantiate the plugin class
                $pluginclass = new $classname();

                $pluginclass->load($field->id);

                $entry_data = new stdClass();
                $pluginclass->entry_data($field->id, $entry->id, $entry_data);

                $plugin_property = sprintf("%s_field", $field->id);
                if (property_exists($entry_data, $plugin_property)) {
                    if (is_array($entry_data->{$plugin_property}) && (in_array($field_type, $field_value_is_array))) {
                        $plugin_value = array_shift($entry_data->{$plugin_property});
                    } else {
                        $plugin_value = $entry_data->{$plugin_property};
                    }

                    if ($field_type == 'course') {
                        $course = $dbc->get_course($plugin_value);
                        if ($course !== false) {
                            $csv['course_fullname']  = $course->fullname;
                            $csv['course_shortname'] = $course->shortname;
                        }
                    }

                    if ($field_type == 'status') {
                        $csv['item_status'] = $plugin_value;
                    }

                    if ($field_type == 'review_date') {
                        $csv['review_date'] = date("Y-m-d H:i:s", $plugin_value);
                    }

                    if ($field_type == 'completion_date') {
                        $csv['completion_date'] = date("Y-m-d H:i:s", $plugin_value);
                    }

                    if ($field_type == 'support_services') {
                        $field_values = $dbc->get_pluginentry($pluginclass->tablename, $entry->id, $field->id, true);
                        $tmp_values   = array();
                        foreach ($field_values as $field_value) {
                            $tmp_values[] = $field_value->value;
                        }

                        $csv['support_services'] = implode(',', $tmp_values);
                    }

                    if ($field_type == 'progress') {
                        $state_items = $dbc->get_report_stateitems($entry->report_id);
                        if (array_key_exists($plugin_value, $state_items)) {
                            $csv['item_progress'] = $state_items[$plugin_value]->value;
                        }
                    }
                }
            }
        }

        print(_array_to_CSV($csv));
    }
} else {
    require_once($CFG->dirroot . '/blocks/ilp/views/generate_reports.html');
}

// helper function for outputting CSV with CRLF line endings
function _array_to_CSV($data) {
    $outstream = fopen("php://temp", 'r+');
    fputcsv($outstream, $data, ',', '"');
    rewind($outstream);
    $csv = fgets($outstream);
    fclose($outstream);

    return $csv;
}
