<?php
/* mod_paint : PaintBBS & Shi-Painter Bridge
 * $Id$
 */
class mod_paint{
	var $THISPAGE, $TMPFolder, $PaintComponent, $PMAX_W, $PMAX_H, $SECURITY;
	function mod_paint(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_paint');
		$this->THISPAGE = $PMS->getModulePageURL('mod_paint');
		$this->TMPFolder = './tmp/'; // 圖檔暫存目錄
		$this->PMAX_W = 500; $this->PMAX_H = 500; // 繪圖最大長寬尺寸
		$this->PaintComponent = array(
			'Base'=>'./paint/', // 其他資源檔基底目錄
			'PaintBBS'=>'./paint/PaintBBS.jar',
			'ShiPainter'=>'./paint/spainter.jar',
			'PCHViewer'=>'./paint/PCHViewer.jar'
		); // 各組件所在位置
		$this->SECURITY = array('CLICK'=> 100, 'TIMER'=> 180, 'URL' => PHP_SELF2); // 安全設定

		$this->Action_deleteOldTemp(); // 清除舊暫存
	}

	function getModuleName(){
		return 'mod_paint : PaintBBS &amp; Shi-Painter Bridge';
	}

	function getModuleVersionInfo(){
		return 'PaintBBS &amp; しぃペインター(Pro) 支援模組 Pre-Alpha';
	}

	/* Hook to ThreadFront */
	function autoHookThreadFront(&$txt){
		$txt .= '<form action="'.$this->THISPAGE.'&amp;action=paint" method="post">
<div style="text-align: center;">
程式<select name="Papplet"><option value="0">PaintBBS</option><option value="1">しぃペインター</option><option value="2">しぃペインターPro</option></select>
寬<input type="text" name="PimgW" value="200" size="3" />x高<input type="text" name="PimgH" value="200" size="3" />
<input type="submit" value="作畫" />
<input type="checkbox" value="true" name="Panime" checked="checked" />作畫記錄
</div>
</form>'."\n";
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		// TODO: 判斷文章圖檔是否為繪圖，如果有動畫就作連結 (action=viewpch&file=XXX.png&type=pch)
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		// TODO: 判斷文章圖檔是否為繪圖，如果有動畫就作連結
	}

