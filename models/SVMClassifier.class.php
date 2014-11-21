<?php
//SVM with weight calculation
class SVMClassifier implements Classifier {
	private $data = [];
	
	private $total_neg = 0;
	private $total_pos = 0;
	
	private $model = null;
	
	public $name = 'SVM';

	public function trainBulk($data){
		//Calculate the scale
		foreach($data as $datum){
			if($datum[0] < 0){
				$this->total_neg++;
			} else {
				$this->total_pos++;
			}
		}
		
		//Calculate weight
		$neg_weight = 1 - ($this->total_neg / ($this->total_neg+$this->total_pos));
		$pos_weight = 1 - ($this->total_pos / ($this->total_neg+$this->total_pos));
		
		$svm = new SVM();
		$this->model = $svm->train($data, array(-1 => $neg_weight, 1 => $pos_weight));	
	}
	
	public function train($input, $output){
		throw new Exception('SVM only supports bulk training');
	}
	
	public function predict($input){
		$input = array_slice($input, 1, null, true);
		return $this->model->predict($input);
	}
	
	public function load($filename){
		if(!file_exists($filename)){
			throw new Exception('File not found');
		}
		
		$this->model = new SVMModel($filename);
	}
	
	public function save($filename){
		$this->model->save($filename);
	}
}