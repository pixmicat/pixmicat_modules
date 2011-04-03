<?php
class mod_ipfilter{
	var $SELF;

	function mod_ipfilter(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__);
		$this->SELF = $PMS->getModulePageURL(__CLASS__);
	}

	function getModuleName(){
		return __CLASS__.' : 搜尋 IP';
	}

	function getModuleVersionInfo(){
		return 'b110403';
	}

	function autoHookLinksAboveBar(&$link, $pageId, $addinfo=false){
		if($pageId == 'admin' && $addinfo == true)
			$link .= '[<a href="'.$this->SELF.'">搜尋 IP</a>]';
	}

	function ModulePage(){
		global $PMS, $PIO, $FileIO;
		if(!adminAuthenticate('check')) die('[Error] Access Denied.');

		$content = '';
		// POST
		if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){
			// 刪除
			$del = isset($_POST['del']) ? $_POST['del'] : null;
			if($del != null){
				$files = $PIO->removePosts($del);
				$delta_totalsize = 0;
				if(count($files)) $delta_totalsize -= $FileIO->deleteImage($files);
				if($delta_totalsize != 0) total_size($delta_totalsize);
				foreach($del as $n){
					if($oldCaches = glob('./cache/'.$n.'-*')){
						foreach($oldCaches as $o) @unlink($o);
					}
				}
			}

			// 對 host 作 search
			$filter = isset($_POST['filter']) ? CleanStr($_POST['filter']) : '';
			if($filter != ''){
				$res = $PIO->searchPost($filter, '`host`', 'AND');
				foreach ($res as $p){
					$no = $p['no']; $now = $p['now']; $host = $p['host'];
					$com = htmlspecialchars(str_cut(str_replace('<br />',' ', $p['com']), 50));
					$content .= <<< HERE
<tr>
	<td><input type="checkbox" name="del[]" value="$no" /></td>
	<td>$no</td>
	<td>$now</td>
	<td>$com</td>
	<td>$host</td>
</tr>
HERE;
				}
				// 沒結果
				if(count($res) == 0) $content = '<tr><td rows="5">Not Found</td></tr>';
			}
			// AJAX 要求在此即停止，一般要求則繼續印出頁面
			if(isset($_POST['ajax'])){
				echo $content;
				return;
			}
		}

		// 顯示表單
		$dat = '';
		head($dat);
		$dat .= '<div class="bar_admin">搜尋 IP</div>
<div id="content">
<form action="'.$this->SELF.'" method="post">
<div id="ipconfig">
Filter: <input type="text" name="filter" size="30" />
<input type="submit" value="搜尋" onclick="return search(this.form);" /><br />
<table border="0" width="100%">
<tr><th>Delete</th><th>No.</th><th>Date</th><th>Comment</th><th>Host</th></tr>
'.$content.'
</table>
<input type="submit" value="刪除" />
</div>
</form>
<hr />
</div>
<script type="text/javascript">
// <![CDATA[
function search(form){
	var f = form.filter.value;
	$.post("'.str_replace('&amp;', '&', $this->SELF).'", {filter: f, ajax: true}, function(d){
		$("table", form)
			// Remove all items except header
			.find("tr:gt(0)").remove()
			// Fill the results
			.end().append(d);
	});
	return false;
}
// ]]>
</script>
';
		foot($dat);
		echo $dat;
	}
}
?>