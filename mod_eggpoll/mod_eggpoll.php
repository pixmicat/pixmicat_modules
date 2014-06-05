<?php
class mod_eggpoll extends ModuleHelper {
	private $mypage,$conn;
	private	$rankDB = 'eggpoll'; // poll database
	private	$rankNames = array('噓爆','噓','中立','推','大推');
	private	$rankAlphas = array('30','60','100','100','100');
	private	$rankColors = array('#f00','#a00','','#274','#4b7');
	private	$rankMin = 3; // 開始評價的最少票數
	private	$oneSidedCount = 5; // 一面倒評價的最少票數
	private	$shrinkThread = true; // 摺疊開版文時是否摺疊整個串 (festival.tpl用)
	private	$daysThreshold = 14; // 詳細投票記錄保留日數
	private	$addBR = 2; // #postform_main - #threads 之間插入空行? (0=不插入/1=在#postform_main後/2=在#threads前)

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->mypage = $this->getModulePageURL();
	}
 
	/* Get the name of module */
	public function getModuleName(){
		return 'mod_eggpoll : 文章評分機制';
	}

	/* Get the module version infomation */
	public function getModuleVersionInfo(){
		return '7th.Release-dev (v140605)';
	}

	public function autoHookHead(&$txt, $isReply){
		//global $language;
		//client side include jquery if not include.
		$txt.='<script type="text/javascript">
	window.jQuery || document.write("\x3Cscript src=\"//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js\">\x3C/script>");
</script> 
<style type="text/css">
.eggpoll {font-size: 0.8em;}
.eggpoll .rankup, .eggpoll .rankdown { color:#fff; cursor:pointer; }
.eggpoll .rankup { background-color: #c66; }
.eggpoll .rankdown { background-color: #66c; }
.eggpoll .rtoggle { display:none; }
.eggpoll .rtoggle, .eggpoll .rtoggled { color:blue; cursor:pointer; padding:0 0.27em; font-weight:bold; border: 1px solid blue; }
</style>
<script type="text/javascript">
var postNos = new Array();
var RankTexts = ["'.implode('","',$this->rankNames).'"];
var RankAlphas = ["'.implode('","',$this->rankAlphas).'"];
var RankColors = ["'.implode('","',$this->rankColors).'"];
var shrinkThread = '.$this->shrinkThread.';
// <![CDATA[
function mod_eggpollRank(no,rank){
	$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").hide();
	$.ajax({
		url: "'.str_replace('&amp;', '&', $this->mypage).'&no="+no+"&rank="+rank,
		type: "GET",
		success: function(rv){
			if(rv.substr(0, 4)!=="+OK "){
				$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").css("display","inline");
				alert(rv);
				return false;
			}
			rv = $.parseJSON(rv.substr(4));
			updateAppearance(rv.polls[0].no,rv.polls[0].rank,1);
		},
		error: function(){
			$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").css("display","inline");
			alert("Network error.");
		}
	});
}
function mod_eggpollToggle(o,no){
	if(o.className=="rtoggle") {
		o.className="rtoggled";
		$("div#r"+no+">.quote, div#r"+no+"+div.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideToggle();
		if(shrinkThread) $("div#g"+no).height("auto");
	} else {
		o.className="rtoggle";
		$("div#r"+no+">.quote, div#r"+no+"+div.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideUp();
		if(shrinkThread) $("div#g"+no).animate({height:"1.35em"});
	}
}
function updateAppearance(no,rank,voted) {
	if(RankAlphas[rank] != "null") {
		$("div#r"+no+", div#r"+no+"+div.quote, div#g"+no+">.quote").fadeTo("fast",parseInt(RankAlphas[rank])/100);
	}
	$("span#ep"+no+">.ranktext").html(RankTexts[rank]);
	$("span#ep"+no+">.ranktext").css("color",RankColors[rank]);
	if(rank==0) {
		$("span#ep"+no+">.rtoggle").show();
		$("div#r"+no+">.quote, div#r"+no+"+div.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideUp();
		if(shrinkThread) $("div#g"+no).animate({height:"1.35em"});
	}
	if(voted) {
		$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").hide();
	}
}
function getPollValues() {
	'.($this->addBR==1?'$("#postform_main").after("<br/>");':($this->addBR==2?'$("#threads").before("<br/>");':'')).'
	$.getJSON("'.str_replace('&amp;', '&', $this->mypage).'&get="+postNos,function(data) {
		$.each(data.polls, function(i,poll){
			updateAppearance(poll.no,poll.rank,poll.voted);
		});
	});
}
// ]]>
</script>';
	}

	public function autoHookFoot(&$foot){ 
		$foot .= '<script type="text/javascript">if(postNos.length)getPollValues();</script>';
	}

	public function autoHookThreadPost(&$arrLabels, $post, $isReply){
		//global $language, $PIO;
		$arrLabels['{$QUOTEBTN}'] .= '&#xA0;<script type="text/javascript">postNos.push('.$post['no'].');</script><span class="eggpoll" id="ep'.$post['no'].'"><span class="rankup" onclick="mod_eggpollRank('.$post['no'].',1)">＋</span><span class="rankdown" onclick="mod_eggpollRank('.$post['no'].',0)">－</span><span class="ranktext">'.$this->rankNames[2].'</span> <a class="rtoggle" onclick="mod_eggpollToggle(this,'.$post['no'].')">↕</a></span>';
	}

	public function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	private function _calcRank($up,$down) {
		$total = $up+$down;
		if(!$total) return 2; //prevent divide by zero
		$u = $up / $total;
		$d = 1 - $u;
		
		if($down == 0) {
			if($up >= $this->oneSidedCount) return 4;
			else if($up >= $this->oneSidedCount/2) return 3;
			else return 2;
		} else if($up == 0) {
			if($down >= $this->oneSidedCount) return 0;
			else if($down >= $this->oneSidedCount/2) return 1;
			else return 2;
		} else {
			if($u >= 0.65) return 4;
			else if($u >= 0.55) return 3;
			else if($d >= 0.65) return 0;
			else if($d >= 0.55) return 1;
			else return 2;
		}
	}

	private function _getPollValuesPDO($no,&$file_db) {
		$ip = getREMOTE_ADDR(); $datestr = gmdate('Ymd',time()+TIME_ZONE*60*60); $voted = array(); $first = true;
		$qry = 'SELECT no FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no IN('.$no.')';
		$rs=$file_db->query($qry);  	
    	while( ($number = $rs->fetchColumn())!==false ){
    		$voted[$number]=1;
    	}
    	unset($rs);

		$qry = 'SELECT no,up,down FROM eggpoll_votes WHERE no IN('.$no.')';
		$rs = $file_db->query($qry);
		echo '{
			"polls" : [';
		    while ($row = $rs->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
				if(!$first) echo ',';
				echo '{ "no" : '.$row['no'].',
				"rank" : '.$this->_calcRank($row['up'],$row['down']).',
				"voted" : '.(isset($voted[$row['no']]) ? 1 : 0).' }';
				if($first) $first=false;
		    } 
		echo ']
}';
		unset($rs);
	}

	private function _getPollValuesOld($no) {
		$ip = getREMOTE_ADDR(); $datestr = gmdate('Ymd',time()+TIME_ZONE*60*60); $voted = array(); $first = true;
		$qry = 'SELECT no,ip,date FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no IN('.$no.')';
		$rs = sqlite_query($this->conn,$qry);
		while($row = sqlite_fetch_array($rs)) { $voted[$row['no']]=1; }

		$qry = 'SELECT * FROM eggpoll_votes WHERE no IN('.$no.')';
		$rs = sqlite_query($this->conn,$qry);
		echo '{
			"polls" : [';
		while($row = sqlite_fetch_array($rs)) {
			if(!$first) echo ',';
			echo '{ "no" : '.$row['no'].',
				"rank" : '.$this->_calcRank($row['up'],$row['down']).',
				"voted" : '.(isset($voted[$row['no']]) ? 1 : 0).' }';
			if($first) $first=false;
		}
		echo ']
}';
	}

	private function _ModulePagePDO(){
		$sqlerr = ''; 
		$nodb = false;
		$PIO = PMCLibrary::getPIOInstance();
		$PTE = PMCLibrary::getPTEInstance();

		if(!file_exists($this->rankDB.'.sqlite3')) $nodb = true;
			$file_db = new PDO('sqlite:'.$this->rankDB.'.sqlite3');
    		// Set errormode to exceptions
    		$file_db->setAttribute(PDO::ATTR_ERRMODE, 
                            PDO::ERRMODE_EXCEPTION);
		if($nodb) {
			$str = "CREATE TABLE [eggpoll_votes] (
[no] INTEGER  PRIMARY KEY NOT NULL,
[up] INTEGER DEFAULT '0' NOT NULL,
[down] INTEGER DEFAULT '0' NOT NULL
);
CREATE TABLE [eggpoll_detail] (
[no] INTEGER NOT NULL,
[option] INTEGER DEFAULT '0' NOT NULL,
[ip] TEXT NOT NULL,
[date] TEXT  NOT NULL
);
CREATE INDEX eggpoll_detail_index_ip_date ON eggpoll_detail(ip,date);";
			$file_db->exec($str);
		}

		if(isset($_GET['get'])) {
			$this->_getPollValuesPDO($_GET['get'],$file_db);
		}
		else if(isset($_GET['no'])&&isset($_GET['rank'])){
			$ip = getREMOTE_ADDR(); $tim = time()+TIME_ZONE*60*60;
			$datestr = gmdate('Ymd',$tim); $deldate = gmdate('Ymd',strtotime('-'.$this->daysThreshold.' days',$tim));
			$no = intval($_GET['no']); $rank = intval($_GET['rank']);

			// 查IP
			$baninfo = '';
			$host = gethostbyaddr($ip);
			if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) die($this->_T('regist_ipfiltered', $baninfo));

			$post = $PIO->fetchPosts($no);
			if(!count($post)) die('[Error] Post does not exist.'); // 被評之文章不存在

			// 檢查是否已經投票
			$qry = 'SELECT no,ip,date FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no ="'.$no.'"';
			$rs = $file_db->query($qry);
			if($rs->fetchColumn()!==false) die('[Error] Already voted.');
			unset($rs);
			// 刐除舊詳細評價
			$qry = 'SELECT 1 FROM eggpoll_detail WHERE date < "'.$deldate.'" LIMIT 1';
			$rs = $file_db->query($qry);
			if($rs->fetchColumn()!==false) {
				$str = 'DELETE FROM eggpoll_detail WHERE date < "'.$deldate.'"';
				$affected_row = $file_db->exec($str);
				if ($affected_row > 0){
					$file_db->exec('VACUUM');
					unset($rs);
				}else{ 
					print_r($file_db->errorInfo());
					unset($rs);
					unset($file_db);
					die ('db error1:');
				}
			} 
			$str = 'INSERT INTO eggpoll_detail (no,option,ip,date) VALUES ('.$no.','.$rank.',"'.$ip.'","'.$datestr.'");';
			$rs= $file_db->query($str);
			if($rs->rowCount() < 1) { 
				print_r($file_db->errorInfo()); 
				unset($file_db);
				unset($rs);
				die( 'db error2:'); 
			} 

			$qry = 'SELECT 1 FROM eggpoll_votes WHERE no ='.$no.';';
			$rs = $file_db->query($qry); 
			if( $rs->fetchColumn() === false) {
				$str = 'INSERT INTO eggpoll_votes (no,up,down) VALUES ('.$no.($rank?',1,0)':',0,1);');
			} else {
				if($rank)
					$str = 'UPDATE eggpoll_votes SET up = up+1 WHERE no='.$no.';';
				else
					$str = 'UPDATE eggpoll_votes SET down = down+1 WHERE no='.$no.';';
			}
			unset($rs);

			$rs=$file_db->query($str); 
			if($rs->rowCount() <= 0) { 
				print_r($file_db->errorInfo()); 
				unset($file_db);
				unset($rs);
				die('db error3');
			} 
			unset($rs);
			echo '+OK ';
			$this->_getPollValuesPDO($no,$file_db);
			unset($file_db);
		}
	}

	private function _ModulePageOld(){
		//global $PIO, $PTE, $language; $sqlerr = ''; $nodb = false;
		$sqlerr = ''; 
		$nodb = false;
		$PIO = PMCLibrary::getPIOInstance();
		$PTE = PMCLibrary::getPTEInstance();

		if(!file_exists($this->rankDB)) $nodb = true;
		$this->conn = sqlite_popen($this->rankDB.".db`",0666,$sqlerr);
		if($nodb) {
			$str = "CREATE TABLE [eggpoll_votes] (
[no] INTEGER  PRIMARY KEY NOT NULL,
[up] INTEGER DEFAULT '0' NOT NULL,
[down] INTEGER DEFAULT '0' NOT NULL
);
CREATE TABLE [eggpoll_detail] (
[no] INTEGER NOT NULL,
[option] INTEGER DEFAULT '0' NOT NULL,
[ip] TEXT NOT NULL,
[date] TEXT  NOT NULL
);
CREATE INDEX eggpoll_detail_index_ip_date ON eggpoll_detail(ip,date);";
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;
		}

		if(isset($_GET['get'])) {
			$this->_getPollValues($_GET['get']);
		}
		else if(isset($_GET['no'])&&isset($_GET['rank'])){
			$ip = getREMOTE_ADDR(); $tim = time()+TIME_ZONE*60*60;
			$datestr = gmdate('Ymd',$tim); $deldate = gmdate('Ymd',strtotime('-'.$this->daysThreshold.' days',$tim));
			$no = intval($_GET['no']); $rank = intval($_GET['rank']);

			// 查IP
			$baninfo = '';
			$host = gethostbyaddr($ip);
			if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) die(_T('regist_ipfiltered', $baninfo));

			$post = $PIO->fetchPosts($no);
			if(!count($post)) die('[Error] Post does not exist.'); // 被評之文章不存在

			// 檢查是否已經投票
			$qry = 'SELECT no,ip,date FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no ="'.$no.'"';
			$rs = sqlite_query($this->conn,$qry);
			if(sqlite_num_rows($rs)) die('[Error] Already voted.');

			// 刐除舊詳細評價
			$qry = 'SELECT date FROM eggpoll_detail WHERE date < "'.$deldate.'" LIMIT 1';
			$rs = sqlite_query($this->conn,$qry);
			if(sqlite_num_rows($rs)) {
				$str = 'DELETE FROM eggpoll_detail WHERE date < "'.$deldate.'"';
				sqlite_exec($this->conn,$str,$sqlerr);
				sqlite_exec($this->conn,'VACUUM',$sqlerr);
			}

			$str = 'INSERT INTO eggpoll_detail (no,option,ip,date) VALUES ('.$no.','.$rank.',"'.$ip.'","'.$datestr.'")';
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;

			$qry = 'SELECT * FROM eggpoll_votes WHERE no ='.$no;
			$rs = sqlite_query($this->conn,$qry);
			if(!sqlite_num_rows($rs)) {
				$str = 'INSERT INTO eggpoll_votes (no,up,down) VALUES ('.$no.($rank?',1,0)':',0,1)');
			} else {
				if($rank)
					$str = 'UPDATE eggpoll_votes SET up = up+1 WHERE no='.$no;
				else
					$str = 'UPDATE eggpoll_votes SET down = down+1 WHERE no='.$no;
			}
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;

			echo '+OK ';
			$this->_getPollValuesOld($no);
		}
	}

	/* 檢查 PDO SQLite3 可用性 */
	private function checkPIOPDOSQLite3(){
		return (class_exists('PDO') && extension_loaded('pdo_sqlite'));
	}
	
	public function ModulePage(){
		if ($this->checkPIOPDOSQLite3()){
			$this->_ModulePagePDO(); //使用PDO
		}else{
			$this->_ModulePageOld(); // 使用舊板SQLITE
		}
	}
}
