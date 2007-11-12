<?php
/*
mod_robottrap : 機器人及砍站程式陷阱程式
由 scribe 略改自しおからｐｈｐスクリプト的「畫像一括ダウンロードロボット‧排除用トラップ」
*/

class mod_robottrap{
	var $DENYLIST_FILE;

	function mod_robottrap(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_robottrap'); // 向系統登記模組專屬獨立頁面

		$this->DENYLIST_FILE = './denylist.txt'; // 封鎖名單列表檔案
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_robottrap : 機器人及砍站程式陷阱程式';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.2 (v071111)';
	}

	/* 自動掛載陷阱點 */
	function autoHookThreadFront(&$dat, $isReply){
		$dat .= '<a href="imglist.htm"></a><a href="src/11266284o2168.jpg"></a>'."\n";
	}

	/* 自動掛載陷阱點 */
	function autoHookThreadRear(&$dat, $isReply){
		$dat .= '<a href="allimage.htm"></a><a href="src/113145915o542.jpg"></a>'."\n";
	}

	/* 加入封鎖名單 */
	function ModulePage(){
		$fp = fopen('./.htaccess', 'a');
		$denyip = 'Deny from '.$_SERVER['REMOTE_ADDR']."\n"; // 加入一行新封鎖設定
		fwrite($fp, $denyip);
		fclose($fp);

		$fp = fopen($this->DENYLIST_FILE, 'a');
		$denytime = gmdate('y/m/d H:i:s', time() + TIME_ZONE * 3600);
		$denytxt = $denytime."\t".$_SERVER['REMOTE_ADDR']."\t".gethostbyaddr($_SERVER['REMOTE_ADDR'])."\t".$_SERVER['HTTP_USER_AGENT']."\n"; // 加入一行新記錄
		fwrite($fp, $denytxt);
		fclose($fp);

		echo 'Denied from '.$_SERVER['REMOTE_ADDR']."\n";
	}
}
?>