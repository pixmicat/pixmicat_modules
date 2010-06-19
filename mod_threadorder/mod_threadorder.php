<?php
class mod_threadorder{
	var $TOPMOST_LOG,$BOTTOMMOST_LOG;
	function mod_threadorder(){
		global $PMS;
		$this->TOPMOST_LOG = './topmost.log'; // 置頂紀錄檔位置
		$this->BOTTOMMOST_LOG = './buttommost.log'; // 置底紀錄檔位置
		$PMS->hookModuleMethod('ModulePage', 'mod_threadorder'); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL('mod_threadorder');
	}

	function getModuleName(){
		return 'mod_threadorder : 討論串置頂置底';
	}

	function getModuleVersionInfo(){
		return 'v100619';
	}

	function autoHookAdminList(&$modFunc, $post, $isres){
		if(!$isres) $modFunc .= '[<a href="'.$this->mypage.'&amp;no='.$post['no'].'&amp;action=top" title="Top most">TM</a>][<a href="'.$this->mypage.'&amp;no='.$post['no'].'&amp;action=bottom" title="Bottom most">BM</a>]';
	}

	function autoHookLinksAboveBar(&$link, $pageId, $addinfo=false) {
		if($pageId == 'admin') $link.=' [<a href="'.$this->mypage.'">置頂/置底管理</a>]';
	}

	function _write($file,$data) {
		$rp = fopen($file, "w");
		flock($rp, LOCK_EX); // 鎖定檔案
		@fputs($rp,$data);
		flock($rp, LOCK_UN); // 解鎖
		fclose($rp);
		chmod($file,0666);
	}

