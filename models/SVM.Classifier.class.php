<?php
//SVM with data scaling, weight calculation
class SVMClassifier implements Classifier {
	private $data = [];
	
	private $min = false;
	private $max = false;
	
	private $total_neg = 0;
	private $total_pos = 0;
	
	private $model = null;
	
	public $name = 'SVM';

	public function trainBulk($data){
		//Calculate the scale
		foreach($this->data as $datum){
			if($this->max === false || $this->datum[1] > $this->max){
				$this->max = $this->datum[1];
			}
			if($this->min === false || $this->datum[1] < $this->min){
				$this->min = $this->datum[1];
			}
			if($datum[0] > 0){
				$this->total_neg++;
			} else {
				$this->total_pos++;
			}
		}
		
		//Scale the data
		foreach($this->data as &$datum){
			$datum[1] = ($datum[1] - $this->min) / ($this->max - $this->min);
		}
		
		//Calculate weight
		$neg_weight = 1 - ($this->total_neg / ($this->total_neg+$this->total_pos));
		$pos_weight = 1 - ($this->total_pos / ($this->total_neg+$this->total_pos));
		
		$svm = new SVM();
		$this->model = $svm->train($this->data, array(-1 => $neg_weight, 1 => $pos_weight));	
	}
	
	public function train($input, $output){
		throw new Exception('SVM only supports bulk training');
	}
	
	public function predict($input){
		$row = [1 => ($input - $this->min) / ($this->max - $this->min)];
		return $this->model->predict($row);
	}
	
	public function load($filename){
		if(!file_exists($filename) || !file_exists($filename . '.limits')){
			throw new Exception('File not found (ensure .limits file exists)');
		}
		
		$this->model = new SVMModel($this->model_file);
		
		$minmax = explode(',', file_get_contents($filename . '.limits'));
		$this->min = $minmax[0];
		$this->max = $minmax[1];
	}
	
	public function save($filename){
		$this->model->save($filename);
		file_put_contents($filename . '.limits', $this->min . ',' . $this->max);
	}
}