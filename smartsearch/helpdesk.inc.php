<?php 
class helpdesk {
	var $table = 'helpdesk_items';
	var $search_fields = array ("item_title","item_summary","item_os","item_application","item_requestor","item_requestor_email");
	var $keyword = null;
	
	
	function chelpdesk (){
		return new helpdesk();
	}
	
	function fetchResults(&$permissions){
		global $AppUI;
		$sql = $this->_buildQuery();
		$results = db_loadList($sql);
		$outstring = "<th nowrap='nowrap' STYLE='background: #cccccc' >".$AppUI->_('Helpdesk')."</th>\n";
		if($results){
			foreach($results as $records){
			    if($permissions->checkModuleItem("helpdesk", "view", $records["item_id"])){
    				$outstring .= "<tr>";
    				$outstring .= "<td>";
    				$outstring .= "<a href = \"index.php?m=helpdesk&a=view&item_id=".$records["item_id"]."\">".$records["item_title"]."</a>\n";
    				$outstring .= "</td>\n";
			    }
			}
		$outstring .= "</tr>";
		}
		else {
			$outstring .= "<tr>"."<td>".$AppUI->_('Empty')."</td>"."</tr>";
		}
		return $outstring;
	}
	
	function setKeyword($keyword){
		$this->keyword = $keyword;
	}
	
	function _buildQuery(){
                $q  = new DBQuery;
                $q->addTable($this->table);
                $q->addQuery('item_id');
                $q->addQuery('item_title');

                $sql = '';
                foreach($this->search_fields as $field){
                        $sql.=" $field LIKE '%$this->keyword%' or ";
                }
                $sql = substr($sql,0,-4);
                $q->addWhere($sql);
                return $q->prepare(true);
	}
}
?>
