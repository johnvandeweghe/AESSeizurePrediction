<?php
ini_set('memory_limit', -1);
include('models/AESSeizurePrediction.class.php');
include('models/Classifier.interface.php');
include('models/SVMClassifier.class.php');
include('models/ANNClassifier.class.php');

$classifiers = [
	new SVMClassifier(),
	//new ANNClassifier(),
];

$classifier_weights = [
	1,
	1,
];

if(!isset($argv[1]) || !isset($argv[2])){
	echo "Usage: php index.php file_prefixes inter_totals pre_totals training_ratio [load_only=0] [save_prefix='']\n";
	exit(1);
}
$prefixes = explode(',', $argv[1]);
$inter_totals = explode(',', $argv[2]);
$pre_totals = explode(',', $argv[3]);
$training_ratio = $argv[4];
$use_saved = !isset($argv[5]) ?: $argv[5] == 1;
$save_prefix = !isset($argv[6]) ? '' : $argv[6];

$p = new AESSeizurePrediction($classifiers, $classifier_weights, $save_prefix);
echo date('c') . " Starting up...\n";


if(!$use_saved){
	foreach($prefixes as $k => $prefix){
		for($i = 1; $i < round($inter_totals[$k]*$training_ratio); $i++){
			$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
			$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'interictal_segment_0'. $padded .'.avg'), true);

			$p->add($data, false);
		}

		echo date('c') . " Done learning " . round($inter_totals[$k]*$training_ratio) . " $prefix inter files\n";

		for($i = 1; $i < round($pre_totals[$k]*$training_ratio); $i++){
			$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
			$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'preictal_segment_0'. $padded .'.avg'), true);

			$p->add($data, true);
		}

		echo date('c') . " Done learning " . round($pre_totals[$k]*$training_ratio) . " $prefix pre files\n";
	}
}

echo date('c') . " Generating models...\n";
$p->process($use_saved);

if(!$use_saved){
	echo date('c') . " Done generating models\n";
}

$inter_right = 0;
$inter_total = 0;
$pre_right = 0;
$pre_total = 0;

foreach($prefixes as $k => $prefix){
	for($i = round($inter_totals[$k]*$training_ratio); $i < $inter_totals[$k]+1; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'interictal_segment_0'. $padded .'.avg'), true);

		$inter_total++;
		$result = $p->predict($data) == -1;
		if($result){
			$inter_right++;
		}
	}
	echo date('c') . " Done testing $prefix inter\n";

	for($i = round($pre_totals[$k]*$training_ratio); $i <  $pre_totals[$k]+1; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'preictal_segment_0'. $padded .'.avg'), true);

		$pre_total++;
		$result = $p->predict($data) == 1;
		if($result){
			$pre_right++;
		}
	}
	echo date('c') . " Done testing $prefix pre\n";
}

echo date('c') . " RESULT:\n".
"Inter avg: " . round($inter_right/$inter_total, 3) . ', Pre avg: ' . round($pre_right/$pre_total, 3) . "\n".
"Inter correct: $inter_right, Inter total: $inter_total, Pre correct: $pre_right, Pre total: $pre_total\n".
"Overall Score: " . round(($inter_right/$inter_total + $pre_right/$pre_total)/2, 3) . "\n";