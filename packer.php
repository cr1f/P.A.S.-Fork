<?php
list($p, $a, $c, $k, $e, $r) = x();

switch($k){
	case 'ZIP':
	case 'PHAR':
		$x = t(c(s().j().($k == 'ZIP' ? 'ob_'.r('end_','').'clean()'.r('.',';') : '').r('exit', 'die', '').'('.r('include','require').r('_once','').'~'.d(r('raw', '').'urldecode','hex2bin').'('.(d('\'%\'.')).u('php://filter/string.rot13|convert.base64-decode|zlib.inflate/resource=phar://').').'.'__FILE__'.'."/".~"').~f().'");');

		switch($k){
			case 'ZIP':  # The DEFLATE output isn't always valid for PHP5/7 (some php-zip bug), try to pack several times
					$a->open($c.$p, ZipArchive::CREATE);
					$a->addFromString(f(0), j().$x.'?>');
					$a->addFromString(f(), $r);
					$a->setCompressionIndex(0, ZipArchive::CM_STORE);
					$a->setCompressionIndex(1, ($e ? ZipArchive::CM_DEFLATE : ZipArchive::CM_STORE));
					$a->close();
			break;

			default:
				$a = new Phar($c.$p);
				$a->setStub($x.'__HALT_COMPILER();');
				$a[f()] = $r;
				if($e) $a->compressFiles(Phar::GZ);
				rename($c.$p, substr($c.$p, 0, (strlen($c.$p) - 5)));
		}
	break;

	default:
		foreach(c(['array_map', 'strrev', 'gzinflate', 'base64_decode', 'create_function', 'str_rot13']) as $fn) @$ps .= (int)$i++.'=%'.implode('%', str_split(bin2hex($fn), 2)).'&';
		file_put_contents($c.$p, t(c(s().'parse_str('.implode('.', array_map(function($k){$r = rand(0,1); return ($r ? '"' : '\'').$k.($r ? '"' : '\'');}, str_split($ps, rand(1,4))))).',$'.$a.');@'.(!$e ? c('eval(') : '$'.$a.'[0]($'.$a.c('[4],array(),array').'("};".').'$'.$a.'[2]($'.$a.'[3]($'.$a.'[5]($'.$a.'[1]("'.strrev(d(chunk_split($r, rand(200, 250), '"."'), $r)).'"))'.(!$e ? '' : ')').')'.(!$e ? '' : '."//"').'));'));
}

m($k.($e ? ' '.$e : '').' Ok');


function x(){
	@list($x, $c, $k, $e) = $_SERVER['argv'];
	$p = '_packed.php'.($k == 'PHAR' ? '.phar' : '');
	$a = ($k == 'ZIP' && class_exists('ZipArchive') ? new ZipArchive : f(0,0));

	(!$c ? m('php '.basename(__FILE__).' file_to_pack.php [ASCII|PHAR|ZIP] [CM|CF]') : (file_exists($c) ? @unlink($c.$p) : m('`'.$c.'` file not exists')));
	($k != 'ASCII' && ini_get('phar.readonly') ? m('PHAR creation is disabled in "'.php_ini_loaded_file().'", need "phar.readonly = Off"') :
	(!is_object($a) && $k == 'ZIP' ? m('php-zip extension required!') : ''));
	$r = str_rot13(chunk_split(base64_encode(gzdeflate(($k == 'ASCII' ? j().'?>' : '').d(s(), t(s())).'?>'.e($c), rand(5, 9))), rand(100, 250) * 4));
	
	return [$p, $a, $c, $k, $e, $r];
}

function f($s = __DIR__, $e = 1){
	$s = strval(filemtime($s ? $s : sys_get_temp_dir()));
	$p = ($s[9] + 1);
	$s = strtolower(strrev(str_replace(['/', '+', '='], '', base64_encode(md5($s)))));
	return substr(substr($s, $p).($e ? '.'.substr($s, 0, $p) : ''), $p);
}

function r(){
	$r = func_get_args();
	return c($r[rand(0, (func_num_args() - 1))]);
}

function m($s){
	exit(PHP_EOL.'> '.$s.PHP_EOL.PHP_EOL);
}

function s(){
	return '<?'.r('='.d('(').r('\'\'', '""', 'false', 'null', '@$'.f(0, 0)).d(')').';','php'.w());
}

function d($x, $z = ''){
	$d = strval(getmypid() - date('s'));
	return $d[strlen($d) - 1] >= 4 ? $x : $z;
}

function w(){
	return implode('', array_rand(array_flip(["\n","\r","\t"," ", "", chr(9)]), rand(2, 5))).r(j(), '');
}

function j(){
	$l = rand(10, 50);
	while(!isset($c[$l])) @$c .= chr(rand(32, 126));
	
	if(rand(0, 1))
		return preg_replace("|\?>|", "", ((rand(0, 1) ? "#".chr(rand(32, 90)) : "//").$c.(rand(0, 1) ? "\r" : "\n")));
	else
		return (rand(0, 1) ? "/*".preg_replace("|\*/|","", $c)."*/" : (rand(0, 1) ? "\t".j() : " ".j()));
}

function t($s){
	foreach(token_get_all($s) as $t)
		@$r .= (is_array($t) ? $t[1] : $t).j();
	return $r;
}

function u($s){
	return implode('.', array_map(
		function($k){
				$r = rand(0,1);
				return ($r ? '"' : '\'').$k.($r ? '"' : '\'');
		}, str_split(implode(d('%'), str_split(bin2hex(~$s), 2)), rand(1,4)))
	);
}

function e($c){
	return trim(file_get_contents($c), "?>\n\r\t ");
}

function c($o){
	if(is_array($o))
		foreach($o as $k => &$v) $v = c($v);
	
	if(is_string($o))
		$o = implode('', array_map(function($s){return rand(0, 1) ? strtolower($s) : strtoupper($s);}, str_split($o)));
	
	return $o;
}
