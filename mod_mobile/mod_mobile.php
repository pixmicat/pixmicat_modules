<?php
/*
mod_mobile : 行動版頁面顯示 (唯讀)
By: scribe
*/

class mod_mobile{
	var $THREADLIST_NUMBER, $thisPage, $displayMode;

	function mod_mobile(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_mobile'); // 向系統登記模組專屬獨立頁面

		$this->THREADLIST_NUMBER = 10; // 一頁顯示列表個數
		$this->thisPage = $PMS->getModulePageURL('mod_mobile'); // 基底位置
		$this->displayMode = isset($_COOKIE['dm']) ? $_COOKIE['dm'] : 's'; // 顯示模式 (s/m/l)
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_mobile : 行動版頁面顯示 (唯讀)';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.2 (v090819)';
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar, $isReply){
		$linkbar .= '[<a href="'.$this->thisPage.'">行動版</a>]'."\n";
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PIO;

		$err = ''; // 錯誤資訊
		$res = isset($_GET['r']) ? intval($_GET['r']) : 0; // 回應編號
		if(isset($_GET['dm'])){ // 是否進入設定模式
			$ss = $ms = $ls = '';
			switch($this->displayMode){
				case 's': $ss = ' selected="selected"'; break;
				case 'm': $ms = ' selected="selected"'; break;
				case 'l': $ls = ' selected="selected"'; break;
			}
			$this->mobileHead($err, TITLE.' - 設定');
			$err .= '<div>[<a href="'.$this->thisPage.'">回首頁</a>]<br/><form action="'.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->thisPage).'" method="post">顯示模式:<br/><select name="dm"><option value="s"'.$ss.'>精簡 (無圖,裁字)</option><option value="m"'.$ms.'>一般 (無圖,全字)</option><option value="l"'.$ls.'>完整 (有圖,全字)</option></select><br/><input type="submit" value="儲存"/></form></div><div id="f">-Pixmicat!m-</div></body></html>';
		}else{
			if(isset($_POST['dm'])){ setCookie('dm', ($this->displayMode = $_POST['dm']), time()+604800); }
			if($res !== 0){
				$pageMax = $page = null; // Not in use
				if(!$PIO->isThread($res)){ $err = 'Thread Not Found'; }
				else{ $post = $PIO->fetchPosts($PIO->fetchPostList($res)); }
			}else{
				$pageMax = ceil($PIO->threadCount() / $this->THREADLIST_NUMBER) - 1; // 分頁最大編號
				$page = isset($_GET['p']) ? intval($_GET['p']) : 0; // 目前所在分頁
				if($page < 0 || $page > $pageMax){ $err = 'Page Out of Range'; } // $page 超過範圍
				else{ $post = $PIO->fetchPosts($PIO->fetchThreadList($this->THREADLIST_NUMBER * $page, $this->THREADLIST_NUMBER, true)); } // 編號由大到小排序取出
			}
		}
		if($err){ echo $err; }
		else{
			$dat = ''; // HTML Buffer
			$this->mobileHead($dat, TITLE);
			$this->mobileBody($dat, $pageMax, $page, $res, $post);
			$this->mobileFoot($dat);
			echo $dat;
		}
	}

	/* 行動版頁首 */
	function mobileHead(&$dat, $title){
		$dat .= '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.1//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/><meta http-equiv="Cache-Control" content="no-cache"/><title>'.$title.'</title><style type="text/css">.s {color:red;} .n{color:green;} .w{color:gray;}</style></head>
<body>
';
	}

	/* 行動版內容 */
	function mobileBody(&$dat, $pageMax, $page, $res, $post){
		global $PIO, $FileIO;
		$post_count = count($post);
		$dat .= '<div id="c">';
		// 逐步取資料
		for($i = 0; $i < $post_count; $i++){
			list($no, $sub, $name, $com, $tim, $ext) = array($post[$i]['no'], $post[$i]['sub'],$post[$i]['name'], $post[$i]['com'], $post[$i]['tim'], $post[$i]['ext']);
			// 資料處理
			if($this->displayMode==='s'){ $com = str_cut($com, 50); } // 取斷字元
			$reply1 = $res ? '<span class="s">'.$sub.'</span>' : '<a href="'.$this->thisPage.'&amp;r='.$no.'">'.$sub.'</a>'; // 回應模式連結
			$reply2 = $res ? '' : '<span class="w">('.($PIO->postCount($no) - 1).')</span>'; // 回應數
			$img = ($this->displayMode==='l' && $FileIO->imageExists($tim.'s.jpg')) ? '<img src="'.$FileIO->getImageURL($tim.'s.jpg').'" alt="p"/><br/>' : '';
			// 輸出
			$dat .= '<div>'.$reply1.$reply2.'<span class="n">'.$name.'</span><br/>'.$img.$com.'</div><hr/>'."\n";
		}
		$dat .= '</div>';
		if($page !== null){ // 分頁欄
			$dat .= '<div id="p">';
			if($page) $dat .= '<a href="'.$this->thisPage.'&amp;p='.($page - 1).'">|&lt;</a> ';
			for($i = 0; $i <= $pageMax; $i++){
				if($i==$page) $dat .= '[<b>'.$i.'</b>] ';
				else $dat .= '[<a href="'.$this->thisPage.'&amp;p='.$i.'">'.$i.'</a>] ';
			}
			if($page < $pageMax) $dat .= '<a href="'.$this->thisPage.'&amp;p='.($page + 1).'">&gt;|</a>';
			$dat .= '</div>';
		}
	}

	/* 行動版頁尾 */
	function mobileFoot(&$dat){
		$dat .= '<div id="f">-Pixmicat!m-<br/><a href="'.$this->thisPage.'&amp;dm=set">顯示模式</a></div></body></html>';
	}
}
?>