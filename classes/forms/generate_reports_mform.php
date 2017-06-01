<?php

/**
 * This class makes the form that is used to generate the custom NMIT report
 *
 * @author  Darryl Hamilton
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
require_once($CFG->dirroot."/blocks/ilp/classes/ilp_formslib.class.php");

class generate_reports_mform extends ilp_moodleform {

  public $report_id;
  public $dbc;

  /**
   * TODO comment this
   */
  function __construct() {
    global $CFG;

    $this->dbc = new ilp_db();

    // call the parent constructor
    parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/generate_reports.php");
  }

  /**
   * TODO comment this
   */
  function definition() {
    global $USER, $CFG;

    $mform =& $this->_form;

    // START DATE element
    $mform->addElement(
      'date_selector',
      'start_date',
      get_string('startdate', 'block_ilp'),
      array('class' => 'form_input')
    );
    $mform->addRule('start_date', NULL, 'required', NULL, 'client');
    $mform->setType('start_date', PARAM_RAW);

    // END DATE element
    $mform->addElement(
      'date_selector',
      'end_date',
      get_string('enddate', 'block_ilp'),
      array('class' => 'form_input')
    );
    $mform->addRule('end_date', NULL, 'required', NULL, 'client');
    $mform->setType('end_date', PARAM_RAW);

    $mform->addElement('submit', 'generatereportbutton', get_string('submit'));
  }

}


?>
