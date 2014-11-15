<?php
ini_set('memory_limit', -1);
include('Matlab.class.php');
include('MatlabArray.class.php');
include('AESSeizurePrediction.class.php');

if(!isset($argv[1]) || !isset($argv[2])){
	echo "Usage: php index.php mat_path file_prefix use_saved_model\n";
	exit(1);
}
$path = $argv[1];
$prefix = $argv[2];
$use_saved = $argv[3] == 1;

if($use_saved && !file_exists('model.sav')){
	echo "Save file not found!\n";
	exit(1);
}

$p = new AESSeizurePrediction();
echo date('c') . " Starting up...\n";


if(!$use_saved){
	for($i = 1; $i < 51; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$ml = new Matlab($path . $prefix . 'interictal_segment_0'. $padded .'.mat');

		learn($ml, false);

		echo date('c') . ' Finished ' . $prefix . 'interictal_segment_0'. $padded .".mat\n";
	}

	echo date('c') . " Done learning inter\n";

	for($i = 1; $i < 23; $i++){
		$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
		$ml = new Matlab($path . $prefix . 'preictal_segment_0'. $padded .'.mat');

		learn($ml, true);
		echo date('c') . ' Finished ' . $prefix . 'preictal_segment_0'. $padded .".mat\n";
	}

	echo date('c') . " Done learning pre\n";
	if(file_exists('model.sav')){
		unlink('model.sav');
	}
	if(file_exists('analysis.ex')){
		unlink('analysis.ex');
	}
}

$p->process($use_saved);

if(!$use_saved){
	echo date('c') . " Done creating model\n";
}

$inter_right = 0;
$inter_total = 0;

//for($i = 338; $i < 451; $i++){
for($i = 400; $i < 451; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$ml = new Matlab($path . $prefix . 'interictal_segment_0'. $padded .'.mat');

	$inter_total++;
	$result = learn($ml, -1);
	if(!$result){
		$inter_right++;
	}
	echo date('c') . ' Finished ' . $prefix . 'interictal_segment_0'. $padded .".mat\n";
}

$pre_right = 0;
$pre_total = 0;

for($i = 23; $i < 31; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$ml = new Matlab($path . $prefix . 'preictal_segment_0'. $padded .'.mat');

	$pre_total++;
	$result = learn($ml, -1);
	if($result){
		$pre_right++;
	}
	echo date('c') . ' Finished ' . $prefix . 'preictal_segment_0'. $padded .".mat\n";
}

echo date('c') . " RESULT:\n".
"Inter avg: " . round($inter_right/$inter_total, 3) . ', Pre avg: ' . round($pre_right/$pre_total, 3) . "\n".
"Inter correct: $inter_right, Inter total: $inter_total, Pre correct: $pre_right, Pre total: $pre_total\n".
"Overall Score: " . round(($inter_right+$pre_right)/($inter_total+$pre_total), 3) . "\n";


function learn($ml, $is_seizure){
	global $p;

	$array = $ml->nextElement();

	$sampling_frequency = round($array->getFieldData('sampling_frequency'), 2);
	$data_length_sec = $array->getFieldData('data_length_sec')[1];
	$channels = $array->getFieldData('channels');
	$data = $array->getFieldData('data');
	$ml->close();
	unset($array);

	$data = array_chunk($data, $sampling_frequency * $data_length_sec);

	foreach($data as $k => $datum){
		$data[$channels[$k][1]] = $datum;
		unset($data[$k]);
	}

	//Data is ready for processing

	if($is_seizure === -1){
		return $p->predict($data) == 1;
	} else {
		$p->add($data, $is_seizure);
	}
}