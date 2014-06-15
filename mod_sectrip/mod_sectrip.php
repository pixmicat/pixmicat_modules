<?php
class mod_sectrip extends ModuleHelper {
	private $secure_salt='./secsalt.php';// 如沒有 <pmc目錄>/secsalt.php 則自動生成
	private $secureTrip;
	private $myPage;
	private $urlcount;
	
	public function __construct($PMS) {
		parent::__construct($PMS);
	
		$this->myPage = $this->getModulePageURL(); // 基底位置
		$secsaltfile='./secsalt.php';
		if(!file_exists($secsaltfile)) { // 如沒有 <pmc目錄>/secsalt.php 則自動生成
			$fp=fopen($secsaltfile,'wb');
			fputs($fp,'<?php $this->secure_salt = \''.md5(uniqid(rand(), true)).'\';?>');
			fclose($fp);
		}
	    include_once($secsaltfile); // 讀入sceure trip salt
	}

	public function getModuleName(){
		return 'mod_sectrip : Secure Tripcode';
	}

	public function getModuleVersionInfo(){
		return 'v140606';
	}

	public function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo){
		$name=preg_replace('/«(.*)»/','($1)',$name); //防止偽冒
		if(preg_match('/\!sectrip\s*#(.*)/i',$name,$m)) { // name!sectrip#securetrip
			$this->secureTrip=$m[1];
			$name=str_replace($m[0],'',$name); // 跳過普通Trip處理
		}
	}

	public function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if($this->secureTrip) { // 如有sectrip
			$name .= "<span class='nor'>«".substr(base64_encode(pack("H*",md5($this->secure_salt.$this->secureTrip))),2,16)."»</span>";
		}
	}

	public function autoHookPostInfo(&$postinfo){
		$postinfo .= "<li>可使用 <a href='".$this->myPage."' rel='_blank'>Secure Tripcode</a></li>\n";
	}

	public function ModulePage(){
		$dat='';
		head($dat);
		$dat.=<<<EOH
<p>Secure Tripcode提供了一個更高安全性的身份認證方案。</p>
<p>Sceure Tripcode使用了伺服器端salt(伺服器自動生成或由管理員自行指定)以及不限制長度的密碼，將有效減少Tripcode的碰撞機率。</p>
<dl><dt>使用方法：</dt><dd>
	只要在Tripcode前加上"!sectrip"(不含引號)即可。<br/>
	例如： Name#mypass → Name!sectrip#mypass<br/>
	顯示將有改變： <strong>Name</strong>◆39DOV4DpKY → <strong>Name</strong>«ifS1AJ05UCt/Onmt»
</dd></dl>
<hr/>
EOH;
		foot($dat);
		echo $dat;
	}

}//End-of-Module
