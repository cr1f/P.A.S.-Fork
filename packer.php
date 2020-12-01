<?php
(!isset($argv[1]) ? exit("\nphp ".basename(__FILE__)." file.php [ PHP8 ]\n\n") : @list($x, $fp, $e) = $argv);

$n = chr(rand(97, 122));

foreach(array("array_map", "strrev", "gzuncompress", "base64_decode", "create_function") as $f) @$ps .= (int)$i++."=%".implode("%", str_split(bin2hex($f), 2))."&";
 
(file_exists($fp) ? file_put_contents($fp."_packed.php","<?php ".r()."parse_str".r()."(".r().implode(".", array_map(function($k){return r()."'".$k."'".r();}, str_split($ps, rand(1,4)))).r().",".r()."\$$n".r().")".r().";".r().($e ? "@".r()."eval".r()."(" : "@".r()."\$$n".r()."[".r()."0".r()."]".r()."(".r()."\$$n".r()."[".r()."4".r()."]".r().",".r()."array".r()."(".r().")".r().",".r()."array".r()."(".r()."'};'".r().".").r()."\$$n".r()."[".r()."2".r()."]".r()."(".r()."\$$n".r()."[".r()."3".r()."]".r()."(".r()."\$$n".r()."[".r()."1".r()."]".r()."(".r()."'".strrev(base64_encode(gzcompress("?>".file_get_contents($fp))))."'".r().")".r().($e ? "" : ")").r().")".r().".".r()."'//'".r().")".r().")".r().";".r()) : exit("\n".$fp." not exists\n\n"));


function r(){
	$l = rand(10, 50);
	while(!isset($c[$l])) @$c .= chr(rand(32, 126));
	
	if(rand(0, 1))
		return (rand(0, 1) ? "#".chr(rand(32, 90)) : "//").str_replace("?>", "", $c).(rand(0, 1) ? "\r" : "\n");
	else
		return (rand(0, 1) ? "/*".str_replace("*/","", $c)."*/" : (rand(0, 1) ? "\t".r() : " ".r()));
}
