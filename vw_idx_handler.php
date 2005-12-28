<?php /* HELPDESK $Id: vw_idx_handler.php,v 1.22 2005/10/07 16:09:30 pedroix Exp $*/

  /*
   * opened = 0
   * closed = 1
   * mine = 2
   */
function vw_idx_handler ($type) {
  global $m, $ipr, $ist, $AppUI;

  $where = $date_field_name = $date_field_title = "";

  switch($type){
  	case 0:// Opened
  		$date_field_title = $AppUI->_('Opened On');
  		$date_field_name = "item_created";
		  $where .= "(TO_DAYS(NOW()) - TO_DAYS(hi.item_updated) = 0)
		             AND his.status_code = 0";
  		break;
  	case 1:// Closed
  		$date_field_title = $AppUI->_('Closed On');
  		$date_field_name = "status_date";
		$where .= "item_status=2
        	     AND status_code=11
        	     AND (TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)";
  		break;
  	case 2: // Mine
  		$date_field_title = $AppUI->_('Opened On');
  		$date_field_name = "item_created";
      		$where .= "item_assigned_to={$AppUI->user_id}
                 AND item_status !=2
                 AND his.status_code = 0";
  		break;
  	default:
      print "Shouldn't be here (for now)";
      exit(1);
  }

  $df = $AppUI->getPref( 'SHDATEFORMAT' );
  $tf = $AppUI->getPref( 'TIMEFORMAT' );
  $format = $df." ".$tf;

  /*
   * Unassigned = 0
   * Open = 1
   * Closed = 2
   * On hold = 3
   * Delete = 4
   * Testing = 5
   */
   
  $item_perms = getItemPerms();

  $sql = "SELECT hi.*,
          CONCAT(co.contact_first_name,' ',co.contact_last_name) assigned_fullname,
          co.contact_email as assigned_email,
          p.project_id,
          p.project_name,
          p.project_color_identifier,
          his.status_date sd
          FROM helpdesk_items hi
          LEFT JOIN helpdesk_item_status his ON his.status_item_id = hi.item_id
          LEFT JOIN users u ON u.user_id = hi.item_assigned_to
          LEFT JOIN contacts co ON co.contact_id = u.user_contact
          LEFT JOIN projects p ON p.project_id = hi.item_project_id
          WHERE $where
          AND $item_perms
          GROUP BY item_id
          ORDER BY item_id";

  $items = db_loadList( $sql );

  ?>
  <table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
  <tr>
    <th><?php echo $AppUI->_('Number'); ?></th>
    <th><?php echo $AppUI->_('Requestor'); ?></th>
    <th><?php echo $AppUI->_('Title'); ?></th>
    <th nowrap="nowrap"><?php echo $AppUI->_('Assigned To'); ?></th>
    <th><?php echo $AppUI->_('Status'); ?></th>
    <th><?php echo $AppUI->_('Priority'); ?></th>
    <th><?php echo $AppUI->_('Updated'); ?></th>
    <th><?php echo $AppUI->_('Project'); ?></th>
    <th nowrap="nowrap"><?php echo $date_field_title?></th>
  </tr>
  <?php
  foreach ($items as $row) {
    /* We need to check if the user who requested the item is still in the
       system. Just because we have a requestor id does not mean we'll be
       able to retrieve a full name */

    if ($row[$date_field_name]) {
      $date = new CDate( $row[$date_field_name] );
      $tc = $date->format( $format );
    } else {
      $tc = ' ';
    }
    if ($row['status_date']) {
      $datesd = new CDate( $row['status_date'] );
      $sd = $datesd->format( $format );
    } else {
      $sd = ' ';
    }

    ?>
    <tr>
      <td nowrap="nowrap"><b><a href="?m=helpdesk&a=view&item_id=<?php echo $row['item_id']?>"><?php echo $row['item_id']?></a></b>
          <?php echo dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, ''); ?></td>
      <td nowrap=\"nowrap\">
      <?php
      if ($row["item_requestor_email"]) {
        print "<a href=\"mailto: ".$row["item_requestor_email"]."\">".$row['item_requestor']."</a>";
      } else {
        print $row['item_requestor'];
      }
      ?>
      </td>
      <td width="80%"><a href="?m=helpdesk&a=view&item_id=<?php echo $row['item_id']?>"><?php echo $row['item_title']?></a></td>
      <td align="center" nowrap="nowrap">
      <?php
      if ($row['assigned_email']) {
        print "<a href='mailto:{$row['assigned_email']}'>{$row['assigned_fullname']}</a>";
      } else {
        print $row['assigned_fullname'] ? $row['assigned_fullname'] : "-";
      }
      ?>
      </td>
      <td align="center" nowrap><?php echo $AppUI->_($ist[@$row["item_status"]]); ?></td>
      <td align="center" nowrap><?php echo $AppUI->_($ipr[@$row["item_priority"]]); ?></td>
      <td align="center" nowrap><?php echo @$sd?></td>
      <td align="center" style="background-color: #<?php echo $row['project_color_identifier']?>;" nowrap>
      <?php if ($row['project_id']) { ?>
        <a href="./index.php?m=projects&a=view&project_id=<?php echo $row['project_id']?>" style="color: <?php echo  bestColor( $row["project_color_identifier"] ) ?>;"><?php echo $row['project_name']?></a>
      <?php } else { ?>
        -
      <?php } ?>
      </td>
      <td nowrap="nowrap"><?php print ($tc); ?></td>
    </tr>
  <?php } ?>
  </table>
<?php
}
