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
	GLOBAL $AppUI, $perms, $m;

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
  if (isset($perms[$m][PERM_ALL])) {
    if (($perms[$m][PERM_ALL] == PERM_READ) &&
        ($perm_type == PERM_READ)) {
      $get_all = true;
    } else if (($perms[$m][PERM_ALL] == PERM_EDIT) &&
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

	if(isset($perms[$m])){
		foreach($perms[$m] as $key => $value){
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
  return hditemPerm($hditem, PERM_READ);
}

function hditemEditable($hditem) {
  return hditemPerm($hditem, PERM_EDIT);
}

function hditemPerm($hditem, $perm_type) {
  global $HELPDESK_CONFIG, $AppUI, $m;

  $created_by = $hditem['item_created_by'];
  $company_id = isset($hditem['item_company_id'])?$hditem['item_company_id']:'';
  $assigned_to = isset($hditem['item_assigned_to'])?$hditem['item_assigned_to']:'';
  $requested_by = isset($hditem['item_requestor_id'])?$hditem['item_requestor_id']:'';

  switch($perm_type) {
    case PERM_READ:
      $company_perm = !getDenyRead($m, $company_id);
      break;
    case PERM_EDIT:
      // If the item is not assigned to a company, figure out if we can edit it
      if ($company_id == 0) {
        if ($HELPDESK_CONFIG['no_company_editable']) {
          $company_perm = 1;
        } else {
          $company_perm = 0;
        }
      } else {
        $company_perm = !getDenyEdit($m, $company_id);
      }
      break;
    default:
      die ("Wrong permission type was passed");
  }

  /* User is allowed if
    1. He has the company permission
    2. He is the creator
    3. He is the assignee
    4. He is the requestor
  */
  if($company_perm ||
     ($created_by == $AppUI->user_id) ||
     ($assigned_to == $AppUI->user_id) ||
     ($requested_by == $AppUI->user_id)) {
    return true;
  } else {
    return false;
  }
}

function hditemCreate() {
  global $perms, $m;

  /* A user can create items only if he has write access to at least one
     company */
  $create = FALSE;

	if((isset($perms[$m][PERM_ALL]) && ($perms[$m][PERM_ALL]==PERM_EDIT)) || 
     (isset($perms["all"][PERM_ALL]) && ($perms["all"][PERM_ALL]==PERM_EDIT))) {
    $create = true;
  } else if (is_array($perms[$m])) {
    foreach ($perms[$m] as $perm) {
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

function linkLinks($data){
	$data = strip_tags($data);
	$search_email = '/([\w-]+([.][\w_-]+){0,4}[@][\w_-]+([.][\w-]+){1,3})/';
	$search_http = '/(http(s)?:\/\/[^\s]+)/i';
	$data = preg_replace($search_email,"<a href=\"mailto:$1\">$1</a>",$data);
	$data = preg_replace($search_http,"<a href=\"$1\" target=\"_blank\">$1</a>",$data);
	return $data;
}


?>
