<?php
/* Function to build a where clasuse that will restrict the list of Help Desk
 * items to only those viewable by a user. The viewable items include
 * 1. Items the user created
 * 2. Items that are assigned to the user
 * 3. Items where the user is the requestor
 * 4. Items of a company you have permissions for
 */
function getItemPerms() {
  global $AppUI;

  $permarr = array();
  //pull in permitted companies
  $permarr[] = getCompanyPerms("item_company_id", "item_created_by", PERM_READ);
  //it's assigned to the current user
  $permarr[] = "item_assigned_to=".$AppUI->user_id;
  //it's requested by a user and that user is you
  $permarr[] = " (item_requestor_type=1 AND item_requestor_id=".$AppUI->user_id.') ' ;

  $sql = '('.implode("\n OR ", $permarr).')';

  return $sql;
}

// Function to build a where clause to be appended to any sql that will narrow
// down the returned data to only permitted company data
function getCompanyPerms($mod_id_field,$created_by_id_field,$perm_type,$the_company=NULL){
	GLOBAL $AppUI, $perms;

  // Check for the system wide "all" permission
  if (isset($perms["all"][PERM_ALL])) {
    if (($perms["all"][PERM_ALL] == PERM_READ) &&
        ($perm_type == PERM_READ)) {
      $get_all = true;
    } else if (($perms["all"][PERM_ALL] == PERM_EDIT)  &&
               (($perm_type == PERM_EDIT) || ($perm_type == PERM_READ))) {
      $get_all = true;
    }
  }
  
  // Check for company "all" permissions
  if (isset($perms['companies'][PERM_ALL])) {
    if (($perms['companies'][PERM_ALL] == PERM_READ) &&
        ($perm_type == PERM_READ)) {
      $get_all = true;
    } else if (($perms['companies'][PERM_ALL] == PERM_EDIT) &&
               (($perm_type == PERM_EDIT) || ($perm_type == PERM_READ))) {
      $get_all = true;
    }
	}

  if ($get_all) {
    $sql = "SELECT company_id FROM companies";
    $list = db_loadColumn( $sql );
  } else {
    $list = array();
  }

	if(isset($perms['companies'])){
		foreach($perms['companies'] as $key => $value){
			if($key==PERM_ALL)
				continue;

			switch($value){
				case PERM_EDIT:
          if (($perm_type == PERM_EDIT) || ($perm_type == PERM_READ))
	  		    $list[] = $key;
					break;
				case PERM_READ:
          if ($perm_type == PERM_READ)
	  		    $list[] = $key;
					break;
				case PERM_DENY:
					unset($list[array_search($key, $list)]);
					break;
				default:
					break;
			}
		}
	}

  if (is_numeric($the_company)) {
    $list[] = $the_company;
  }

	$list = array_unique($list);

  // If we're not allowed to see any company, let's make sure our SQL is ok
  if (!count($list)) {
    $list[] = "-1";
  }

  $sql = " ($mod_id_field in (".implode(",",$list).") ";
  
  if ($created_by_id_field != NULL) {
    $sql .= " OR $created_by_id_field=".$AppUI->user_id;
  }

  $sql .= ") ";

  return $sql;
}

function hditemReadable($hditem) {
  global $AppUI;

  $company_id = $hditem['item_company_id'];
  $created_by = $hditem['item_created_by'];

  $canReadCompany = !getDenyRead("companies", $company_id);

  if($canReadCompany || ($created_by == $AppUI->user_id)){
    return true;
  } else {
    return false;
  }
}

function hditemEditable($hditem) {
  global $HELPDESK_CONFIG, $AppUI;

  $company_id = $hditem['item_company_id'];
  $created_by = $hditem['item_created_by'];
  $assigned_to = $hditem['item_assigned_to'];
  $requested_by = $hditem['item_requestor_id'];

  /* Items can be edited by a user if
    1. He is the creator
    2. He is the assignee
    3. He is the requestor
  */
  if (($created_by == $AppUI->user_id) ||
      ($assigned_to == $AppUI->user_id) ||
      ($requested_by == $AppUI->user_id)) {
    return true;
  }

  // If the item is not assigned to a company, figure out who can access it
  if ($item_company_id == 0) {
    if ($HELPDESK_CONFIG['no_company_editable']) {
      $canEditCompany = 1;
    } else {
      $canEditCompany = 0;
    }
  } else {
    $canEditCompany = !getDenyEdit("companies", $item_company_id);
  }

  if (!hditemCreate()) {
    return false;
  } else if($canEditCompany){
    return true;
  } else {
    return false;
  }
}

function hditemCreate() {
  global $perms, $m;

  $create = FALSE;

  $canEditModule = !getDenyEdit( $m );

  if (!$canEditModule) {
    return $create;
  }

	if((isset($perms['companies'][PERM_ALL]) && ($perms['companies'][PERM_ALL]==PERM_EDIT)) || 
     (isset($perms["all"][PERM_ALL]) && ($perms["all"][PERM_ALL]==PERM_EDIT))) {
    $create = true;
  } else if (is_array($perms['companies'])) {
    foreach ($perms['companies'] as $perm) {
      if ($perm == PERM_EDIT) {
        $create = true;
        break;
      }
    }
  }

  return $create;
}

function dump ($var) {
  print "<pre>";
  print_r($var);
  print "</pre>";
}

/*
I think the built in strip_tags() does all this and more. Please tell me if
I'm wrong.
function stripHTML($data){
	$search_html= '/([<][^>]+[>])/';
	$data = preg_replace($search_html,'',$data);
	return $data;
}
*/

function linkLinks($data){
	$data = strip_tags($data);
	$search_email = '/([\w-]+([.][\w_-]+){0,4}[@][\w_-]+([.][\w-]+){1,3})/';
	$search_http = '/(http(s)?:\/\/[^\s]+)/i';
	$data = preg_replace($search_email,"<a href=\"mailto:$1\">$1</a>",$data);
	$data = preg_replace($search_http,"<a href=\"$1\" target=\"_blank\">$1</a>",$data);
	return $data;
}


?>
