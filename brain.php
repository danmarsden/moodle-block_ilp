<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

define('AJAX_SCRIPT', true);

require_once('../../config.php');

require_login(0,false);

$fn=optional_param('fn',null,PARAM_RAW);

if($fn)
{
   $fn();
}
else
{
   print "";
}

function groups_in_course()
{
   global $USER;

   $id=optional_param('id',0,PARAM_INT);

   $r=array();
   foreach(groups_get_all_groups($id) as $group)
   {
      if(!$courseid or $courseid==$group->courseid)
      {
         $r[$group->id]=$group->name;
      }
   }

   print json_encode($r);
}