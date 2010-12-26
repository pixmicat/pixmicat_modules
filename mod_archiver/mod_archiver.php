<?php
/*
mod_archiver : Pixmicat! Archiver 靜態庫存頁面(精華區)生成
*/

class mod_archiver{
	var $page;
	var $ARCHIVE_ROOT, $MULTI_COPY, $ADMIN_ONLY;
	var $PUSHPOST_SEPARATOR, $PROCESS_PUSHPOST;

	function mod_archiver(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->page = $PMS->getModulePageURL(__CLASS__);

		$this->ARCHIVE_ROOT = './archives/'; // 生成靜態庫存頁面之存放位置
		$this->MULTI_COPY = true; // 容許同一串有多份存檔
		$this->ADMIN_ONLY = true; // 只容許管理員生成靜態庫存頁面

		$this->PUSHPOST_SEPARATOR = '[MOD_PUSHPOST_USE]';
		$this->PROCESS_PUSHPOST = 1;	// 處理推文 (是：1 否：0)
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_archiver : Pixmicat! Archiver 靜態庫存頁面(精華區)生成';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '5th.Release (v100905)';
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar, $isReply){
		global $PMS;
		$linkbar .= '[<a href="'.$this->ARCHIVE_ROOT.'">精華區</a>]'."\n";
	}

	function ModulePage(){
		if($this->ADMIN_ONLY && !adminAuthenticate('check')) {	// 只容許管理員生成靜態庫存頁面
			echo 'Access Denied.';
			return;
		}
		
		$res = isset($_GET['res']) ? $_GET['res'] : 0; // 欲生成靜態庫存頁面之討論串編號

		if(!$res || (!$this->MULTI_COPY && glob($this->ARCHIVE_ROOT.$res.'-*.xml'))){
			echo('No argument or the archive already existed.'); // 參數不對或XML檔案已存在
		}else{
			$this->GenerateArchive($res); // 生成靜態庫存頁面
			echo 'FINISH.';
		}
	}

	function autoHookAdminList(&$modFunc, $post, $isres){
		global $PMS;
		if(!$isres) $modFunc .= '[<a href="'.$this->page.'&amp;res='.$post['no'].'">存</a>]';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if($this->ADMIN_ONLY) return; // 只允許管理員生成
		$arrLabels['{$QUOTEBTN}'] .= '&nbsp;[<a href="'.$this->page.'&amp;res='.$post['no'].'">存</a>]';
	}

