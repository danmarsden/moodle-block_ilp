<?php
/**
 * Library of assorted functions for the ILP
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */



function var_crap($var,$header="") {
	echo "<pre> {$header} <br />";
	var_dump($var);
	echo "</pre>";
	
}


/**
 * Generates a random number for when something needs to be identified
 * uniquely.
 *
 * @return int A random number
 */
function uniqueNum() {
    return rand().time();
}

/**
 * Adds a record of an action to the log
 *
 * @param int $course_id
 * @param string $log_action the generic short name for the event
 * @param string $log_url (optional, defaults to ASSMGR_LOG_URL_PREFIX)
 * @param string $log_info Detailed explanation of what has happened
 * @return void
 */
function ilp_add_to_log($course_id, $log_action, $log_url, $log_info) {

    // prepend the url prefix if the log_url is not empty
    $log_url = empty($log_url) ? '' : ILP_LOG_URL_PREFIX.'/'.$log_url;

    // add to the moodle log
    add_to_log($course_id, ILP_LOG_MODULE, $log_action, $log_url, $log_info);
}

/**
 * Utility function which makes a recordset into an array
 * Similar to recordset_to_menu. Array is keyed by the specified field of each record and
 * either has the second specified field as the value, or the results of the callback function which
 * takes the second field as it's first argument
 *
 * field1, field2 is needed because the order from get_records_sql is not reliable
 * @param records - records from get_records_sql() or get_records()
 * @param field1 - field to be used as menu index
 * @param field2 - feild to be used as coresponding menu value
 * @param string $callback (optional) the name of a function to call in order ot generate the menu item for each record
 * @param string $callbackparams (optional) the extra parameters for the callback function
 * @return mixed an associative array, or false if an error occured or the RecordSet was empty.
 */
function ilp_records_to_menu($records, $field1, $field2, $callback = null, $callbackparams = null) {

    $menu = array();

    if(!empty($records)) {
        foreach ($records as $record) {
            if(empty($callback)) {
                $menu[$record->$field1] = $record->$field2;
            } else {
                // array_unshift($callbackparams, $record->$field2);
                $menu[$record->$field1] = call_user_func_array($callback,array($record->$field2,$callbackparams));
            }
        }

    }
    return $menu;
}


/**
 * Removes any resources from the given array that are disabled in either the global or instance config.
 *
 * @param array $resources the array containing the resources
 * @param int $userid optional id of the user. If 0 then $USER->id is used.
 * @param bool $return optional defaults to false. If true the list is returned rather than printed
 * @return string HTML
 */
function assmgr_remove_disbaled_resources($resources,$course_id=null) {

    $dbc = new assmgr_db();

    $instance_config  = (array) $dbc->get_instance_config($course_id);

    $resource_array = array();
    $resource_temp = array();

    $globalconfig = get_config('block_assmgr');

     foreach($globalconfig as $setting => $value) {
         if(substr($setting, 0, 16) == 'assmgr_resource_') {
              foreach ($resources as $resource) {
                   if ($setting == $resource->name && !empty($value)) {
                        array_push($resource_temp,$resource);
                   }
              }
         }
     }
    if (!empty($instance_config)) {
        foreach ($resource_temp as $resource) {
            if (isset($instance_config[$resource->name])) {
                if (!empty($instance_config[$resource->name])) {
                     array_push($resource_array,$resource);
                }
            }

        }
    } else {
        $resource_array = $resource_temp;
    }

    return $resource_array;
}


/**
 * Wrapper for native ilp_build_navigation() function that truncates the length of
 * each of the breadcrumbs to ensure that they all fit neatly on the page
 */
function ilp_build_navigation($breadcrumbs) {

    // determine the total length of all the breadcrumbs
    $length = 0;
    foreach($breadcrumbs as $crumb) {
        $length += strlen($crumb['name']);
    }

    // if it too long then we need to truncate
    if($length > MAXLENGTH_BREADCRUMB) {
        // calculate the per crumb limit
        $limit = round(MAXLENGTH_BREADCRUMB/count($breadcrumbs));
        // enforce it
        foreach($breadcrumbs as $id => $crumb) {
            $breadcrumbs[$id]['name'] = limit_length($crumb['name'], $limit);
        }
    }

    return build_navigation($breadcrumbs);
}

/**
 * uninstalls all resource tables
 *
 */
