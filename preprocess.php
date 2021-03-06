<?php
ini_set('memory_limit', -1);
include('libraries/Matlab.class.php');
include('libraries/MatlabArray.class.php');
include('models/AESSeizurePrediction.class.php');
include('models/SeizureData.class.php');

if(!isset($argv[1]) || !isset($argv[2])){
	echo "Usage: php index.php mat_path file_prefix total_inter total_pre total_testing [save_prefix=file_prefix]\n";
	exit(1);
}
$path = $argv[1];
$prefix = $argv[2];
$total_inter = $argv[3];
$total_pre = $argv[4];
$total_testing = $argv[5];
$save_prefix =  isset($argv[6]) ? $argv[6] : $prefix;

for($i = 1; $i <= $total_inter; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$ml = new Matlab($path . $prefix . 'interictal_segment_0'. $padded .'.mat');

	$average = getData($ml);

	file_put_contents('averaged_data/' . $save_prefix . 'interictal_segment_0'. $padded .'.json', json_encode($average));

	echo date('c') . ' Finished ' . $prefix . 'interictal_segment_0'. $padded .".mat\n";
}

for($i = 1; $i <= $total_pre; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$ml = new Matlab($path . $prefix . 'preictal_segment_0'. $padded .'.mat');

	$average = getData($ml);

	file_put_contents('averaged_data/' . $save_prefix . 'preictal_segment_0'. $padded .'.json', json_encode($average));

	echo date('c') . ' Finished ' . $prefix . 'preictal_segment_0'. $padded .".mat\n";
}

for($i = 1; $i <= $total_testing; $i++){
	$padded = str_pad($i . '', 3, '0', STR_PAD_LEFT);
	$ml = new Matlab($path . $prefix . 'test_segment_0'. $padded .'.mat');

	$average = getData($ml);

	file_put_contents('averaged_data/' . $save_prefix . 'test_segment_0'. $padded .'.json', json_encode($average));

	echo date('c') . ' Finished ' . $prefix . 'test_segment_0'. $padded .".mat\n";
}


function getData($ml){
	$array = $ml->nextElement();
	
	var_dump($array->getFieldsList());

	$sampling_frequency = round($array->getFieldData('sampling_frequency'), 2);
	$data_length_sec = $array->getFieldData('data_length_sec')[1];
	$channels = $array->getFieldData('channels');
	$data = $array->getFieldData('data');
	$sequence = $array->getFieldData('sequence')["1"];
	$ml->close();
	unset($array);

	$data = array_chunk($data, $sampling_frequency * $data_length_sec);

	foreach($data as $k => $datum){
		$data[$channels[$k][1]] = $datum;
		unset($data[$k]);
	}

	return new SeizureData(AESSeizurePrediction::cleanAverages($data), $sequence);
}