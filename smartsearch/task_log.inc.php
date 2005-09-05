<?php 
class task_log {
	var $table = 'task_log';
	var $search_fields = array ("task_log_name","task_log_description");
	var $keyword = null;
		
	function ctask_log (){
		return new task_log();
	}
	
		function fetchResults(&$permissions){
			global $AppUI;
		$sql = $this->_buildQuery();
		$results = db_loadList($sql);
		$outstring = "<th nowrap='nowrap' STYLE='background: #cccccc' >".$AppUI->_('Task Log')."</th>\n";
		if($results){
			foreach($results as $records){
			    if ($permissions->checkModuleItem("tasks", "view", $records["task_log_task"])) {
    				$outstring .= "<tr>";
    				$outstring .= "<td>";
    				($records["task_log_task"]) ? $outstring .= "<a href = \"index.php?m=tasks&a=view&task_id=".$records["task_log_task"]."&tab=1&task_log_id=".$records["task_log_id"]. "\">".$records["task_log_name"]."</a>\n" : $outstring .= "<a href = \"index.php?m=helpdesk&a=view&item_id=".$records["task_log_help_desk_id"]."&tab=1&task_log_id=".$records["task_log_id"]. "\">".$records["task_log_name"]." (".$AppUI->_('Helpdesk').")</a>\n";
    				$outstring .= "</td>";
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
                $q->addQuery('task_log_id');
                $q->addQuery('task_log_name');
                $q->addQuery('task_log_task');
                $q->addQuery('task_log_help_desk_id');

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
