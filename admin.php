<?php

// filename
// course name
// teachers
// # enrolled

require_once('../../config.php');             // global config
$config = include_once('config/config.php');  // plugin config

// if config indicates development mode, then allow errors to be thrown to the dom

  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  ini_set('memory_limit', '1024M');
  error_reporting(-1);


global $OUTPUT;
global $PAGE;

// meta stuff
$PAGE->set_context(context_system::instance());
$PAGE->set_title('File Scan Report (System-wide)');
$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('reportheading', 'block_filescan'));
$PAGE->set_cacheable($config['cacheable']);

// a valid token is required to access the web service.
// see: https://docs.moodle.org/35/en/Using_web_services
$token = array('token' => $config['token']);

// use this variable to scale the bars under the displayed percentages
$scale = array(
  'x' => 2,
  'y' => 1
);

// use this variable to define the width and height (in pixels) of the progress bars
// located in each card
$progressBar = array(
  'width'   => 100 * $scale['x'],
  'height'  => 16  * $scale['y']
);

/**
 * This counts how many records have the passed in option
 *
 * @param $option
 * @return int
 */
function has($option)
{
  global $DB;
  $table = 'block_filescan_files';

  switch ($option) {
    case 'text':
      return $DB->count_records($table, ['hastext' => 1]);
      break;
    case 'title':
      return $DB->count_records($table, ['hastitle' => 1]);
      break;
    case 'outline':
      return $DB->count_records($table, ['hasoutline' => 1]);
      break;
    case 'language':
      return $DB->count_records($table, ['haslanguage' => 1]);
      break;
    default: // catch all
      return 0;
      break;
  }
}

/**
 * This function will generate a report encapsulating all files within the plugin table
 *
 * @param $status
 * @return int
 */

function generateOverallReport($status)
{
  global $DB;

  $table = 'block_filescan_files';

  switch ($status) {
    case 'passing':
      return $DB->count_records($table, ['status' => 'pass']);
      break;
    case 'fails':
      return $DB->count_records($table, ['status' => 'fail']);
      break;
    case 'errors':
      return $DB->count_records($table, ['status' => 'error']);
      break;
    case 'checks':
      return $DB->count_records($table, ['status' => 'check']);
      break;
    default: // catch all
      return 0;
      break;
  }
}

// call the datatables module
$PAGE->requires->js_call_amd('block_filescan/dataTableModule', 'make', $token);

/**
 * This function returns the total records within $table
 *
 * @param $table
 * @return int
 */

function getTotalRecords($table)
{
  global $DB;
  return $DB->count_records($table, $conditions = null);
}

// TODO: move all this output junk into a function or class
// generate the dashboard totals
$checks         = array('title', 'text', 'outline', 'language');
$totalRecords   = getTotalRecords('block_filescan_files');

// start outputting our page
echo $OUTPUT->header();

echo html_writer::tag('h4', 'At a Glance', array('class' => 'text-primary'));

// generate the title, text, outline and language card row
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

foreach ($checks as $check) {
  $fileHas    = has($check); // do this so we dont kill the db
  $completed  = round($fileHas / $totalRecords * 100,2);

  $fillAttributes = array(
    'style' => 'width: ' . $completed * $scale['x'] . 'px; height: ' . $progressBar['height'] . 'px; max-width: 95%;',
    'class' => 'bg-success'
  );

  $cardAttributes = array(
    'class' => 'card text-white bg-dark m-3 p-3 text-center'
  );

  $primaryCard = array(
    'class' => 'card text-white bg-primary m-3 p-3 text-center'
  );

  echo html_writer::start_tag('div', $cardAttributes, null);
  echo html_writer::start_tag('div', array('class' => 'card-body'), null);
  echo html_writer::tag('p', ucfirst($check), array('class' => 'lead'));

  echo html_writer::tag('h3', $completed . '%', array('class' => 'text-white display-4'));
  echo html_writer::start_tag('div', array('style' => 'margin: 0 auto;  max-width: 95%; width: ' . $progressBar['width'] . 'px;', 'class' => 'mb-2 bg-danger'), null);
  echo html_writer::tag('div', null, $fillAttributes);

  echo html_writer::end_tag('div');

  echo html_writer::tag('p', $fileHas . ' / ' . $totalRecords, array('class' => 'text-muted'));

  echo html_writer::end_tag('div');
  echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

// Generate the datatable
echo
'
  <div class="container-fluid">
    <main>       
      <table id="myTable" class="table mx-2 my-3" style="width: 100%;">
        <thead>
            <tr class="bg-primary text-white">
              <th class="text-center">Status</th>
              <th class="text-center">Text</th>
              <th class="text-center">Title</th>
              <th class="text-center">Outline</th>
              <th class="text-center">Language</th>
              <th class="text-center">Checked</th>
              <th>Course Information</th>
            </tr>
        </thead>
      </table>  
    </main>
  </div>
';

echo '
<style>
.dataTables_paginate,
.dataTables_info {
    font-size: 16px;
}
.dataTables_paginate a {
    padding: 10px;
}
.paginate_button.next, .paginate_button.previous {
    font-weight: bold;
    padding: 10px 16px;
}
.list-fix li {
border: 0;
margin: 0;
padding: 0;
}
</style>
';
echo $OUTPUT->footer();
