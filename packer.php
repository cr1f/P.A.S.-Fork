<?php
list($p, $a, $c, $k, $e, $r) = x();

switch($k){
	case 'ZIP':
	case 'PHAR':
		$e = t(s().j().($k == 'ZIP' ? 'ob_'.r('end_','').'clean()'.r('.',';') : '').r('exit', 'die', '').'('.r('include','require').r('_once','').'~'.(d() ? r('raw', '').'urldecode' : 'hex2bin').'('.(d() ? '\'%\'.' : '').u('php://filter/string.rot13|convert.base64-decode|zlib.inflate/resource=phar://').').__FILE__."/".~"'.~(addslashes(f(__DIR__))).'");');
		$r = str_rot13(chunk_split(base64_encode(gzdeflate(s().'?>'.file_get_contents($c), 9)), rand(1, 100) * 4));

		switch($k){
			case 'ZIP': # The output isn't always valid for PHP5/7 (some php-zip bug), try to pack several times
				$a->open($c.$p, ZipArchive::CREATE);
				$a->addFromString(f(), j().$e.'?>');
				$a->addFromString(f(__DIR__), $r);
				$a->setCompressionIndex(0, ZipArchive::CM_STORE);
				$a->close();
			break;

			default:
				$a = new Phar($c.$p);
				$a->setStub($e.'__HALT_COMPILER();');
				$a[f(__DIR__)] = $r;
				rename($c.$p, substr($c.$p, 0, (strlen($c.$p) - 5)));
		}
	break;

	default:
		foreach(array('array_map', 'strrev', 'gzuncompress', 'base64_decode', 'create_function') as $fn) @$ps .= (int)$i++.'=%'.implode('%', str_split(bin2hex($fn), 2)).'&';
		file_put_contents($c.$p, t(s().'parse_str('.implode('.', array_map(function($k){return '"'.$k.'"';}, str_split($ps, rand(1,4)))).',$'.$r.');@'.($e ? 'eval(' : '$'.$r.'[0]($'.$r.'[4],array(),array'.'("};".').'$'.$r.'[2]($'.$r.'[3]($'.$r.'[1]("'.strrev(base64_encode(gzcompress('?>'.s().'?>'.file_get_contents($c)))).'")'.($e ? '' : ')').')."//"));'));
}

m($k.' Ok');

function x(){
	$r = chr(rand(97, 122));
	@list($x, $c, $k, $e) = $_SERVER['argv'];
	$p = '_packed.php'.($k == 'PHAR' ? '.phar' : '');
	$a = (class_exists('ZipArchive') ? new ZipArchive : false);
	
	($k !== 'ASCII' && ini_get('phar.readonly') ? m('PHAR creation is disabled in "'.php_ini_loaded_file().'", need "phar.readonly = Off"') :
	(empty($c) ? m($a ? 'php '.basename(__FILE__).' file_to_pack.php [ASCII|PHAR|ZIP] [PHP8]' : 'php-zip extension required!') :
	(file_exists($c) ? @unlink($c.$p) : m('`'.$c.'` not exists'))));
	
	return [$p, $a, $c, $k, $e, $r];
}

function f($s = 0){
	$s = strval(filemtime($s ? $s : sys_get_temp_dir()));
	$p = ($s[9] + 1);
	$s = strtolower(strrev(str_replace(['/', '+', '='], '', base64_encode(md5($s)))));
	return substr(substr($s, $p).'.'.substr($s, 0, $p), $p);
}

function r(){
	$r = func_get_args();
	return $r[rand(0, (func_num_args() - 1))];
}

function m($s){
	exit(PHP_EOL.'> '.$s.PHP_EOL.PHP_EOL);
}

function s(){
	return '<?'.r('='.r('\'\'','""','false', 'null', '@$'.str_repeat(chr(rand(65, 90)), rand(1, 3))).';'.r('',j()),'php'.w());
}

function d(){
	return date('s') > 30;
}

function w(){
	return implode('', array_rand(array_flip(["\n","\r","\t"," ", "", chr(9)]), rand(2, 5))).r(j(),'');
}

function j(){
	$l = rand(10, 50);
	while(!isset($c[$l])) @$c .= chr(rand(32, 126));
	
	if(rand(0, 1))
		return (rand(0, 1) ? "#".chr(rand(32, 90)) : "//").str_replace("?>", "", $c).(rand(0, 1) ? "\r" : "\n");
	else
		return (rand(0, 1) ? "/*".str_replace("*/","", $c)."*/" : (rand(0, 1) ? "\t".j() : " ".j()));
}

function t($s){
	foreach(token_get_all($s) as $t)
		@$r .= (is_array($t) ? $t[1] : $t).j();
	return $r;
}

function u($s){
	return implode('.', array_map(
		function($k){
				return '\''.$k.'\'';
		}, str_split(implode((d() ? '%' : ''), str_split(bin2hex(~$s), 2)), rand(1,4)))
	);
}
