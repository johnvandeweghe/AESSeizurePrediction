<?php
ini_set('memory_limit', -1);
include('models/AESSeizurePrediction.class.php');
include('models/Classifier.interface.php');
include('models/SVMClassifier.class.php');
include('models/ANNClassifier.class.php');

$classifiers = [
	new SVMClassifier(),
	new ANNClassifier(),
];

if(!isset($argv[1]) || !isset($argv[2])){
	echo "Usage: php index.php file_prefix [load_only=0] [save_prefix='']\n";
	exit(1);
}
$prefix = $argv[1];
$use_saved = !isset($argv[2]) ?: $argv[2] == 1;
$save_prefix = !isset($argv[3]) ? '' : $argv[3];

$p = new AESSeizurePrediction($classifiers, $save_prefix);
echo date('c') . " Starting up...\n";


if(!$use_saved){
	for($i = 1; $i < 336; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'interictal_segment_0'. $padded .'.avg'), true);

		$p->add($data, false);
	}

	echo date('c') . " Done learning inter\n";

	for($i = 1; $i < 21; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'interictal_segment_0'. $padded .'.avg'), true);

		$p->add($data, true);
	}

	echo date('c') . " Done learning pre\n";
}

echo date('c') . " Generating models...\n";
$p->process($use_saved);

if(!$use_saved){
	echo date('c') . " Done generating models\n";
}

$inter_right = 0;
$inter_total = 0;

//for($i = 338; $i < 451; $i++){
for($i = 336; $i < 451; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'interictal_segment_0'. $padded .'.avg'), true);

	$inter_total++;
	$result = $p->predict($data) == -1;
	if($result){
		$inter_right++;
	}
}
echo date('c') . " Done testing inter\n";

$pre_right = 0;
$pre_total = 0;

for($i = 21; $i < 31; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$data = json_decode(file_get_contents('averaged_data/' . $prefix . 'preictal_segment_0'. $padded .'.avg'), true);

	$pre_total++;
	$result = $p->predict($data) == 1;
	if($result){
		$pre_right++;
	}
}
echo date('c') . " Done testing pre\n";

echo date('c') . " RESULT:\n".
"Inter avg: " . round($inter_right/$inter_total, 3) . ', Pre avg: ' . round($pre_right/$pre_total, 3) . "\n".
"Inter correct: $inter_right, Inter total: $inter_total, Pre correct: $pre_right, Pre total: $pre_total\n".
"Overall Score: " . round(($inter_right+$pre_right)/($inter_total+$pre_total), 3) . "\n";