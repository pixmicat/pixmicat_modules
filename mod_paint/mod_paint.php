<?php
/* mod_paint : PaintBBS & Shi-Painter Bridge
 * $Id$
 * $Date$
 */

// PHP4 file_put_contents Define
// Source: http://www.php.net/manual/en/function.file-put-contents.php#68329
if(!function_exists('file_put_contents') && !defined('FILE_APPEND')){
	define('FILE_APPEND', 1);
	function file_put_contents($n, $d, $flag = false){
		$mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
		$f = @fopen($n, $mode);
		if($f === false) return false;
		if(is_array($d)) $d = implode($d);
		$bytes_written = fwrite($f, $d);
		fclose($f);
		return $bytes_written;
	}
}

class mod_paint{
	var $THISPAGE, $TMPFolder, $PMAX_W, $PMAX_H, $SECURITY, $PAINT_RESTRICT, $PaintComponent, $TIME_UNIT;
	function mod_paint(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__);
		$this->THISPAGE = $PMS->getModulePageURL(__CLASS__);
		// 可設定項目
		$this->TMPFolder = './tmp/'; // 圖檔暫存目錄
		$this->PMAX_W = 500; $this->PMAX_H = 500; // 繪圖最大長寬尺寸
		$this->SECURITY = array('CLICK'=> 1, 'TIMER'=> 1, 'URL' => PHP_SELF2); // 安全設定
		$this->PAINT_RESTRICT = array('POST'=>true, 'REPLY'=>true); // 繪圖模式是否可使用 (POST:發文, REPLY:回應)
		$this->PaintComponent = array( // 各組件所在位置
			'Base'=>'./paint/', // 其他資源檔基底目錄
			'PaintBBS'=>'./paint/PaintBBS.jar',
			'ShiPainter'=>'./paint/spainter_all.jar',
			'PCHViewer'=>'./paint/PCHViewer.jar'
		);
		$this->TIME_UNIT = array('TIME'=>'作畫時間：', 'D'=> '日', 'H'=>'時', 'M'=>'分', 'S'=>'秒'); // 時間單位
	}

	function getModuleName(){
		return 'mod_paint : PaintBBS &amp; しぃペインター(Pro) 支援模組';
	}

	function getModuleVersionInfo(){
		return '4th.Release.3 (v090310)';
	}

	/* Hook to ThreadFront */
	function autoHookThreadFront(&$txt, $isReply){
		$txt .= '<form action="'.$this->THISPAGE.'&amp;action=paint" method="post">
<div style="text-align: center;">
程式<select name="Papplet"><option value="0">PaintBBS</option><option value="1">しぃペインター</option><option value="2">しぃペインターPro</option></select>
寬<input type="text" name="PimgW" value="200" size="3" />x高<input type="text" name="PimgH" value="200" size="3" />
<input type="submit" value="作畫" />
<input type="checkbox" value="true" name="Panime" id="Panime" checked="checked" /><label for="Panime">作畫記錄</label>'.($isReply ? '<input type="hidden" name="resto" value="'.$isReply.'" />' : '').'
<br /><a href="'.$this->THISPAGE.'&amp;action=post'.($isReply ? '&amp;resto='.$isReply : '').'">使用先前繪圖</a>
</div>
</form>'."\n";
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		$pchBase = './'.IMG_DIR.$post['tim'];
		$pchType = '';
		if(file_exists($pchBase.'.pch')){ $pchFile = $post['tim'].'.pch'; }
		elseif(file_exists($pchBase.'.spch')){ $pchFile = $post['tim'].'.spch'; $pchType = '&amp;type=spch'; }
		else{ return; }
		$pchLink = $this->THISPAGE.'&amp;action=viewpch&amp;file='.$post['tim'].$post['ext'].$pchType;
		$paintTime = '';
		if(preg_match('/_PCH:([0-9]+)_/', $post['status'], $paintTime)){
			$paintTime = intval($paintTime[1]);
			$ptime = '';
			if($paintTime >= 86400){
				$D = intval($paintTime/86400);
				$ptime .= $D.$this->TIME_UNIT['D'];
				$paintTime -= $D * 86400;
			}
			if($paintTime >= 3600){
				$H = intval($paintTime/3600);
				$ptime .= $H.$this->TIME_UNIT['H'];
				$paintTime -= $H * 3600;
			}
			if($paintTime >= 60){
				$M = intval($paintTime/60); 
				$ptime .= $M.$this->TIME_UNIT['M'];
				$paintTime -= $M * 60;
			}
			if($paintTime){
				$ptime .= $paintTime.$this->TIME_UNIT['S'];
			}
			$paintTime = ' - '.$this->TIME_UNIT['TIME'].$ptime.' ';
		}else{ $paintTime = ''; }
		$arrLabels['{$IMG_BAR}'] .= '<small>'.$paintTime.'<a href="'.$pchLink.'">[動畫]</a></small>';
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	/* 將繪圖與文章暗中連結起來 */
	function autoHookPostForm(&$form){
		if(strpos($_SERVER['REQUEST_URI'], str_replace('&amp;', '&', $this->THISPAGE).'&action=post')!==false){ // 符合插入頁面條件
			$userCode = str_replace(array('/','?'), '_', substr(crypt(md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].IDSEED),'id'), -12)); // 使用者識別碼 (IP + UserAgent)
			$imgItem = '<select name="paintImg">';
			foreach(glob($this->TMPFolder.'*_'.$userCode.'.*') as $item){
				if(preg_match('/\.(jpg|png)$/', $item)) $imgItem .= '<option>'.basename($item).'</option>';
			}
			$imgItem .= '</select>';
			$form .= '<tr><td class="Form_bg"><b>附加繪圖</b></td><td>'.$imgItem.'<input type="hidden" name="PaintSend" value="true" /></td></tr>';
		}
	}

	/* 處理繪圖跟文章的連結 */
	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		if(!isset($_POST['paintImg'])) return; // 沒選圖檔
		if(isset($_POST['PaintSend'])){ // 繪圖模式送來的儲存
			$upfileInfo['file'] = $this->TMPFolder.$_POST['paintImg'];
			$upfileInfo['name'] = $_POST['paintImg'];
			$upfileInfo['status'] = 0;
		}
	}

	/* 處理 PCH 檔 (如果有的話) 和暫存清除 */
	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if(!isset($_POST['paintImg'])) return; // 沒選圖檔
		if(isset($_POST['PaintSend'])){ // 繪圖模式送來的儲存
			unlink($this->TMPFolder.$_POST['paintImg']); // 刪除暫存圖檔
			$pchOldfile = str_replace(strrchr($_POST['paintImg'], '.'), '', $_POST['paintImg']); // 暫存 PCH 動畫檔案名 (不含副檔名)
			$pchNewfile = './'.IMG_DIR.str_replace(strrchr($dest, '.'), '', basename($dest)); // 本機儲存 PCH 動畫檔案路徑 (不含副檔名)
			$datFile = $this->TMPFolder.$pchOldfile.'.dat'; // Dat 資訊檔
			$PaintSecond = file_get_contents($datFile); $status .= '_PCH:'.$PaintSecond.'_'; // 於狀態增設作畫時間旗標
			unlink($datFile);
			if(file_exists($this->TMPFolder.$pchOldfile.'.pch')){ $pchOldfile = $this->TMPFolder.$pchOldfile.'.pch'; $pchNewfile .= '.pch'; }
			elseif(file_exists($this->TMPFolder.$pchOldfile.'.spch')){ $pchOldfile = $this->TMPFolder.$pchOldfile.'.spch'; $pchNewfile .= '.spch'; }
			else{ return; }
			copy($pchOldfile, $pchNewfile); unlink($pchOldfile);
		}
	}

	/* 中控頁面: 根據 Action 執行指定動作 */
	function ModulePage(){
		/*
		TODO:
		- Continue Painting (or Discard this function?)
		*/
		$Action = isset($_GET['action']) ? $_GET['action'] : '';
		switch($Action){
			case 'paint':
				$this->Action_deleteOldTemp(); // 清除舊暫存 (放於此處可以達成觸發又不會太頻繁)
				$this->Action_Paint(); break;
			case 'save':
				$this->Action_Save(); break;
			case 'post':
				$this->Action_Post(); break;
			case 'viewpch':
				$this->Action_ViewPCH(); break;
			default:
				echo 'Welcome to my world.';
		}
	}

	/* 印出繪圖頁面 */
	function Action_Paint(){
		$nowTime = time();
		$resto = isset($_POST['resto']) ? intval($_POST['resto']) : 0; // 回應編號
		if($resto != 0){ // 回應
			if(!$this->PAINT_RESTRICT['REPLY']) error('Replying with a painting is Not allowed.');
			$resto = '&amp;resto='.$resto;
		}else{
			if(!$this->PAINT_RESTRICT['POST']) error('Posting with a painting is Not allowed.');
			$resto = '';
		}
		$Papplet = isset($_POST['Papplet']) ? $_POST['Papplet'] : '0';
		$Panime = isset($_POST['Panime']) ? $_POST['Panime'] : false;
		$PimgW = isset($_POST['PimgW']) ? intval($_POST['PimgW']) : 200; if($PimgW < 100){ $PimgW = 100; } if($PimgW > $this->PMAX_W){ $PimgW = $this->PMAX_W; }
		$PimgH = isset($_POST['PimgH']) ? intval($_POST['PimgH']) : 200; if($PimgH < 100){ $PimgH = 100; } if($PimgH > $this->PMAX_H){ $PimgH = $this->PMAX_H; }
		$AppletW = $PimgW + 150; if($AppletW < 400){ $AppletW = 400; } // Applet Width
		$AppletH = $PimgH + 170; if($AppletH < 420){ $AppletH = 420; } // Applet Height
		$userCode = str_replace(array('/','?'), '_', substr(crypt(md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].IDSEED),'id'), -12)); // 使用者識別碼 (IP + UserAgent)
		$AppletHeader = "user={$userCode},time=".$nowTime; // Applet send_header
		switch($Papplet){
			case '2': // ShiPainterPro
			case '1': // ShiPainter
				$ShiSwitch = array(1=>'normal', 2=>'pro');
				$PappletJar = $this->PaintComponent['ShiPainter'];
				$PappletCode = 'c.ShiPainter.class';
				$PappletParams = '<param name="dir_resource" value="'.$this->PaintComponent['Base'].'" />
<param name="tt.zip" value="tt_def.zip" />
<param name="res.zip" value="res.zip" />
<param name="tools" value="'.$ShiSwitch[intval($Papplet)].'" />
<param name="layer_count" value="3" />
<param name="quality" value="1" />';
				if($AppletW < 500){ $AppletW = 500; }
				if($AppletH < 500 && $Papplet=='2'){ $AppletH = 500; }
				break;
			default: // PaintBBS
				$PappletJar = $this->PaintComponent['PaintBBS'];
				$PappletCode = 'pbbs.PaintBBS.class';
				$PappletParams = '';
		}

		$dat = '';
		head($dat);
		$dat .= '
