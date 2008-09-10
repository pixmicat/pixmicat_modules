<?php
class mod_adminenhance{
	function getModuleName(){
		return 'mod_adminenhance : 管理工具增強組合包';
	}

	function autoHookRegistBegin(){
		global $BANPATTERN, $BAD_FILEMD5;
		if(file_exists('.ht_blacklist')) $BANPATTERN = array_merge($BANPATTERN, array_map('rtrim', file('.ht_blacklist')));
		if(file_exists('.ht_md5list')) $BAD_FILEMD5 = array_merge($BAD_FILEMD5, array_map('rtrim', file('.ht_md5list')));
	}

	function getModuleVersionInfo(){
		return '4th.Release.3 (v080910)';
	}

	function _showHostString(&$arrLabels, $post, $isReply){
		$arrLabels['{$NOW}'] .= " <u>{$post['host']}</u>";
	}

	function autoHookAdminFunction($action, &$param, $funcLabel, &$message){
		global $PIO, $PMS;
		if($action=='add'){
			// Manual hook: showing hostname of users
			$PMS->hookModuleMethod('ThreadPost', array(&$this, '_showHostString'));
			$PMS->hookModuleMethod('ThreadReply', array(&$this, '_showHostString'));

			$param[] = array('mod_adminenhance_thstop', 'AE: 停止/恢復討論串');
			$param[] = array('mod_adminenhance_banip', 'AE: IP 加到黑名單 (鎖 Class C)');
			$param[] = array('mod_adminenhance_banimg', 'AE: 圖檔 MD5 加到黑名單');
			return;
		}

		switch($funcLabel){
			case 'mod_adminenhance_thstop':
				$infectThreads = array();
				foreach($PIO->fetchPosts($param) as $th){
					if($th['resto']) continue; // 是回應
					$infectThreads[] = $th['no'];
					$flgh = $PIO->getPostStatus($th['status']);
					$flgh->toggle('TS');
					$PIO->setPostStatus($th['no'], $flgh->toString());
				}
				$PIO->dbCommit();
				$message .= '停止/恢復討論串 (No.'.implode(', ', $infectThreads).') 完成<br />';
				break;
			case 'mod_adminenhance_banip':
				$fp = fopen('.ht_blacklist', 'a');
				foreach($PIO->fetchPosts($param) as $th){
					if(($IPaddr = gethostbyname($th['host'])) != $th['host']) $IPaddr .= '/24';
					fwrite($fp, $IPaddr."\n");
				}
				fclose($fp);
				$message .= 'IP 黑名單更新完成<br />';
				break;
			case 'mod_adminenhance_banimg':
				$fp = fopen('.ht_md5list', 'a');
				foreach($PIO->fetchPosts($param) as $th){
					if($th['md5chksum']) fwrite($fp, $th['md5chksum']."\n");
				}
				fclose($fp);
				$message .= '圖檔黑名單更新完成<br />';
				break;
			default:
		}
	}
}
?>