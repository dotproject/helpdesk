<?php /* PROJECTS $Id: reports.php,v 1.2 2007/06/30 15:27:11 caseydk Exp $ */

if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

require_once( $AppUI->getModuleClass( 'companies' ) );
require_once( $AppUI->getModuleClass( 'projects' ) );

$company_id = intval( dPgetParam( $_REQUEST, "company_id", 0 ) );
$project_id = intval( dPgetParam( $_REQUEST, "project_id", 0 ) );
$report_type = dPgetParam( $_REQUEST, "report_type", '' );

// check permissions for this record
$perms =& $AppUI->acl();

$canRead = $perms->checkModule( $m, 'view' );
if (!$canRead) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$obj = new CCompany();
$q = new DBQuery;
$q->addTable('companies');
$q->addQuery('company_id, company_name, company_description');                     
$q->addGroup('company_id');
$q->addOrder('company_name');
$obj->setAllowedSQL($AppUI->user_id, $q);

$company_list=array("0"=> $AppUI->_("All", UI_OUTPUT_RAW) );
$ptrc = $q->exec();
$nums=db_num_rows($ptrc);
echo db_error();
for ($x=0; $x < $nums; $x++) {
        $row = db_fetch_assoc( $ptrc );
        if ($row["company_id"] == $company_id) $display_company_name=$row["company_name"];
        $company_list[$row["company_id"]] = $row["company_name"];
}
$q->clear();


$obj = new CProject();
$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_status, project_name, project_description, project_short_name');
if ($company_id) {
	$q->addWhere('project_company = '.$company_id);
}
$q->addGroup('project_id');
$q->addOrder('project_short_name');
$obj->setAllowedSQL($AppUI->user_id, $q);

$project_list=array("0"=> $AppUI->_("All", UI_OUTPUT_RAW) );
$ptrc = $q->exec();
$nums=db_num_rows($ptrc);
echo db_error();
for ($x=0; $x < $nums; $x++) {
        $row = db_fetch_assoc( $ptrc );
        if ($row["project_id"] == $project_id) $display_project_name='('.$row["project_short_name"].') '.$row["project_name"];
        $project_list[$row["project_id"]] = '('.$row["project_short_name"].') '.$row["project_name"];
}
$q->clear();

if (! $suppressHeaders) {
?>
<script language="javascript">
                                                                                
function changeIt(obj) {
        var f=document.changeMe;
        if (obj.name == "company_id") {
        	if (obj.value>0) {
        		f.company_id.value = obj.value;
        	}
        }
        if (obj.name == "project_id") {
        	if (obj.value>0) {
        		f.project_id.value = obj.value;
        	}
        }
        f.submit();
}
</script>

<?php
}
// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

$reports = $AppUI->readFiles( dPgetConfig( 'root_dir' )."/modules/$m/reports", "\.php$" );

// setup the title block
if (! $suppressHeaders) {
	$titleBlock = new CTitleBlock( 'Issue Management Reports', 'helpdesk.png', $m, "$m.$a" );
	$titleBlock->addCrumb( "?m=helpdesk", 'home' );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "list" );
	if ($report_type) {
		$titleBlock->addCrumb( "?m=helpdesk&a=reports&project_id=$project_id", "reports index" );
	}
	$titleBlock->show();
}

$report_type_var = dPgetParam($_GET, 'report_type', '');
if (!empty($report_type_var))
	$report_type_var = '&report_type=' . $report_type;

if (! $suppressHeaders) {
if (!isset($display_company_name)) {
	if (!isset($display_project_name)) {
		$display_project_name = "None";
	} else {
		echo $AppUI->_('Selected Project') . ": <b>".$display_project_name."</b>"; 
		$display_company_name = "None";
	}
} else {
	echo $AppUI->_('Selected Company') . ": <b>".$display_company_name."</b>";
}
?>
<form name="changeMe" action="./index.php?m=helpdesk&a=reports<?php echo $report_type_var; ?>" method="post">
<table>
<tr>
<td><?php echo $AppUI->_('Companies') . ':';?></td>
<td><?php echo arraySelect( $company_list, 'company_id', 'size="1" class="text" onchange="changeIt(this);"', $company_id, false );?></td>
</tr>
<tr>
<td><?php echo $AppUI->_('Projects') . ':';?></td>
<td><?php echo arraySelect( $project_list, 'project_id', 'size="1" class="text" onchange="changeIt(this);"', $project_id, false );?></td>
</tr>
</form>

<?php
}
if ($report_type) {
	$report_type = $AppUI->checkFileName( $report_type );
	$report_type = str_replace( ' ', '_', $report_type );
	require "$baseDir/modules/$m/reports/$report_type.php";
} else {
	echo "<table>";
	echo "<tr><td><h2>" . $AppUI->_( 'Reports Available' ) . "</h2></td></tr>";
	foreach ($reports as $v) {
		$type = str_replace( ".php", "", $v );
		$desc_file = str_replace( ".php", ".{$AppUI->user_locale}.txt", $v );
		$desc = @file( "$baseDir/modules/$m/reports/$desc_file");

		echo "\n<tr>";
		echo "\n	<td><a href=\"index.php?m=$m&a=reports&project_id=$project_id&report_type=$type";
		if (isset($desc[2]))
			echo "&" . $desc[2];
		echo "\">";
		echo @$desc[0] ? $desc[0] : $v;
		echo "</a>";
		echo "\n</td>";
		echo "\n<td>" . (@$desc[1] ? "- $desc[1]" : '') . "</td>";
		echo "\n</tr>";
	}
	echo "</table>";
}
?>