<div id="linkbar">
[<a href="'.PHP_SELF2.'">回到版面</a>]
<div class="bar_reply">繪圖模式</div>
</div>
<div id="container" style="text-align: center">
<applet code="'.$PappletCode.'" archive="'.$PappletJar.'" name="paintbbs" width="'.$AppletW.'" height="'.$AppletH.'" mayscript="mayscript">
'.$PappletParams.'
<param name="image_width" value="'.$PimgW.'" />
<param name="image_height" value="'.$PimgH.'" />
<param name="image_jpeg" value="true" />
<param name="image_size" value="60" />
<param name="compress_level" value="15" />
<param name="undo" value="90" />
<param name="undo_in_mg" value="45" />
<param name="poo" value="false" />
<param name="send_advance" value="true" />
<param name="tool_advance" value="true" />
<param name="thumbnail_width" value="100%" />
<param name="thumbnail_height" value="100%" />
<param name="url_save" value="'.$this->THISPAGE.'&amp;action=save" />
<param name="url_exit" value="'.$this->THISPAGE.'&amp;action=post'.$resto.'" />';
	if($Panime) $dat .= '
<param name="thumbnail_type" value="animation" />';
	$dat .= '
<param name="send_header" value="'.$AppletHeader.'" />
<param name="security_click" value="'.$this->SECURITY['CLICK'].'" />
<param name="security_timer" value="'.$this->SECURITY['TIMER'].'" />
<param name="security_url" value="'.$this->SECURITY['URL'].'" />
<param name="security_post" value="false" />

