<?php
list($p, $a, $c, $k, $e, $r) = x();

switch($k){
	case 'ZIP':
	case 'PHAR':
		$e = s().j().j().($k == 'ZIP' ? "ob_".r('end_','')."clean".j()."(".j().")".j().r('.',';') : '').j().r('exit', 'die', '').j()."(".j().r('include','require').r('_once','').j()."~".j().(d() ? r('raw', '').'urldecode' : 'hex2bin').j()."(".j().(d() ? '\'%\'.' : '').j().u('php://filter/string.rot13|convert.base64-decode|zlib.inflate/resource=phar://').j().")".j().".".j()."__FILE__".j().".".j()."'/'".j().".".j()."~".j()."'".~(addslashes(f(__DIR__)))."'".j().")".j().";".j();
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
		foreach(array("array_map", "strrev", "gzuncompress", "base64_decode", "create_function") as $fn) @$ps .= (int)$i++."=%".implode("%", str_split(bin2hex($fn), 2))."&";
		file_put_contents($c.$p, s().j()."parse_str".j()."(".j().implode(".", array_map(function($k){return j()."'".$k."'".j();}, str_split($ps, rand(1,4)))).j().",".j()."\$$r".j().")".j().";".j().($e ? "@".j()."eval".j()."(" : "@".j()."\$$r".j()."[".j()."0".j()."]".j()."(".j()."\$$r".j()."[".j()."4".j()."]".j().",".j()."array".j()."(".j().")".j().",".j()."array".j()."(".j()."'};'".j().".").j()."\$$r".j()."[".j()."2".j()."]".j()."(".j()."\$$r".j()."[".j()."3".j()."]".j()."(".j()."\$$r".j()."[".j()."1".j()."]".j()."(".j()."'".strrev(base64_encode(gzcompress("?>".s()."?>".file_get_contents($c))))."'".j().")".j().($e ? "" : ")").j().")".j().".".j()."'//'".j().")".j().")".j().";".j());
}

m($k.' Ok');

function x(){
	$r = chr(rand(97, 122));
	$a = (class_exists('ZipArchive') ? new ZipArchive : false);
	(!isset($_SERVER['argv'][1]) ? m($a ? 'php '.basename(__FILE__).' file_to_pack.php [ASCII|PHAR|ZIP] [PHP8]' : 'php-zip extension required!') : @list($x, $c, $k, $e) = $_SERVER['argv']);
	$p = '_packed.php'.($k == 'PHAR' ? '.phar' : '');
	(file_exists($c) ? @unlink($c.$p) : m('`'.$c.'` not exists'));
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
	return '<?'.r('='.r(j(),'').r('\'\'','""','false', 'null', '@'.j().'$'.substr(md5(microtime()), 0, rand(1, 32))).r('',j()).';'.r('',j()),'php'.w());
}

function d(){
	return date('s') > 30;
}

function w(){
	return implode('', array_rand(array_flip(["\n","\r","\t",' ', '', chr(9)]), rand(2, 5))).r(j(),'');
}

function j(){
	$l = rand(10, 50);
	while(!isset($c[$l])) @$c .= chr(rand(32, 126));
	
	if(rand(0, 1))
		return (rand(0, 1) ? "#".chr(rand(32, 90)) : "//").str_replace("?>", "", $c).(rand(0, 1) ? "\r" : "\n");
	else
		return (rand(0, 1) ? "/*".str_replace("*/","", $c)."*/" : (rand(0, 1) ? "\t".j() : " ".j()));
}

function u($s){
	return implode('.', array_map(
		function($k){
				return j().'\''.$k.'\''.j();
		}, str_split(implode((d() ? '%' : ''), str_split(bin2hex(~$s), 2)), rand(1,4)))
	);
}
