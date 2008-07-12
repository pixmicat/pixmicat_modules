<?php
/*
mod_siokara : Pixmicat! siokara management subset (Alpha)
by: scribe
*/

class mod_siokara{
	var $mypage;

	function mod_siokara(){
		global $PMS;
		AttachLanguage(array($this,'_loadLanguage')); // 載入語言檔
		$PMS->hookModuleMethod('ModulePage', 'mod_siokara'); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL('mod_siokara');
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_siokara : しおから式管理擴充套件';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'v071119';
	}

	function autoHookAdminList(&$modFunc, $post, $isres){
		global $PMS,$FileIO;
		extract($post);

		$fh=new FlagHelper($status);
		if(!$isres) $modFunc .= '[<a href="'.$this->mypage.'&amp;no='.$no.'&amp;action=sage"'.($fh->value('asage')?' title="'._T('siokara_admin_ufsage').'">s':' title="'._T('siokara_admin_fsage').'">S').'</a>]';
		if($ext && $FileIO->imageExists($tim.$ext)) {
			$modFunc .= '[<a href="'.$this->mypage.'&amp;no='.$no.'&amp;action=thumb"'.($fh->value('htmb')?' title="'._T('siokara_admin_uhtmb').'">t':' title="'._T('siokara_admin_htmb').'">T').'</a>]';
			if($ext == '.gif') $modFunc .= '[<a href="'.$this->mypage.'&amp;no='.$no.'&amp;action=agif"'.($fh->value('agif')?' title="'._T('siokara_admin_agif').'">g':' title="'._T('siokara_admin_uagif').'">G').'</a>]';
		}
	}

