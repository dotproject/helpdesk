<?php /* HELPDESK $Id: view.php 265 2006-12-14 18:06:35Z kang $ */

if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

$df = $AppUI->getPref( 'SHDATEFORMAT' );
$tf = $AppUI->getPref( 'TIMEFORMAT' );
$format = $df." ".$tf;

$item_id = dPgetParam( $_GET, 'item_id', 0 );
$helpdesk_id = $item_id;
$task_log_id = intval(dPgetParam($_GET, 'task_log_id', 0)); 

// Get pagination page
if (isset($_GET['page'])) {
  $AppUI->setState('HelpDeskLogPage', $_GET['page']);
} else {
  $AppUI->setState('HelpDeskLogPage', 0);
}

$page = $AppUI->getState('HelpDeskLogPage') ? $AppUI->getState('HelpDeskLogPage') : 0;

// Get tab state
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpLogVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpLogVwTab' ) !== NULL ? $AppUI->getState( 'HelpLogVwTab' ) : 0;

// Pull data
$q = new DBQuery; 
$q->addQuery('hi.*,CONCAT(co.contact_first_name,\' \',co.contact_last_name) assigned_fullname,
             co.contact_email as assigned_email,
             p.project_id,p.project_name,p.project_color_identifier,
             c.company_name');
$q->addTable('helpdesk_items','hi');
$q->addJoin('users','u','u.user_id = hi.item_assigned_to');
$q->addJoin('contacts','co','u.user_contact = co.contact_id');
$q->addJoin('projects','p','p.project_id = hi.item_project_id');
$q->addJoin('companies','c','c.company_id = hi.item_company_id');
$q->addWhere('item_id = ' . $item_id);

$hditem = $q->loadHash();
if (!$hditem ) {

	$titleBlock = new CTitleBlock( $AppUI->_('Invalid item id'), 'helpdesk.png', $m, 'ID_HELP_HELPDESK_VIEW' );
	$titleBlock->addCrumb( "?m=helpdesk", 'home' );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list' );
	$titleBlock->show();
} else {
  // Check permissions on this record

  $canRead = hditemReadable($hditem);
  //ANDY $canEdit = hditemEditable($hditem);
  //$canEdit = $perms->checkModule($m, 'edit') && hditemEditable($hditem);  


  if ($hditem["item_status"] == $HELPDESK_CONFIG['closed_status_id']) {
      $issue_closed = true;
  } else {
      $issue_closed = false;
  }

  if(!$canRead && !$canEdit){
	  $AppUI->redirect( "m=public&a=access_denied" );
  }

  $name = $hditem['item_requestor'];
  $assigned_to_name = $hditem['item_assigned_to'] ? $hditem['assigned_fullname'] : '';
  $assigned_email = $hditem['assigned_email'];

  $q = new DBQuery; 
  $q->addQuery('helpdesk_item_watchers.user_id, 
                CONCAT(contact_first_name, \' \', contact_last_name) as name,	contact_email');
  $q->addTable('helpdesk_item_watchers');
  $q->addJoin('users','','helpdesk_item_watchers.user_id = users.user_id');
  $q->addJoin('contacts','','user_contact = contact_id');
  $q->addWhere('item_id = ' . $item_id);
  $q->addOrder('contact_last_name, contact_first_name');
  $watchers = $q->loadList();



  $titleBlock = new CTitleBlock( 'Viewing Issue Item', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );
  if (hditemCreate()) {
    $titleBlock->addCell(
      '<input type="submit" class="button" value="'.$AppUI->_('New Item').'" />', '',
      '<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
    );
  }

	$titleBlock->addCrumb( "?m=helpdesk", 'home');
	$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list');
	if ($canEdit) {
		// Deletion allowed for new or closed items only
		if ((@$hditem["item_status"] == 0) || $issue_closed)
			$titleBlock->addCrumbDelete('delete this item', 1);

		// TODO: Evaluate a better way of controling editing and exporting
		$titleBlock->addCrumb( "?m=helpdesk&a=addedit&item_id=$item_id", 'edit this item' );
		if (!$issue_closed || $AppUI->user_type == 1) {
			$titleBlock->addCrumb( "?m=helpdesk&a=export&item_id=$item_id", 'export this item to task' );
		}
	}

	$titleBlock->show();
?>
  <script language="JavaScript">
  function delIt() {
    if (confirm( "<?php print $AppUI->_('doDelete').' '.$AppUI->_('Item').'?';?>" )) {
      document.frmDelete.submit();
    }
  }

  function toggle_comment(id){
     var element = document.getElementById(id)
     element.style.display = (element.style.display == '' || element.style.display == "none") ? "inline" : "none"
  }
  </script>

  <table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td valign="top">
  <table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
  <tr>
    <td valign="top" width="50%" colspan="2">
      <strong><?php echo $AppUI->_('Item Details')?>  </strong>
       <?php if(($hditem["item_deadline"]!=NULL) && (!strcmp($hditem["item_deadline"], "0000-00-00 00:00:00")==0)) {
      		$date3 = new CDate( $hditem['item_deadline'] );
		echo "<a title='Deadline: ".$date3->format($format)."'>(";
		echo get_due_time($hditem["item_deadline"]);
		echo ")</a>";
	}
       ?>
     </td>   
  </tr>
  <tr>
    <td valign="top">
      <table cellspacing="1" cellpadding="2" width="100%">
      <tr>
        <!--KZHAO  8-3-2006-->
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Item Created')?>:</td>
	<td class="hilite" width="100%"><?php
	if($hditem["item_created"]!=NULL){
		$date1 = new CDate( $hditem['item_created'] );
	  	echo $date1->format($format);
	    }
	 else echo "N/A";
	 
	   ?>
		(<font color="#ff0000"><?php 
			if($hditem["item_created"]!=NULL) { 
				echo get_time_ago($hditem["item_created"]);
			}
			else echo "N/A";			
			?>
		</font>)
	</td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Number')?>:</td>
        <td class="hilite" width="100%"><?php echo $hditem["item_id"]?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Title')?>:</td>
        <td class="hilite" width="100%"><?php echo $hditem["item_title"]?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Requestor')?>:</td>
        <td class="hilite" width="100%"><?php
          print $hditem["item_requestor_email"] ? 
            "<a href=\"mailto:".$hditem["item_requestor_email"]."\">".$hditem['item_requestor']."</a>" :
            $hditem['item_requestor'];?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Requestor Phone')?>:</td>
        <td class="hilite" width="100%"><?php echo $hditem["item_requestor_phone"]?></td>
      </tr>
      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Assigned To')?>:</td>
        <td class="hilite" width="100%"><?php
          print $assigned_email ?
            "<a href=\"mailto:$assigned_email\">$assigned_to_name</a>" :
            $assigned_to_name;?></td>
      </tr>
      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Company')?>:</td>
        <td class="hilite" width="100%"><?php echo $hditem["company_name"]?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project')?>:</td>
        <td class="hilite" width="100%" style="background-color: #<?php echo $hditem['project_color_identifier']?>;"><a href="./index.php?m=projects&a=view&project_id=<?php echo $hditem["project_id"]?>"; style="color: <?php echo  bestColor( $hditem['project_color_identifier'] ) ?>;"><?php echo $hditem["project_name"]?></a></td>
      </tr>
    </table>
    </td><td valign="top">
    <table cellspacing="1" cellpadding="2" width="100%">
      
      <tr>
        <!--KZHAO  8-7-2006-->
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Item Updated')?>:</td>
        <td class="hilite" width="100%">
	<?php if($hditem["item_updated"]!=NULL){
		$date2 = new CDate( $hditem['item_updated'] );
	  	echo $date2->format($format);
	    }
	   elseif($hditem["item_modified"]!=NULL){
	        $date2 = new CDate( $hditem['item_modified'] );
	        echo $date2->format($format);
	    }
	   else echo "Unknown";
	   
	   ?>
		(<font color="#ff0000"><?php 
			if($hditem["item_updated"]!=NULL) { 
				echo get_time_ago($hditem["item_updated"]);
			}
			elseif($hditem["item_modified"]!=NULL){
				echo get_time_ago($hditem["item_modified"]);
			}
			else echo "N/A";			
			?></font>)
	</td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Issue Type')?>:</td>
        <td class="hilite" width="100%"><?php
          print $AppUI->_($ict[$hditem["item_calltype"]])." ";
          print dPshowImage (dPfindImage( 'ct'.$hditem["item_calltype"].'.png', $m ), 15, 17, 'align=center');
        ?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Source')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$ics[$hditem["item_source"]])?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Status')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$ist[$hditem["item_status"]])?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Priority')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$ipr[$hditem["item_priority"]])?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Severity')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$isv[$hditem["item_severity"]])?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Operating System')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$ios[$hditem["item_os"]])?></td>
      </tr>

      <tr>
        <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Application')?>:</td>
        <td class="hilite" width="100%"><?php echo $AppUI->_(@$iap[$hditem["item_application"]])?></td>
      </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td valign="top" colspan="2">
      
      <table cellspacing="0" cellpadding="2" border="0" width="100%">
      <tr>
        <td><strong><?php echo $AppUI->_('Summary')?></strong></td>
        <td><strong><?php echo $AppUI->_('Watchers')?></strong></td>
      <tr>
        <td class="hilite" width="50%"><?php echo str_replace( chr(10), "<br />", linkLinks($hditem["item_summary"]))?>&nbsp;</td>
        <td class="hilite" width="50%"><?php
		$delimiter = "";
		foreach($watchers as $watcher){
			echo "$delimiter <a href=\"mailto: {$watcher['contact_email']}\">".$watcher['name']."</a>";
			$delimiter = ",";
		}
        ?>&nbsp;</td>
      </tr>
      
      </table>

    </td>
  </tr>
  </table>
  </td></tr>
  <tr><td valign="top">
  <?php 

  $tabBox = new CTabBox( "?m=helpdesk&a=view&item_id=$item_id", "", $tab );
  $tabBox->add( dPgetConfig('root_dir') . '/modules/helpdesk/vw_logs', 'Task Logs' );

  if ($canEdit) {

	//ANDY: rejected, closed - no more logs
	if (!$issue_closed)
	    $tabBox->add( dPgetConfig('root_dir') . '/modules/helpdesk/vw_log_update', 'New Log' );
  }
  $tabBox->add( dPgetConfig('root_dir') . '/modules/helpdesk/vw_history', 'Item History' );

  //Set the project_id variable so the tab view has the info
  $project_id = $hditem["project_id"];
  $tabBox->add( dPgetConfig('root_dir') . '/modules/helpdesk/helpdesk_tab.view.files', 'Files' );

  $tabBox->show();

} 
?>
</td></tr></table>

<form name="frmDelete" action="./index.php?m=helpdesk&a=list" method="post">
  <input type="hidden" name="dosql" value="do_item_aed">
  <input type="hidden" name="del" value="1" />
  <input type="hidden" name="item_id" value="<?php echo $item_id?>" />
</form>
