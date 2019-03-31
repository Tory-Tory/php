<?php
	foreach ($argv as $item) {
		if(filter_var($item, FILTER_VALIDATE_INT) || $item == '+' || $item == '-') {
			$int[] = $item;
		}
	}

	for($i = 0; $i < count($int); $i++){
		if(filter_var($int[$i], FILTER_VALIDATE_INT)){
			$res = intval($int[$i]);
			break;
		}
	}

	for($k = $i; $k < count($int); $k++){
		if($int[$k] == '+' && filter_var($int[$k+1], FILTER_VALIDATE_INT)){
			$res = $res + intval($int[$k+1]);
		}
		if($int[$k] == '-' && filter_var($int[$k+1], FILTER_VALIDATE_INT)){
			$res = $res - intval($int[$k+1]);
		}
	}

	echo 'Решение: '.implode(' ',$int).' = '.$res."\n";
?>