	function autoHookThreadOrder($resno,$page_num,$single_page,&$threads){
		if($logs=@file($this->TOPMOST_LOG)) { // order asc
//			$logs = array_reverse($logs);
			foreach($logs as $tm) {
			    $temp = array_search( $tm=trim($tm), $threads );
			    if($temp !== NULL && $temp !== FALSE) {
					array_splice($threads, $temp, 1);
					array_unshift($threads, $tm);
				}
			}
		}
		if($logs=@file($this->BOTTOMMOST_LOG)) { // order asc
			foreach($logs as $bm) {
			    $temp = array_search( $bm=trim($bm), $threads );
			    if($temp !== NULL && $temp !== FALSE) {
					array_splice($threads, $temp, 1);
					array_push($threads, $bm);
				}
			}
		}
	}
	function ModulePage(){
		global $PIO;
		if(!adminAuthenticate('check')) die('403 Access denied');

		$act=isset($_REQUEST['action'])?$_REQUEST['action']:'';
		if($_SERVER['REQUEST_METHOD']=='POST') {

			if($act=='reorder'){ // 排序
				$newTop = explode('|',$_POST['newTopmost']);
				$newTop = array_reverse($newTop);
				$newTop = trim(implode("\n",$newTop));
				$this->_write($this->TOPMOST_LOG,$newTop);
				$newBottom = trim(implode("\n",explode('|',$_POST['newBottommost'])));
				$this->_write($this->BOTTOMMOST_LOG,$newBottom);
				die('Done. Please go back.');
			}
		}
		switch($act) {
			case 'top'; // 置頂
				if($PIO->isThread($_GET['no'])) {
					$post = $PIO->fetchPosts($_GET['no']);
					if(!count($post)) die('[Error] Post does not exist.');
					$this->_write($this->TOPMOST_LOG,$_GET['no']."\n".@file_get_contents($this->TOPMOST_LOG));
					die('Done. Please go back.');
				} else die('[Error] Thread does not exist.');
				break;
			case 'bottom'; // 置底
				if($PIO->isThread($_GET['no'])) {
					$post = $PIO->fetchPosts($_GET['no']);
					if(!count($post)) die('[Error] Post does not exist.');
					$this->_write($this->BOTTOMMOST_LOG,@file_get_contents($this->BOTTOMMOST_LOG).$_GET['no']."\n");
					die('Done. Please go back.');
				} else die('[Error] Thread does not exist.');
				break;
			default:
				$dat=''; head($dat); echo $dat; $dat='';
				echo '<script type="text/javascript">
function move(target,index,to) {
	var list = document.getElementById(target);
	var total = list.options.length-1;
	if (index == -1) return false;
	if (to == +1 && index == total) return false;
	if (to == -1 && index == 0) return false;
	var items = new Array;
	var values = new Array;
	for (i = total; i >= 0; i--) {
		items[i] = list.options[i].text;
		values[i] = list.options[i].value;
	}
	for (i = total; i >= 0; i--) {
		if (index == i) {
			list.options[i + to] = new Option(items[i],values[i], 0, 1);
			list.options[i] = new Option(items[i + to], values[i +to]);
			i--;
		} else {
			list.options[i] = new Option(items[i], values[i]);
	   }
	}
	list.focus();
	return true;
}
function add(target,name,value){
target.options[target.length] = new Option(name, value);
}
function remove(target){
	var list = document.getElementById(target);
  var selIndex = list.selectedIndex;
  if (selIndex != -1) {
    for(i=list.length-1; i>=0; i--)
    {
      if(list.options[i].selected)
      {
        list.options[i] = null;
      }
    }
    if (list.length > 0) {
      list.selectedIndex = selIndex == 0 ? 0 : selIndex - 1;
    }
  }
}
function place(){
  placeInHidden("|", "topmost", "ntop");
  placeInHidden("|", "buttommost", "nbottom");

}
function placeInHidden(delim, selStr, hidStr){
  var selObj = document.getElementById(selStr);
  var hideObj = document.getElementById(hidStr);
  hideObj.value = "";
  for (i = 0; i <= selObj.options.length-1; i++) { 
	hideObj.value += selObj.options[i].value + delim;
  }

}
</script>
<form action='.$this->mypage.' method="post" onsubmit="place()">
<input type="hidden" name="action" value="reorder" />
<table>
<tr>
<td>置頂：</td><td></td><td>置底：</td><td></td>
</tr>
<tr>
<td align="middle">';
echo '  <select id="topmost" size="30">';
$logs=@file($this->TOPMOST_LOG); // order asc
$logs=array_reverse($logs);
foreach($logs as $tm) {
	echo '    <option value="'.$tm.'" >'.$tm.'</option>';
}
echo '  </select>
</td>
<td>
<input type="button" value="↑" 
onClick="move(\'topmost\',document.getElementById(\'topmost\').selectedIndex,-1)"><br/><br/>
<input type="button" value="↓"
onClick="move(\'topmost\',document.getElementById(\'topmost\').selectedIndex,+1)"><br/><br/>
<input type="button" value="解"
onClick="remove(\'topmost\')">
</td>
<td align="middle">';
echo '  <select id="buttommost" size="30">';
$logs=@file($this->BOTTOMMOST_LOG); // order asc
foreach($logs as $bm) {
	echo '    <option value="'.$bm.'" >'.$bm.'</option>';
  } 
echo '  </select>
</td>
<td>
<input type="button" value="↑" 
onClick="move(\'buttommost\',document.getElementById(\'buttommost\').selectedIndex,-1)"><br/><br/>
<input type="button" value="↓"
onClick="move(\'buttommost\',document.getElementById(\'buttommost\').selectedIndex,+1)"><br/><br/>
<input type="button" value="解"
onClick="remove(\'buttommost\')">
</td>
</tr>
<tr><td colspan="4"><input type="hidden" name="newTopmost" id="ntop" value="" /><input type="hidden" name="newBottommost" id="nbottom" value="" />
  <input type="hidden" name="action" value="reorder">
  <input type="submit">
</td>
</tr>
</table></form>';
			$dat=''; foot($dat); echo $dat;
		}
	}
}
