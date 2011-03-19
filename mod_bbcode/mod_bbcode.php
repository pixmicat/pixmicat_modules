<?php
class mod_bbcode{
	var $ImgTagTagMode, $URLTagMode, $MaxURLCount, $URLTrapLog;
	var $myPage, $urlcount;

	function mod_bbcode(){
		global $PMS;

		$PMS->hookModuleMethod('ModulePage', 'mod_bbcode'); // 向系統登記模組專屬獨立頁面
		$this->myPage = $PMS->getModulePageURL('mod_bbcode'); // 基底位置

		$this->ImgTagTagMode = 1; // [img]標籤行為 (0:不轉換 1:無貼圖時轉換 2:常時轉換)
		$this->URLTagMode = 1; // [url]標籤行為 (0:不轉換 1:正常)
		$this->MaxURLCount = 2; // [url]標籤上限 (超過上限時標籤為陷阱標籤[寫入至$URLTrapLog])
		$this->URLTrapLog = './URLTrap.log'; // [url]陷阱標籤記錄檔

		if(method_exists($PMS,'addCHP')) {
			$PMS->addCHP('mod_bbbutton_addButtons',array($this,'_addButtons'));
		}
	}

	function getModuleName(){
		return 'mod_bbcode : 內文BBCode轉換';
	}

	function getModuleVersionInfo(){
		return '6th.Release-dev (v110319)';
	}

	function autoHookPostInfo(&$postinfo){
		$postinfo .= "<li>可使用 <a href='".$this->myPage."' rel='_blank'>BBCode</a></li>\n";
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $resto, $imgWH){
		$com = $this->_bb2html($com,$dest);
	}