<param name="image_bkcolor" value="#FFFFFF" />
<param name="color_text" value="#333333" />
<param name="color_bk" value="#FFFFFF" />
<param name="color_bk2" value="#DDDDDD" />
<param name="color_icon" value="#FFFFFF" />
<param name="color_iconselect" value="#999999" />
<param name="color_bar" value="#999999" />
<param name="color_frame" value="#666666" />
<param name="tool_color_button" value="#FFFFFF" />
<param name="tool_color_button2" value="#FFFFFF" />
<param name="tool_color_text" value="#333333" />
<param name="tool_color_bar" value="#FFFFFF" />
<param name="tool_color_frame" value="#666666" />
</applet>
<br />
'.$this->TIME_UNIT['TIME'].'<input type="text" id="count" />
<script type="text/javascript">
// <![CDATA[
stime = new Date();
setInterval(function(){
	now = new Date();
	s = Math.floor((now.getTime() - stime.getTime())/1000);
	disp = "";
	if(s >= 86400){
		d = Math.floor(s/86400);
		disp += d+"'.$this->TIME_UNIT['D'].'";
		s -= d*86400;
	}
	if(s >= 3600){
		h = Math.floor(s/3600);
		disp += h+"'.$this->TIME_UNIT['H'].'";
		s -= h*3600;
	}
	if(s >= 60){
		m = Math.floor(s/60);
		disp += m+"'.$this->TIME_UNIT['M'].'";
		s -= m*60;
	}
	document.getElementById("count").value = disp+s+"'.$this->TIME_UNIT['S'].'";
}, 1000);
// ]]>
</script>
</div>
<hr />';
		foot($dat);
		echo $dat;
	}

	/* 處理 Applet 送來的 Raw Data 並分析儲存 */
	function Action_Save(){
		$nowTime = time(); // 現在時間
		$RAWInput = fopen('php://input', 'rb');
		$RAWData = '';
		while (!feof($RAWInput)) $RAWData .= fread($RAWInput, 8192);
		fclose($RAWInput);
		$userHeaderLength = intval(substr($RAWData, 1, 8)); // User HEADER Length
		$userHeader = explode(',', substr($RAWData, 9, $userHeaderLength)); // User Header
		foreach($userHeader as $h){
			$h = explode('=', $h);
			$$h[0] = $h[1]; // 分配變數 ($user = XXX, $time = XXX)
		}
		$datData = ($nowTime - $time);
		$filename = $nowTime.'_'.$user; // 檔名
		file_put_contents($this->TMPFolder.$filename.'.dat', $datData); // Recognize Data (作畫秒數)

		$imgLength = intval(substr($RAWData, 9 + $userHeaderLength, 8)); // Image Data Length
		$imgData = substr($RAWData, 19 + $userHeaderLength, $imgLength); // Image Data
		$imgType = substr($imgData, 1, 5); // Image Type (Probably PNG\r\n)
		file_put_contents($this->TMPFolder.$filename.(($imgType=="PNG\r\n") ? '.png' : '.jpg'), $imgData);

		$pchLength = intval(substr($RAWData, 19 + $userHeaderLength + $imgLength, 8)); // PCH Length
		if($pchLength > 0){
			$pchType = substr($RAWData, 0, 1); // PCH Type
			$pchData = substr($RAWData, 19 + $userHeaderLength + $imgLength + 8, $pchLength); // PCH BINARY DATA
			file_put_contents($this->TMPFolder.$filename.($pchType=='S' ? '.s' : '.').'pch', $pchData);
		}
	}

	/* 發文頁面 */
	function Action_Post(){
		$resto = isset($_GET['resto']) ? intval($_GET['resto']) : 0; // 回應編號
		$userCode = str_replace(array('/','?'), '_', substr(crypt(md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].IDSEED),'id'), -12)); // 使用者識別碼 (IP + UserAgent)
		$imgList = '';
		$imgs = glob($this->TMPFolder.'*_'.$userCode.'.*');
		if(count($imgs) == 0) error('目前沒有屬於您的繪圖存在，請先作畫');
		foreach($imgs as $l){
			if(preg_match('/\.(jpg|png)$/', $l)) $imgList .= '<div style="float: left; margin: 1em; border: solid grey 1px"><img src="'.$l.'" /><br />'.basename($l).'</div>'."\n";
		}

		$dat = '';
		head($dat);
		form($dat, $resto, false); // 發文表單不摺疊
		$dat .= '<script type="text/javascript">try{ $g("fupfile").disabled = true; showform(); }catch(e){}</script>