	function autoHookPostForm(&$form){
		global $language;
		$form .= '<tr><td class="Form_bg"><b>'._T('siokara_extra_opt').'</b></td><td>[<input type="checkbox" name="anigif" id="anigif" value="on" />'._T('siokara_anigif').']</td></tr>';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $PIO,$FileIO;
		$fh = new FlagHelper($post['status']);
		if($fh->value('asage')) { // 強制sage
			if($arrLabels['{$COM}']) $arrLabels['{$WARN_ENDREPLY}'].='<br/><span class="warn_txt"><small>'._T('siokara_warn_sage').'<br/></small></span>';
			else $arrLabels['{$WARN_ENDREPLY}'] = '<span class="warn_txt"><small>'._T('siokara_warn_sage').'<br/></small></span>';
		}
		if($FileIO->imageExists($post['tim'].$post['ext'])) {
			if($fh->value('agif')) { // 動態GIF
				$imgURL = $FileIO->getImageURL($post['tim'].$post['ext']);
				$arrLabels['{$IMG_SRC}']=preg_replace('/<img src=".*"/U','<img src="'.$imgURL.'"',$arrLabels['{$IMG_SRC}']);
				$arrLabels['{$IMG_BAR}'].='<small>['._T('siokara_anigif').']</small>';
			}
			if($fh->value('htmb')) { // 替換縮圖
				$arrLabels['{$IMG_SRC}']=preg_replace('/<img src=".*" style="width: \d+px; height: \d+px;"/U','<img src="nothumb.gif"',$arrLabels['{$IMG_SRC}']);
				$arrLabels['{$COM}'].='<br/><br/><span class="warn_txt"><small>'._T('siokara_warn_hidethumb').'<br/></small></span>';
			}
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		global $PIO;
		$fh = new FlagHelper($status);
		$size = @getimagesize($dest);

		if(isset($_POST['anigif']) && ($size[2] == 1)) { // 動態GIF
			$fh->toggle('agif');
			$status = $fh->toString();
		}

		if($isReply) {
			$rpost = $PIO->fetchPosts($isReply); // 強制sage
			$rfh = new FlagHelper($rpost[0]['status']);
			if($rfh->value('asage')) $age = false;
		}

	}

	function _loadLanguage() {
		global $language;
		if(PIXMICAT_LANGUAGE != 'zh_TW' && PIXMICAT_LANGUAGE != 'ja_JP' && PIXMICAT_LANGUAGE != 'en_US') $lang = 'en_US';
		else $lang = PIXMICAT_LANGUAGE;

		// builtin language strings
		if($lang == 'zh_TW') {
			$language['siokara_admin_fsage'] = '強制sage';
			$language['siokara_admin_ufsage'] = '解除強制sage';
			$language['siokara_admin_htmb'] = '替換縮圖';
			$language['siokara_admin_uhtmb'] = '解除替換縮圖';
			$language['siokara_admin_agif'] = '替換縮圖為靜態縮圖';
			$language['siokara_admin_uagif'] = '解除替換靜態縮圖';
			$language['siokara_extra_opt'] = '附加選項';
			$language['siokara_anigif'] = '動態GIF';
			$language['siokara_warn_sage'] = '此討論串已被強制sage。';
			$language['siokara_warn_hidethumb'] = '縮圖已被替換。';
		} else if($lang == 'ja_JP'){
			$language['siokara_admin_fsage'] = '強制sage';
			$language['siokara_admin_ufsage'] = '強制sage解除';
			$language['siokara_admin_htmb'] = 'サムネイル差替';
			$language['siokara_admin_uhtmb'] = 'サムネイル差替解除';
			$language['siokara_admin_agif'] = 'GIFをサムネイル化する';
			$language['siokara_admin_uagif'] = 'GIFサムネイル化解除';
			$language['siokara_extra_opt'] = '余分なオプション';
			$language['siokara_anigif'] = 'GIFアニメ';
			$language['siokara_warn_sage'] = 'このスレは管理者によりsage指定されています。理由はお察しください。';
			$language['siokara_warn_hidethumb'] = 'この記事の画像は管理者によりサムネイルが差し替えられています。理由はお察しください。<br/>サムネイルをクリックすると元の画像を表示します。';
		} else if($lang == 'en_US'){
			$language['siokara_admin_fsage'] = 'Force sage';
			$language['siokara_admin_ufsage'] = 'unForce sage';
			$language['siokara_admin_htmb'] = 'Replace thumbnail with nothumb image';
			$language['siokara_admin_uhtmb'] = 'Use orginal thumbnail';
			$language['siokara_admin_agif'] = 'Use still image of GIF image';
			$language['siokara_admin_uagif'] = 'Use Animated GIF';
			$language['siokara_extra_opt'] = 'Extra Options';
			$language['siokara_anigif'] = 'Animated GIF';
			$language['siokara_warn_sage'] = 'This thread was forced sage by administrator.';
			$language['siokara_warn_hidethumb'] = 'The thumbnail was replaced by administrator.';
		}

		// external language file
		if(file_exists($langfile=str_replace('.php','.lang.php',__FILE__))) include_once($langfile);
	}

	function ModulePage(){
		global $PIO,$FileIO;
		if(!adminAuthenticate('check')) die('403 Access denied');
		$act=isset($_GET['action'])?$_GET['action']:'';
		
		switch($act) {
			case 'sage'; // 強制sage
				if($PIO->isThread($_GET['no'])) {
					$post = $PIO->fetchPosts($_GET['no']);
					if(!count($post)) die('[Error] Post does not exist.');
					$flgh = $PIO->getPostStatus($post[0]['status']);
					$flgh->toggle('asage');
					$PIO->setPostStatus($post[0]['no'], $flgh->toString());
					$PIO->dbCommit();
					die('Done. Please go back.');
				} else die('[Error] Thread does not exist.');
				break;
			case 'thumb'; // 替換縮圖
				$post = $PIO->fetchPosts($_GET['no']);
				if(!count($post)) die('[Error] Post does not exist.');
				if($post[0]['ext']) {
					if(!$FileIO->imageExists($post[0]['tim'].$post[0]['ext'])) die('[Error] attachment does not exist.');
					$flgh = $PIO->getPostStatus($post[0]['status']);
					$flgh->toggle('htmb');
					$PIO->setPostStatus($post[0]['no'], $flgh->toString());
					$PIO->dbCommit();
					die('Done. Please go back.');
				} else die('[Error] Post does not have attechment.');
				break;
			case 'agif'; // 動態GIF
				$post = $PIO->fetchPosts($_GET['no']);
				if(!count($post)) die('[Error] Post does not exist.');
				if($post[0]['ext'] && $post[0]['ext'] == '.gif') {
					if(!$FileIO->imageExists($post[0]['tim'].$post[0]['ext'])) die('[Error] attachment does not exist.');
					$flgh = $PIO->getPostStatus($post[0]['status']);
					$flgh->toggle('agif');
					$PIO->setPostStatus($post[0]['no'], $flgh->toString());
					$PIO->dbCommit();
					die('Done. Please go back.');
				} else die('[Error] Post does not have attechment.');
				break;
		}
	}

}
?>