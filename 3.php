<?php
	$str = 'Привет, мир! Я лучший текст из всех, что ты когда-либо видел!';
	$words = ['лучший', 'Привет', 'code', 'видел'];
	foreach ($words as $word) {
		$pos =mb_strpos($str, $word);
		$length = mb_strlen($word);
		if($pos !== false){
				$part_1 = mb_substr($str, $pos-10, 10);
				$part_2 = mb_substr($str, $pos+$length, 10);
			$res = $part_1.','.$part_2;
			echo $res."\n";
		}else{
			echo "Нет слова!\n";
		}
	}
?>