<div id="imglist">
'.$imgList.'
</div>';

		foot($dat);
		echo $dat;
	}

	/* 顯示動畫 */
	function Action_ViewPCH(){
		$imgfile = isset($_GET['file']) ? './'.IMG_DIR.$_GET['file'] : false; // 圖檔名
		if(!file_exists($imgfile)) error('File Not Found.');
		$size = getimagesize($imgfile);
		$imgW = $size[0]; $imgH = $size[1]; // 繪圖版面大小
		$appletW = $imgW; if($appletW < 200){ $appletW = 200; } // Applet 大小
		$appletH = $imgH + 26; if($appletH < 226){ $appletH = 226; }

		$name = str_replace(strrchr($imgfile, '.'), '', $imgfile); // 去除副檔名
		$type = isset($_GET['type']) ? $_GET['type'] : 'pch'; // pch or spch
		$pchName = $name.'.'.$type; // 動畫檔案位置
		$pchSize = filesize($pchName);

		switch($type){
			case 'pch': // PaintBBS PCH File
				$PappletCode = 'pch.PCHViewer.class';
				$PappletJar = $this->PaintComponent['PCHViewer'].','.$this->PaintComponent['PaintBBS'];
				$PappletParams = '';
				break;
			case 'spch': // ShiPainter SPCH File
				$PappletCode = 'pch2.PCHViewer.class';
				$PappletJar = $this->PaintComponent['PCHViewer'].','.$this->PaintComponent['ShiPainter'];
				$PappletParams = '<param name="res.zip" value="res.zip" />
<param name="tt.zip" value="tt_def.zip" />
<param name="tt_size" value="31" />';
				break;
			default:
				error('File Not support');
		}

		$dat = '';
		head($dat);
		$dat .= '
<div id="linkbar">
[<a href="'.PHP_SELF2.'">回到版面</a>]
<div class="bar_reply">動畫播放模式</div>
</div>
<div id="container" style="text-align: center">
<applet name="pch" code="'.$PappletCode.'" archive="'.$PappletJar.'" width="'.$appletW.'" height="'.$appletH.'" mayscript="mayscript">
'.$PappletParams.'
<param name="image_width" value="'.$imgW.'" />
<param name="image_height" value="'.$imgH.'" />
<param name="pch_file" value="'.$pchName.'" />
<param name="speed" value="10" />
<param name="buffer_progress" value="false" />
<param name="buffer_canvas" value="false" />

<param name="color_back" value="#FFFFFF" />
<param name="color_text" value="#333333" />
<param name="color_icon" value="#FFFFFF" />
<param name="color_bar" value="#AAAAAA" />
<param name="color_bar_select" value="#999999" />
<param name="color_frame" value="#666666" />
</applet>
<p>-<a href="'.$pchName.'">Download</a>-<br />('.$pchSize.' bytes)</p>
</div>
<hr />';
		foot($dat);
		echo $dat;
	}

	/* 刪除舊暫存 */
	function Action_deleteOldTemp(){
		global $FileIO;

		if(!is_dir($this->TMPFolder)){ mkdir($this->TMPFolder); @chmod($this->TMPFolder, 0777); }
		// 檢查暫存是否過舊無人認領，超過一段時間就砍
		$nowTime = time();
		$files = glob($this->TMPFolder.'*');
		foreach($files as $f){
			if($nowTime - intval($f) > 86400){ unlink($f); } // 超過一天未處理則刪除
		}
		// 作畫動畫檔相依性檢查
		$files2 = array_merge(glob(IMG_DIR.'*.pch'), glob(IMG_DIR.'*.spch'));
		foreach($files2 as $ff){
			$fff = basename($ff, strrchr($ff, '.'));
			if(!$FileIO->imageExists($fff.'.png') && !$FileIO->imageExists($fff.'.jpg')){ unlink($ff); } // 作畫動畫原始圖檔已刪
		}
	}
}
?>