function uninstall_resources()  {
    global $CFG, $DB;
    require_once($CFG->dirroot."/blocks/assmgr/db/assmgr_db.php");
    $dbc = new assmgr_db();

    $resource_tables    =   $dbc->get_resource_types();

    if (!empty($resource_tables)) {
        foreach ($resource_tables as $resource_t) {
            // include the class for this type of evidence
            if (!empty($resource_t->name)) {

                if (file_exists($CFG->dirroot."/blocks/assmgr/classes/resources/plugins/{$resource_t->name}.php")) {
                    @include_once($CFG->dirroot."/blocks/assmgr/classes/resources/plugins/{$resource_t->name}.php");
                    $resource = new $resource_t->name();

                    if (!empty($resource))  {
                        $resource->uninstall();
                    }
                }
            }
        }
    }
}



/**
 * Lock the portfolio is possible, or throw an error if not.
 *
 * @param int $portfolio_id
 * @param object $dbc recycled database access object. Saves memory compared to making a new one
 * @return void will lock the portfoilo or throw an exception
 */
// is the current portfolio locked?
function lock_portfolio_if_possible($portfolio_id) {

    global $USER, $CFG;

    //include assessment manager db class
    require_once($CFG->dirroot."/blocks/assmgr/db/assmgr_db.php");
    $dbc = new assmgr_db();

    if($dbc->lock_exists($portfolio_id)) {
        // renew the lock if it belongs to the current user
        if($dbc->lock_exists($portfolio_id, $USER->id)) {
            $dbc->renew_lock($portfolio_id, $USER->id);
        } else {
            // otherwise throw an error
            print_error('portfolioinuse', 'block_assmgr');
        }
    } else {
        // create a new lock
        $dbc->create_lock($portfolio_id, $USER->id);
    }
}


/**
 * Truncates long strings and adds a tooltip with a longer verison.
 *
 * @param string $string The string to truncate
 * @param int $maxlength The maximum length the string can be. -1 means unlimited, in case you just want a tooltip
 * @param string $tooltip (optional) tooltip to display. defaults to $string
 * @param array $special_case (optional) array of characters/entities that if found in string
 *              stop the truncation and deceoding
 * @return string HTML
 */
function limit_length($html, $maxlength, $tooltip = null) {

    // permit only html tags and quotes so we can parse the tags properly
    $html = ilp_db::decode_htmlchars(assmgr_db::encode($html));

    $printedlength = 0;
    $position = 0;
    $tags = array();

    $return = null;

    while ($printedlength < $maxlength && preg_match('{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $html, $match, PREG_OFFSET_CAPTURE, $position)) {

        list($tag, $tagPosition) = $match[0];

        // print text leading up to the tag
        $str = substr($html, $position, $tagPosition - $position);
        if ($printedlength + strlen($str) > $maxlength) {
            $return .= (substr($str, 0, $maxlength - $printedlength));
            $printedlength = $maxlength;
            break;
        }

        $return .= ($str);
        $printedlength += strlen($str);

        if ($tag[0] == '&') {
            // handle the entity
            $return .= ($tag);
            $printedlength++;
        } else {
            // handle the tag
            $tagName = $match[1][0];
            if ($tag[1] == '/') {
                // this is a closing tag

                $openingTag = array_pop($tags);
                assert($openingTag == $tagName); // check that tags are properly nested

                $return .= ($tag);
            } else if ($tag[strlen($tag) - 2] == '/') {
                // self-closing tag
                $return .= ($tag);
            } else {
                // opening tag
                $return .= ($tag);
                $tags[] = $tagName;
            }
        }

        // continue after the tag
        $position = $tagPosition + strlen($tag);
    }

    // print any remaining text
    if ($printedlength < $maxlength && $position < strlen($html)) {
        $return .= (substr($html, $position, $maxlength - $printedlength));
    }

    // add the ellipsis, if truncated
    $return .= (strip_tags($return) != strip_tags($html)) ? '&hellip;' : null;

    // close any open tags
    while (!empty($tags)) {
        $return .= sprintf('</%s>', array_pop($tags));
    }

    // don't show a tooltip if it's set to false, or if no truncate has been done
    if($tooltip === false || ($return == $html && empty($tooltip))) {
        return $return;
    } else {

        // make the tooltip the original string if a specific value was not set
        if(empty($tooltip)) {
            $tooltip = $html;
        }

        $tooltip = ilp_db::encode($tooltip);

        // generate the unique id needed for the YUI tooltip
        $id = 'tootlip'.uniqueNum();

        $script = "<script type='text/javascript'>
                       //<![CDATA[
                       new YAHOO.widget.Tooltip('ttA{$id}', {
                           context:'{$id}',
                           effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.20}
                       });
                       //]]>
                   </script>";

        return "<span id='{$id}' class='tooltip' title='{$tooltip}'>{$return}</span>{$script}";
    }
}