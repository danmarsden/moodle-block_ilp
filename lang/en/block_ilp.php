<?php
	
	$string['addprompt'] 			= 	'Add Prompt';
	$string['addfield'] 			= 	'Add Field';
	$string['addpromptdots'] 		= 	'Add Prompt...'; 
	$string['apply'] 				= 	'Apply'; 
	$string['blockname'] 			= 	'ILP 2.0';
	$string['createreport']			=	'Create Report';
	$string['defaulthozsize'] 		= 	'Default number of columns';
	$string['defaulthozsizeconfig'] = 	'The default number of columns for all AJAX tables that do not override this setting.';
	$string['defaultverticalperpage'] = 'Default number of table rows';
	$string['defaultverticalperpageconfig'] = 'How many rows to show in the tables as the default for the vertical pagination';
	
	$string['description']			=	'Description';
	$string['display'] 				= 	'Display';
	$string['editreport']			=	'Edit Report';
	$string['fieldcreationsuc']		=	'The field was successfully created';
	$string['fieldmovesuc']			=	'The field was successfully moved';
	
	
	
	$string['label']				=	'Label';
	$string['name']					=	'Name';
	$string['notrequired']	 		= 	'Not required';
	$string['move']		 			= 'Move';
	$string['movedown'] 			= 'Move down';
	$string['moveleft'] 			= 'Move left';
	$string['moveleftone'] 			= 'Move left 1';
	$string['moveright'] 			= 'Move right';
	$string['moverightone'] 		= 'Move right 1';
	$string['movetoend'] 			= 'Move to end';
	$string['moveup']		 		= 'Move up';
	
	
	
	$string['perpage'] 				=	'per page';
	$string['pluginname'] 			= 	'ILP block';
	$string['preview'] 				= 	'Preview';
	$string['req'] 			= 	'Required';
	$string['reportcreationsuc'] 	= 	'The report was successfully created';
	
	$string['reportfields'] 		= 	'Report Fields';
	$string['required']	 			= 	'Required';
	
	
	
	$string['showingpages'] = 'Showing {$a->startpos} - {$a->endpos} of {$a->total}';
	$string['submitanddisplay'] 	= 	'Submit and display';
	$string['type'] 				= 	'Type';
	
	
	
	
	

	
	
	//ERROR MESSAGES CHANGING THESE IS NOT RECOMMENDED
	$string['reportcreationerror'] 			= 	'A error occurred whilst creating the report';
	$string['fieldcreationerror'] 			= 	'A error occurred whilst creating the field';
	$string['fieldmoveerror'] 				= 	'A has error occurred the field was not moved';
	$string['formelementdeleteerror']		= 	'';
	

	
	global $CFG;

	// Include ilp db class
	require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');
	
	$dbc = new ilp_db();
	$plugins = $CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins';
	
	// get all the currently installed form element plugins
	$form_element_plugins = ilp_records_to_menu($dbc->get_form_element_plugins(), 'id', 'name');
	
	//this section gets language strings for all plugins
	foreach ($form_element_plugins as $plugin_file) {
		
	    if (file_exists($plugins.'/'.$plugin_file.".php")) 
	    {
	        require_once($plugins.'/'.$plugin_file.".php");
	        // instantiate the object
	        $class = basename($plugin_file, ".php");
	        $resourceobj = new $class();
	        $method = array($resourceobj, 'language_strings');

	        
	        //check whether the language string element has been defined
	        if (is_callable($method,true)) {
	            $resourceobj->language_strings($string);
	        }
	    }
	}