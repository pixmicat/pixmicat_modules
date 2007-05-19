<?php
/* mod_exif : EXIF information (Pre-Alpha)
 * $Id$
 * exif.php from http://www.rjk-hosting.co.uk/programs/prog.php?id=4
 */
class mod_exif{
	var $myPage;
	
	function mod_exif(){
		global $PMS, $PIO, $FileIO;

		$PMS->hookModuleMethod('ModulePage', 'mod_exif'); // 向系統登記模組專屬獨立頁面
		$this->myPage = $PMS->getModulePageURL('mod_exif'); // 基底位置
	}

	function getModuleName(){
		return 'mod_exif';
	}

	function getModuleVersionInfo(){
		return 'mod_exif : EXIF information (Pre-Alpha)';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if(FILEIO_BACKEND=='normal') { // work for normal File I/O only
			if($arrLabels['{$IMG_BAR}']!='') {
				preg_match('/rel\="_blank">(.*)<\/a>/', $arrLabels['{$IMG_BAR}'], $matches);
				$arrLabels['{$IMG_BAR}'] .= '<small> <a href="'.$this->myPage.'&amp;file='.$matches[1].'">[EXIF]</a></small>';
			}
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function ModulePage(){
		global $PMS, $FileIO;
		$file=isset($_GET['file'])?$_GET['file']:'';
		if($file && $FileIO->imageExists($file)){
			$pfile=$FileIO->getImageURL($file);
			if(function_exists("exif_read_data")) {
				echo "DEBUG: Using exif_read_data<br/>";
				$exif_data = exif_read_data($pfile,0,true);
				if(isset($exif_data['FILE'])) unset($exif_data['FILE']);
				if(isset($exif_data['COMPUTED'])) unset($exif_data['COMPUTED']);
				echo !count($exif_data) ? "No EXIF data found.<br />" : "Image contains EXIF data:<br />";
				foreach($exif_data as $key=>$section) {
				   foreach($section as $name=>$val) {
				       echo "$key.$name: $val<br />";
				   }
				}
			} else {
				echo "DEBUG: Using exif.php library<br/>";
				include('exif.php');
				exif($FileIO->getImageURL($file));
				echo !count($exif_data) ? "No EXIF data found.<br />" : "Image contains EXIF data:<br />";
				foreach($exif_data as $key=>$val)
				       echo "$key: $val<br />";
			}
		}
		echo "<a href='javascript:history.go(-1)'>[Back]</a>";
	}
}
?>