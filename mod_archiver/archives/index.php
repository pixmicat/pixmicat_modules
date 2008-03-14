<?php
echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-tw">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Content-Language" content="zh-tw">
<title>Index : Pixmicat! Archiver</title>
<link rel="stylesheet" type="text/css" href="archivestyle.css" />
</head>
<body>

<div id="content">
<p>靜態庫存頁面列表：</p>
';

/* 取出 XML 檔案討論串資訊 */
$sub = $name = $tmp = '';
function startElement($p, $name, $attrs){
	global $tmp;
	$tmp = $name;
}

function endElement($p, $name){
	global $tmp;
	$tmp = '';
}

function characterData($p, $data){
	global $tmp, $sub, $name;
	if($sub == '' && $tmp == 'SUBJECT') $sub = $data;
	if($name == '' && $tmp=='NAME') $name = $data;
}

function getSubjectAndName($file){
	global $tmp, $sub, $name;
	$sub = $name = $tmp = '';

	$xml_parser = xml_parser_create();
	xml_set_element_handler($xml_parser, "startElement", "endElement");
	xml_set_character_data_handler($xml_parser, "characterData");
	$line = file($file); $countline = count($line);
	for($i = 0; $i < $countline; $i++){
		if(!xml_parse($xml_parser, $line[$i], ($i == $countline - 1))) return false;
		if($sub != '' && $name != '') break;
	}
	xml_parser_free($xml_parser);
	return array('sub' => $sub, 'name' => $name);
}

/* 取得靜態庫存頁面列表 */
$fileList = Array();
function GetArchives($sPath){
	global $fileList;
	// 打開目錄逐個搜尋XML檔案並加入陣列
	$handle = opendir($sPath);
	while($file = readdir($handle)){
        if($file != '..' && $file != '.' && is_file($sPath.'/'.$file)) // 為檔案
			if(strpos($file, '.xml')) $fileList[] = $file;
    }
	// 排序陣列
	closedir($handle);
	@sort($fileList);
    @reset($fileList);
}

GetArchives('.');
$t = array(); $infobar = '';

// 列出檔案連結
echo "<ul>\n";
if($fileList_count = count($fileList)){ // 有列表
	for($i = 0; $i < $fileList_count; $i++){
		$infobar = ($t = getSubjectAndName($fileList[$i])) ? $t['name'].' - '.$t['sub'] : '';
		echo '	<li><a href="'.$fileList[$i].'">'.$fileList[$i]."</a> $infobar</li>\n";
	}
}else{
	echo '<li>目前還沒有靜態庫存頁面可供瀏覽</li>';
}
echo '</ul>
</div>

<hr />
';

echo <<< __HTML_FOOTER__

<div id="footer">
<!-- Pixmicat! -->
<small>- <a href="http://pixmicat.openfoundry.org/" rel="_blank">Pixmicat!</a> -</small>
</div>

</body>
</html>
__HTML_FOOTER__;
?>