<?php

function getAllowedUsers(){
  global $HELPDESK_CONFIG;
	//populate user list with all users from permitted companies
	$sql = "SELECT user_id, CONCAT(contact_last_name, ',', contact_first_name)
			FROM users
		   LEFT JOIN contacts ON user_contact = contact_id
			WHERE ". getCompanyPerms("user_company", PERM_EDIT, $HELPDESK_CONFIG['the_company'])
			." OR ". getCompanyPerms("contact_company", PERM_EDIT, $HELPDESK_CONFIG['the_company'])
		 . "ORDER BY contact_last_name, contact_first_name";
	$users = db_loadHashList( $sql );
	return $users;
}
	
function getAllowedCompanies(){
  global $AppUI;
  require_once( $AppUI->getModuleClass ('companies' ) );
  $company = new CCompany();

  $allowedCompanies = $company->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
  //$allowedCompanies = arrayMerge( array( '0'=>'' ), $allowedCompanies );
  return $allowedCompanies;
}

function getAllowedProjects(){
  global $AppUI, $HELPDESK_CONFIG;
  //if helpdeskUseProjectPerms is true, get a list of Projects based on the users standard project permissions
  if($HELPDESK_CONFIG['helpdeskUseProjectPerms']){
	  require_once( $AppUI->getModuleClass('projects'));
	  $project = new CProject;
	  $allowedProjects = $project->getAllowedRecords($AppUI->user_id, 'project_id, project_name','project_name');
	//echo "!".implode(" AND ",$rowproject>getAllowedSQL( $AppUI->user_id))."!";
	  return $allowedProjects;
  } else {
  //otherwise, get a list of all projects associated with the user's permitted companies.
  //the use case here would be that the person assigning or updating the Helpdesk item may not have access to all Projects.  They might just be traffic control.  This will minimise perm maintenance.
  	$sql = "SELECT project_id, project_name FROM projects WHERE project_company in (". implode(",",array_keys(getAllowedCompanies())).") ORDER BY project_name";
  	return db_loadList( $sql );
  }
}

function getAllowedProjectsForJavascript(){
  global $AppUI;
  $allowedProjects = getAllowedProjects();
  //if there are none listed, make sure that sql returns nothing
  if(!$allowedProjects){
  	return "";
  }

  foreach($allowedProjects as $p){
	$whereclause[] = $p['project_id'];
  }
  
  $whereclause = "project_id in (".implode(", ", $whereclause).")";

  $sql = "SELECT project_id, project_name, company_name, company_id
          FROM projects
          LEFT JOIN companies ON company_id = projects.project_company
          WHERE (". $whereclause
       . ") ORDER BY project_name";

  $allowedCompanyProjectList = db_loadList( $sql );


  /* Build array of company/projects for output to javascript
     Adding slashes in case special characters exist */
  foreach($allowedCompanyProjectList as $row){
    $projects[] = "[{$row['company_id']},{$row['project_id']},'"
                . addslashes($row['project_name'])
                . "']";
    $reverse[$row['project_id']] = $row['company_id'];
  }
  return $projects;
}

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
  $allowedCompanies = getAllowedCompanies();

  //if there are none listed, make sure that sql returns nothing
  if(!$allowedCompanies){
  	return "0=1";
  }
  
  foreach($allowedCompanies as $k=>$v){
    $companyIds[] = $k;
  }
  $companyIds = implode(",", $companyIds);
  $permarr[] = "(item_company_id in ("
               .$companyIds
               .")  OR item_created_by="
               .$AppUI->user_id
               .") ";
  //it's assigned to the current user
  $permarr[] = "item_assigned_to=".$AppUI->user_id;
  //it's requested by a user and that user is you
  $permarr[] = " (item_requestor_type=1 AND item_requestor_id=".$AppUI->user_id.') ' ;

  $sql = '('.implode("\n OR ", $permarr).')';

  return $sql;
}

// Function to build a where clause to be appended to any sql that will narrow
// down the returned data to only permitted company data
function getCompanyPerms($mod_id_field,$perm_type=NULL,$the_company=NULL){
	GLOBAL $AppUI, $perms, $m;
	
  //pull in permitted companies
  $allowedCompanies = getAllowedCompanies();

  //if there are none listed, make sure that sql returns nothing
  if(!$allowedCompanies){
  	return "0=1";
  }
 
  $allowedCompanies = array_keys($allowedCompanies);

  if (is_numeric($the_company)) {
    $allowedCompanies[] = $the_company;
  }
  return "($mod_id_field in (".implode(",", $allowedCompanies)."))";
}

function hditemReadable($hditem) {
  return hditemPerm($hditem, PERM_READ);
}

function hditemEditable($hditem) {
  return hditemPerm($hditem, PERM_EDIT);
}

function hditemPerm($hditem, $perm_type) {
  global $HELPDESK_CONFIG, $AppUI, $m;

  $perms = & $AppUI->acl();
  $created_by = $hditem['item_created_by'];
  $company_id = isset($hditem['item_company_id'])?$hditem['item_company_id']:'';
  $assigned_to = isset($hditem['item_assigned_to'])?$hditem['item_assigned_to']:'';
  $requested_by = isset($hditem['item_requestor_id'])?$hditem['item_requestor_id']:'';

  switch($perm_type) {
    case PERM_READ:
      $company_perm = $perms->checkModuleItem('companies', 'view', $company_id);
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
      $company_perm = $perms->checkModuleItem('companies', 'view', $company_id);
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
  global $m, $AppUI;

  $perms = & $AppUI->acl();
  if ($perms->checkModule($m, 'add'))
        return true;

  return false;
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