	/* 取出討論串結構並製成XML結構 */
	function GenerateArchive($res){
		global $PIO, $FileIO;
		$aryNO = $aryNAME = $aryDATE = $arySUBJECT = $aryCOMMENT = $aryPUSHPOST = $aryCATEGORY = $aryIMAGE = array(); // 討論串結構陣列

		/* 第一部份：先製成討論串結構陣列 */
		$tid = $PIO->fetchPostList($res); // 取得特定討論串之編號結構
		$post = $PIO->fetchPosts($tid); // 取出資料
		$post_count = count($post);
		if($post_count==0){ echo 'Not found.'; break; }
		for($i = 0; $i < $post_count; $i++){
			extract($post[$i]);
			$name = preg_replace('/(◆.{10})/', '<span class="nor">$1</span>', $name); // Trip取消粗體
			if(USE_CATEGORY) {
				$ary_category2 = array(); $ary_category = explode(',', str_replace('&#44;', ',', $category)); $ary_category = array_map('trim', $ary_category);
				foreach($ary_category as $c) if($c) $ary_category2[]=$c;
				$category = implode(', ', $ary_category2);
			} else $category = '';
			
			$push_post = '';
			if($this->PROCESS_PUSHPOST == 1) {	// 處理推文
				//echo $com;
				$comArr = explode($this->PUSHPOST_SEPARATOR.'<br />', $com);
				if(count($comArr) > 1) {	// 有推文
					$com = $comArr[0];
					$push_post = $comArr[1];
				}
			}
			$aryNO[] = $no; $aryNAME[] = $name; $aryDATE[] = $now; $arySUBJECT[] = $sub; $aryCOMMENT[] = $com; $aryPUSHPOST[] = $push_post; $aryCATEGORY[] = $category; // 置入陣列
			if($FileIO->imageExists($tim.$ext)){ // 有貼圖
				$size = (int)($FileIO->getImageFilesize($tim.$ext) / 1024);
				$aryIMAGE[] = array($size, $imgw.'x'.$imgh, $ext, $tim);
			}else $aryIMAGE[] = '';
		}
		$archiveDate = date('YmdHis');

		/* 第二部份：生成XML結構 */
		$tmp_c = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="archivestyle.xsl"?>
<threads no="'.$aryNO[0].'">
	<meta creator="'.$this->getModuleVersionInfo().'"'.($this->MULTI_COPY?' archivedate="'.$archiveDate.'"':'').' />
	<name>'.$aryNAME[0].'</name>
	<date>'.$aryDATE[0].'</date>
	<subject>'.$arySUBJECT[0].'</subject>
	<comment>'.$aryCOMMENT[0].'</comment>
	<pushpost>'.$aryPUSHPOST[0].'</pushpost>
	<category>'.$aryCATEGORY[0].'</category>
';
		if($aryIMAGE[0]) $tmp_c .= '	<image kbyte="'.$aryIMAGE[0][0].'" scale="'.$aryIMAGE[0][1].'" ext="'.$aryIMAGE[0][2].'">'.$aryIMAGE[0][3].'</image>';
		else $tmp_c .= '	<image kbyte="" scale="" ext=""></image>';
		for($p = 1; $p < $post_count; $p++){
			$tmp_c .= '
	<reply no="'.$aryNO[$p].'">
		<name>'.$aryNAME[$p].'</name>
		<date>'.$aryDATE[$p].'</date>
		<subject>'.$arySUBJECT[$p].'</subject>
		<comment>'.$aryCOMMENT[$p].'</comment>
		<pushpost>'.$aryPUSHPOST[$p].'</pushpost>
		<category>'.$aryCATEGORY[$p].'</category>
';
			if($aryIMAGE[$p]) $tmp_c .= '		<image kbyte="'.$aryIMAGE[$p][0].'" scale="'.$aryIMAGE[$p][1].'" ext="'.$aryIMAGE[$p][2].'">'.$aryIMAGE[$p][3].'</image>';
			else $tmp_c .= '		<image kbyte="" scale="" ext=""></image>';
		$tmp_c .= '
	</reply>';
		}
		$tmp_c .= '
</threads>';

		/* 第三部份：儲存檔案 */
		if(!($fp = fopen($this->ARCHIVE_ROOT.$res.'-'.$archiveDate.'.xml', 'w'))) exit('File open error!');
		stream_set_write_buffer($fp, 0); // 立刻寫入不用緩衝
		fwrite($fp, $tmp_c); // 寫入XML結構
		fclose($fp);
		// 另開新資料夾保存圖片
		$nfolder = $this->ARCHIVE_ROOT.$res.'-'.$archiveDate.'_files/'; // 保存圖檔資料夾
		if(!is_dir($nfolder)){ mkdir($nfolder); chmod($nfolder, 0777); } // 建立存放資料夾
		for($n = 0; $n < $post_count; $n++){
			if($aryIMAGE[$n]){
				$img = $aryIMAGE[$n][3].$aryIMAGE[$n][2]; // 原圖
				$thumb = $aryIMAGE[$n][3].'s.jpg'; // 預覽圖
				if(FILEIO_BACKEND=='normal'){ // 一般後端，獨立判斷避免http wrapper被關閉無法複製的問題
					$img2 = IMG_DIR.$aryIMAGE[$n][3].$aryIMAGE[$n][2];
					$thumb2 = THUMB_DIR.$aryIMAGE[$n][3].'s.jpg';
				}else{
					$img2 = $FileIO->getImageURL($aryIMAGE[$n][3].$aryIMAGE[$n][2]);
					$thumb2 = $FileIO->getImageURL($aryIMAGE[$n][3].'s.jpg');
				}
				if($FileIO->imageExists($img)) copy($img2, $nfolder.$img);
				if($FileIO->imageExists($thumb)) copy($thumb2, $nfolder.$thumb);
			}
		}
	}
}
?>