	function _addButtons($txt) {
		$txt .= 'bbbuttons.tags = $.extend({
			 b:{desc:"Bold"},
			 i:{desc:"Italic"},
			 u:{desc:"Underline"},
			 p:{desc:"Paragraph"},
			 color:{desc:"Color", prompt:{prompt:"Enter Color:",def:""}},
			 pre:{desc:"Pre-formatted text"},
			 quote:{desc:"Quotation"},
			 email:{desc:"Insert e-mail address"},
			 '.($this->URLTagMode?'url:{desc:"Insert URL"},':'').'
			 '.($this->ImgTagTagMode?'img:{desc:"Insert Image"},':'').'
			},bbbuttons.tags);';
	}

	function _bb2html($string, $dest){
		$this->urlcount=0; // Reset counter
		$string = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $string);
		$string = preg_replace('#\[i\](.*?)\[/i\]#si', '<i>\1</i>', $string);
		$string = preg_replace('#\[u\](.*?)\[/u\]#si', '<u>\1</u>', $string);
		$string = preg_replace('#\[p\](.*?)\[/p\]#si', '<p>\1</p>', $string);

		$string = preg_replace('#\[color=(\S+?)\](.*?)\[/color\]#si', '<font color="\1">\2</font>', $string);

		$string = preg_replace('#\[s([1-7])\](.*?)\[/s([1-7])\]#si', '<font size="\1">\2</font>', $string);

		$string = preg_replace('#\[pre\](.*?)\[/pre\]#si', '<pre>\1</pre>', $string);
		$string = preg_replace('#\[quote\](.*?)\[/quote\]#si', '<blockquote>\1</blockquote>', $string);

		if($this->URLTagMode){
			$string=preg_replace_callback('#\[url\](https?|ftp)(://\S+?)\[/url\]#si', array(&$this, '_URLConv1'), $string);
			$string=preg_replace_callback('#\[url\](\S+?)\[/url\]#si', array(&$this, '_URLConv2'), $string);
			$string=preg_replace_callback('#\[url=(https?|ftp)(://\S+?)\](.*?)\[/url\]#si', array(&$this, '_URLConv3'), $string);
			$string=preg_replace_callback('#\[url=(\S+?)\](.*?)\[/url\]#si', array(&$this, '_URLConv4'), $string);
			$this->_URLExcced();
		}

		$string = preg_replace('#\[email\](\S+?@\S+?\\.\S+?)\[/email\]#si', '<a href="mailto:\1">\1</a>', $string);

		$string = preg_replace('#\[email=(\S+?@\S+?\\.\S+?)\](.*?)\[/email\]#si', '<a href="mailto:\1">\2</a>', $string);
		if (($this->ImgTagTagMode == 2) || ($this->ImgTagTagMode && !$dest)){
			$string = preg_replace('#\[img\](([a-z]+?)://([^ \n\r]+?))\[\/img\]#si', '<img src="\1" border="0" alt="\1" />', $string);
		}

		return $string;
	}

	function _URLConv1($m){
		++$this->urlcount;
		return "<a href=\"$m[1]$m[2]\" rel=\"_blank\">$m[1]$m[2]</a>";
	}

	function _URLConv2($m){
		++$this->urlcount;
		return "<a href=\"http://$m[1]\" rel=\"_blank\">$m[1]</a>";
	}

	function _URLConv3($m){
		++$this->urlcount;
		return "<a href=\"$m[1]$m[2]\" rel=\"_blank\">$m[3]</a>";
	}

	function _URLConv4($m){
		++$this->urlcount;
		return "<a href=\"http://$m[1]\" rel=\"_blank\">$m[2]</a>";
	}

	function _URLRevConv($m){
		if($m[1]=='http' && $m[2]=='://'.$m[3]) {
			return '[url]'.$m[3].'[/url]';
		} elseif(($m[1].$m[2])==$m[3]) {
			return '[url]'.$m[1].$m[2].'[/url]';
		} else {
			if($m[1]=='http')
				return '[url='.substr($m[2],3).']'.$m[3].'[/url]';
			else
				return '[url='.$m[1].$m[2].']'.$m[3].'[/url]';
		}
	}

	function _EMailRevConv($m){
		if($m[1]==$m[2]) return '[email]'.$m[1].'[/email]';
		else return '[email='.$m[1].']'.$m[2].'[/email]';
	}

	function _html2bb(&$string){
		$string = preg_replace('#<b>(.*?)</b>#si', '[b]\1[/b]', $string);
		$string = preg_replace('#<i>(.*?)</i>#si', '[i]\1[/i]', $string);
		$string = preg_replace('#<u>(.*?)</u>#si', '[u]\1[/u]', $string);
		$string = preg_replace('#<p>(.*?)</p>#si', '[p]\1[/p]', $string);

		$string = preg_replace('#<font color="(\S+?)">(.*?)</font>#si', '[color=\1]\2[/color]', $string);

		$string = preg_replace('#<font size="([1-7])">(.*?)</font>#si', '[s\1]\2[/s\1]', $string);

		$string = preg_replace('#<pre>(.*?)</pre>#si', '[pre]\1[/pre]', $string);
		$string = preg_replace('#<blockquote>(.*?)</blockquote>#si', '[quote]\1[/quote]', $string);

		$string = preg_replace_callback('#<a href="(https?|ftp)(://\S+?)" rel="_blank">(.*?)</a>#si', array(&$this, '_URLRevConv'), $string);
		$string = preg_replace_callback('#<a href="mailto:(\S+?@\S+?\\.\S+?)">(.*?)</a>#si', array(&$this, '_EMailRevConv'), $string);

		$string = preg_replace('#<img src="(([a-z]+?)://([^ \n\r]+?))" border="0" alt=".*?" />#si', '[img]\1[/img]', $string);
	}


	function _URLExcced(){
		if($this->urlcount > $this->MaxURLCount) {
		  	  $fh = fopen($this->URLTrapLog, 'a+b');
		  	  fwrite($fh, time()."\t$_SERVER[REMOTE_ADDR]\t$cnt\n");
		  	  fclose($fh);
		  	  error("[url]標籤超過上限");
		}
	}

	function ModulePage(){
		$dat='';$status='現時BBCode設定:<ul><li>[url]標籤行為 (0:不轉換 1:正常) - '.$this->URLTagMode.'</li><li>[url]標籤上限 (超過上限時標籤為陷阱標籤並寫入至記錄檔中) - '.$this->MaxURLCount.'</li><li>'._T('info_basic_urllinking').' '._T('info_0no1yes').' - '.AUTO_LINK.'</li><li>[img]標籤行為 (0:不轉換 1:無貼圖時轉換 2:常時轉換) - '.$this->ImgTagTagMode.'</li></ul>';
		head($dat);
		$dat.=<<<EOH
$status
BBCode 代碼包含一些標籤方便您快速的更改文字的基本形式. 這些可以分述如下: 
<ul><li>要製作一份粗體文字可使用 <b>[b][/b]</b>, 例如: <br/><br/><b>[b]</b>哈囉<b>[/b]</b><br/><br/>會變成<b>哈囉</b><br/><br/></li>
<li>要使用底線時, 可使用<b>[u][/u]</b>, 例如:<br/><br/><b>[u]</b>早安<b>[/u]</b><br/><br/>會變成<u>早安</u><br/><br/></li>
<li>要斜體顯示時, 可使用 <b>[i][/i]</b>, 例如:<br/><br/>這個真是 <b>[i]</b>棒呆了!<b>[/i]</b><br/><br/>將會變成 這個真是 <i>棒呆了!</i></li></ul>

要在您的文章中修改文字顏色及大小需要使用以下的標籤. 請注意, 顯示的效果視您的瀏覽器和系統而定: 
<ul><li>更改文字色彩時, 可使用 <b>[color=][/color]</b>. 您可以指定一個可被辨識的顏色名稱(例如. red, blue, yellow, 等等.) 或是使用顏色編碼, 例如: #FFFFFF, #000000. 舉例來說, 要製作一份紅色文字您必須使用:<br/><br/><b>[color=red]</b>哈囉!<b>[/color]</b><br/><br/>或是<br/><br/><b>[color=#FF0000]</b>哈囉!<b>[/color]</b><br/><br/>都將顯示:<font color="red">哈囉!</font><br/><br/></li>
<li>改變文字的大小也是使用類似的設定, 標籤為 <b>[s?][/s?]</b>. 起始值為 1 (細小) 到 7 為止 (巨大). 舉例說明:<br/><br/><b>[s1]</b>小不拉嘰<b>[/s1]</b><br/><br/>將會產生 <font size="1">小不拉嘰</font><br/><br/>當情形改變時:<br/><br/><b>[s7]</b>有夠大顆!<b>[/s7]</b><br/><br/>將會顯示 <font size="7">有夠大顆!</font></li></ul>

可以結合不同的標籤功能: <br/>
<ul><li>例如要吸引大家的注意時, 您可以使用:<br/><br/><b>[s5][color=red][b]</b>看我這兒!<b>[/b][/color][/s5]</b><br/><br/> 將會顯示出 <font size="5"><font color="red"><b>看我這兒!</b></font></font><br/>&nbsp;</li>
<li>我們並不建議您顯示太多這類的文字! 但是這些還是由您自行決定. 在使用 BBCode 代碼時, 請記得要正確的關閉標籤, 以下就是錯誤的使用方式:<br/><br/><b>[b][u]</b>這是錯誤的示範<b>[/b][/u]</b></li></ul>

如果您想要顯示一段程式代碼或是任何需要固定寬度的文字, 您必須使用 <b>[pre][/pre]</b> 標籤來包含這些文字, 例如:<br/><br/><b>[pre]</b>echo "這是代碼";<b>[/pre]</b><br/><br/>當您瀏覽時, 所有被 <b>[pre][/pre]</b> 標籤包含的文字格式都將保持不變.

若一個完整的URL遵照此方式寫入至討論板，將會自動產生一個超連結連往該URL
<ul><li>http://開頭的會自動成為超連結 (如果自動連結有啟用的話)</li>
    <li>[url]可以做成一個超連結，請參考下例：<br/>
                  <b>[url=http://php.s3.to]</b>按這裡<b>[/url]</b></li>
    <li>下一個方式也有類似效果<br/>
           <b>[url]</b>php.s3.to<b>[/url]</b>
        <p>以上舉例說明中，自動產生了連結以刊登URL。使用者按下該連結將跳出一視窗。沒有"http://"是不能自動產生連結的。<br/>第二個方法裡可以省略"http://"。避免於URL中置入「"」記號，那可能會截斷網址。</p>
    </li></ul>
為了加上Email的連結，請按照以下方式刊登郵址: 
<ul><li><b>[email]</b>php@php.4all.cc<b>[/email]</b></li>
<li>下一個方式也可以做成一個Email連結<br/>
<b>[email=php@php.4all.cc]</b>我的Email<b>[/email]</b></li>
</ul>

BBCode 代碼提供標籤在您的文章中顯示圖像. 使用前, 請記住兩件重要的事;  第一, 許多使用者並不樂於見到文章中有太多的圖片, 第二, 您的圖片必須是能在網路上顯示的 (例如: 不能是您電腦上的檔案, 除非您的電腦是台網路伺服器). 若要顯示圖像, 可以使用 <b>[img][/img]</b> 標籤並指定圖像連結網址,  例如:<br/><br/><b>[img]</b>http://www.google.com/intl/en_com/images/logo_plain.png<b>[/img]</b><br/><br/>如同在先前網址連結的說明一樣, 您也可以使用圖片網址超連結 <b>[url][/url]</b> 的標籤, 例如:<br/><br/><b>[url=http://www.google.com/][img]</b>http://www.google.com/intl/en_com/images/logo_plain.png<b>[/img][/url]</b><br/><br/>將產生:<br/><br/><a href="http://www.google.com/" rel="_blank"><img src="http://www.google.com/intl/en_com/images/logo_plain.png" alt="http://www.google.com/intl/en_com/images/logo_plain.png" border="0" /></a>
<hr/>
EOH;
		foot($dat);
		echo $dat;
	}
}
?>