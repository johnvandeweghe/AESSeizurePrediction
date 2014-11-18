<?php
//ann
class ANNClassifier implements Classifier {

	public function trainBulk($data);
	public function train($input, $output);
	
	public function predict($input);
	
	public function load($filename);
	public function save($filename);
}