<?php
$files = scandir ('averaged_data/');

$data = [];

foreach($files as $file){
	if($file == '.' || $file == '..')
		continue;
	$parts = explode('_', $file);
	$data[$parts[2]][] = json_decode(file_get_contents('averaged_data/' . $file), true);
}

file_put_contents('all_avereages.json', json_encode($data));