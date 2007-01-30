<?php
/*
mod_archiver : Pixmicat! Archiver 靜態庫存頁面(精華區)生成
by: scribe
*/

class mod_archiver{
	var $ARCHIVE_ROOT;

	function mod_archiver(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_archiver'); // 向系統登記模組專屬獨立頁面

		$this->ARCHIVE_ROOT = './archives/'; // 生成靜態庫存頁面之存放位置
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_archiver : Pixmicat! Archiver 靜態庫存頁面(精華區)生成';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'Pixmicat! Archiver Module v070130';
	}

	function ModulePage(){
		$res = isset($_GET['res']) ? $_GET['res'] : 0; // 欲生成靜態庫存頁面之討論串編號

		if(!$res || file_exists($this->ARCHIVE_ROOT.$res.'.xml')){
			echo('No argument or the archive already existed.'); // 參數不對或XML檔案已存在
		}else{
			$this->GenerateArchive($res); // 生成靜態庫存頁面
			echo 'FINISH.';
		}
	}

	/* 取出討論串結構並製成XML結構 */
	function GenerateArchive($res){
		global $PIO, $FileIO;
		$aryNO = $aryNAME = $aryDATE = $arySUBJECT = $aryCOMMENT = $aryIMAGE = array(); // 討論串結構陣列

		/* 第一部份：先製成討論串結構陣列 */
		$tid = $PIO->fetchPostList($res); // 取得特定討論串之編號結構
		$post = $PIO->fetchPosts($tid); // 取出資料
		$post_count = count($post);
		if($post_count==0){ echo 'Not found.'; break; }
		for($i = 0; $i < $post_count; $i++){
			list($imgw,$imgh,$no,$now,$name,$sub,$com,$ext,$tim) = array($post[$i]['imgw'], $post[$i]['imgh'], $post[$i]['no'], $post[$i]['now'], $post[$i]['name'], $post[$i]['sub'], $post[$i]['com'], $post[$i]['ext'], $post[$i]['tim']);
			$name = preg_replace('/(◆.{10})/', '<span class="nor">$1</span>', $name); // Trip取消粗體
			$aryNO[] = $no; $aryNAME[] = $name; $aryDATE[] = $now; $arySUBJECT[] = $sub; $aryCOMMENT[] = $com; // 置入陣列
			if($FileIO->imageExists($tim.$ext)){ // 有貼圖
				$size = (int)($FileIO->getImageFilesize($tim.$ext) / 1024);
				$aryIMAGE[] = array($size, $imgw.'x'.$imgh, $ext, $tim);
			}else $aryIMAGE[] = '';
		}

		/* 第二部份：生成XML結構 */
		$tmp_c = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="archivestyle.xsl"?>
<threads no="'.$aryNO[0].'">
	<meta creator="'.$this->getModuleVersionInfo().'" />
	<name>'.$aryNAME[0].'</name>
	<date>'.$aryDATE[0].'</date>
	<subject>'.$arySUBJECT[0].'</subject>
	<comment>'.$aryCOMMENT[0].'</comment>
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
';
			if($aryIMAGE[$p]) $tmp_c .= '		<image kbyte="'.$aryIMAGE[$p][0].'" scale="'.$aryIMAGE[$p][1].'" ext="'.$aryIMAGE[$p][2].'">'.$aryIMAGE[$p][3].'</image>';
			else $tmp_c .= '		<image kbyte="" scale="" ext=""></image>';
		$tmp_c .= '
	</reply>';
		}
		$tmp_c .= '
</threads>';

		/* 第三部份：儲存檔案 */
		$fp = fopen($this->ARCHIVE_ROOT.$res.'.xml', 'w');
		stream_set_write_buffer($fp, 0); // 立刻寫入不用緩衝
		fwrite($fp, $tmp_c); // 寫入XML結構
		fclose($fp);
		// 另開新資料夾保存圖片
		$nfolder = $this->ARCHIVE_ROOT.$res.'_files/'; // 保存圖檔資料夾
		if(!is_dir($nfolder)){ mkdir($nfolder); chmod($nfolder, 0777); } // 建立存放資料夾
		for($n = 0; $n < $post_count; $n++){
			if($aryIMAGE[$n]){
				if($FileIO->imageExists($aryIMAGE[$n][3].$aryIMAGE[$n][2])) copy($FileIO->getImageURL($aryIMAGE[$n][3].$aryIMAGE[$n][2], true), $nfolder.$aryIMAGE[$n][3].$aryIMAGE[$n][2]); // 原圖
				if($FileIO->imageExists($aryIMAGE[$n][3].'s.jpg')) copy($FileIO->getImageURL($aryIMAGE[$n][3].'s.jpg', true), $nfolder.$aryIMAGE[$n][3].'s.jpg'); // 縮圖
			}
		}
	}
}
?>