	/* 中控頁面: 根據 Action 執行指定動作 */
	function ModulePage(){
		$Action = isset($_GET['action']) ? $_GET['action'] : '';
		switch($Action){
			case 'paint':
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
		$Papplet = isset($_POST['Papplet']) ? $_POST['Papplet'] : '0';
		$Panime = isset($_POST['Panime']) ? $_POST['Panime'] : false;
		$PimgW = isset($_POST['PimgW']) ? $_POST['PimgW'] : 200; if($PimgW < 100){ $PimgW = 100; } if($PimgW > $this->PMAX_W){ $PimgW = $this->PMAX_W; }
		$PimgH = isset($_POST['PimgH']) ? $_POST['PimgH'] : 200; if($PimgH < 100){ $PimgH = 100; } if($PimgH > $this->PMAX_H){ $PimgH = $this->PMAX_H; }
		$AppletW = $PimgW + 150; if($AppletW < 400){ $AppletW = 400; } // Applet Width
		$AppletH = $PimgH + 170; if($AppletH < 420){ $AppletH = 420; } // Applet Height
		switch($Papplet){
			case '2': // ShiPainterPro
			case '1': // ShiPainter
				$ShiSwitch = array(1=>'normal', 2=>'pro');
				$PappletJar = $this->PaintComponent['ShiPainter'].','.$this->PaintComponent['Base'].$ShiSwitch[intval($Papplet)].'.zip';
				$PappletCode = 'c.ShiPainter.class';
				$PappletParams = '<param name="dir_resource" value="'.$this->PaintComponent['Base'].'" />
<param name="tt.zip" value="'.$this->PaintComponent['Base'].'tt.zip" />
<param name="res.zip" value="'.$this->PaintComponent['Base'].'res_'.$ShiSwitch[intval($Papplet)].'.zip" />
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
				break;
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
<param name="url_exit" value="'.$this->THISPAGE.'&amp;action=post" />';
	if($Panime) $dat .= '
<param name="thumbnail_type" value="animation" />';
	$dat .= '
<param name="send_header" value="usercode=dr0RABR1RXGA" />
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
</div>
<hr />';
		foot($dat);
		echo $dat;
	}

	/* 處理 Applet 送來的 Raw Data 並分析儲存 */
	function Action_Save(){
		$RAWInput = fopen('php://input', 'rb');
		$RAWData = '';
		while (!feof($RAWInput)) $RAWData .= fread($RAWInput, 8192);
		fclose($RAWInput);
		file_put_contents($this->TMPFolder.'test.dat', $RAWData); // Raw Data
		$userHeaderLength = intval(substr($RAWData, 1, 8)); // User HEADER Length
		$userHeader = substr($RAWData, 9, $userHeaderLength); // User Header

		$imgLength = intval(substr($RAWData, 9 + $userHeaderLength, 8)); // Image Data Length
		$imgData = substr($RAWData, 19 + $userHeaderLength, $imgLength); // Image Data
		$imgType = substr($imgData, 1, 5); // Image Type (Probably PNG\r\n)
		file_put_contents($this->TMPFolder.'test.'.(($imgType=="PNG\r\n") ? 'png' : 'jpg'), $imgData);

		$pchLength = intval(substr($RAWData, 19 + $userHeaderLength + $imgLength, 8)); // PCH Length
		if($pchLength > 0){
			$pchType = substr($RAWData, 0, 1); // PCH Type
			$pchData = substr($RAWData, 19 + $userHeaderLength + $imgLength + 8, $pchLength); // PCH BINARY DATA
			file_put_contents($this->TMPFolder.'test.'.($pchType=='S' ? 's' : '').'pch', $pchData);
		}
	}

	/* 發文頁面 */
	function Action_Post(){
		/*
		TODO: 使文章跟圖檔連結在一起，發出含有特殊資訊的文章(回應)，回應編號記得要傳遞
		*/
		$dat = '';
		head($dat);
		form($dat, 0);
		$dat .= 'TODO: PostForm Here';

		foot($dat);
		echo $dat;
	}

	/* 顯示動畫 */
	function Action_ViewPCH(){
		$imgfile = isset($_GET['file']) ? $this->TMPFolder.$_GET['file'] : false; // 圖檔名
		if(!file_exists($imgfile)) error('File Not Found.');
		$size = getimagesize($imgfile);
		$imgW = $size[0]; $imgH = $size[1]; // 繪圖版面大小
		$appletW = $imgW; if($appletW < 200){ $appletW = 200; } // Applet 大小
		$appletH = $imgH + 26; if($appletH < 226){ $appletH = 226; }

		$name = str_replace(strrchr($imgfile, '.'), '', $imgfile); // 去除副檔名
		$type = isset($_GET['type']) ? $_GET['type'] : 'pch'; // pch or spch
		$pchName = $name.'.'.$type; // 動畫檔案位置

		switch($type){
			case 'pch': // PaintBBS PCH File
				$PappletCode = 'pch.PCHViewer.class';
				$PappletJar = $this->PaintComponent['PCHViewer'].','.$this->PaintComponent['PaintBBS'];
				$PappletParams = '';
				break;
			case 'spch': // ShiPainter SPCH File
				$PappletCode = 'pch2.PCHViewer.class';
				$PappletJar = $this->PaintComponent['PCHViewer'].','.$this->PaintComponent['ShiPainter'];
				$PappletParams = '<param name="res.zip" value="'.$this->PaintComponent['Base'].'res_normal.zip" />
<param name="tt.zip" value="'.$this->PaintComponent['Base'].'tt.zip" />
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
<applet name="pch" code="'.$PappletCode.'" archive="'.$PappletJar.'" width="'.$appletW.'" height="'.$appletW.'" mayscript="mayscript">
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
<p>-Download-<br />(XXX bytes)</p>
</div>
<hr />';
		foot($dat);
		echo $dat;
	}

	/* 刪除舊暫存 */
	function Action_deleteOldTemp(){
		// TODO: 檢查暫存是否過舊無人認領，超過一段時間就砍
		if(!is_dir($this->TMPFolder)){ mkdir($this->TMPFolder); @chmod($this->TMPFolder, 0777); }
		// delete Old Temp here
	}
}
?>