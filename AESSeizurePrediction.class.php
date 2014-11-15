<?php
class AESSeizurePrediction {
	private $model = null;
	
	private $data = [
	];
	
	private $min = false;
	
	private $max = false;

	public function __construct(){
	}

	public function add($data, $is_seizure){
		$analysis = self::analyze($data);
		
		if($this->min === false || $analysis['min'] < $this->min){
			$this->min = $analysis['min'];
		}
		
		if($this->max === false || $analysis['max'] > $this->max){
			$this->max = $analysis['max'];
		}

		foreach($analysis['averages'] as $average){
			$this->data[] = [($is_seizure ? 1 : -1), 1 => $average];
		}
	}

	public function process($use_save = false){
		if(!$use_save){
			foreach($this->data as &$datum){
				$datum[1] = ($datum[1] - $this->min) / ($this->max - $this->min);
			}
			file_put_contents('analysis.ex', $this->min . ',' . $this->max);
		} else {
			//Lets load in the scale so we can do proper predictions
			$minmax = explode(',', file_get_contents('analysis.ex'));
			$this->min = $minmax[0];
			$this->max = $minmax[1];
		}
		$svm = new SVM();
		$this->model = $use_save && file_exists('model.sav') ? new SVMModel('model.sav') : $svm->train($this->data);
		$this->model->save('model.sav');
	}

	public function predict($data){
		$result = 0;
	
		$analysis = self::analyze($data);
		
		foreach($analysis['averages'] as $average){
			$row = array(1 => ($average - $this->min) / ($this->max - $this->min));
			$result += $this->model->predict($row);
		}

		return $result > 0 ? 1 : -1;
	}

	public static function analyze($data){
		$averages = [];
		$min = false;
		$max = false;
		foreach($data as $channel_data){
			//Lets clean this data up a bit before we do anything with it
			foreach($channel_data as &$datum){
				$datum = abs($datum);
			}
			unset($datum);		
			
			$first_count = count($channel_data);
			$avg = array_sum($channel_data)/$first_count;
			$std_sum = 0;

			foreach($channel_data as $datum){
				$std_sum += pow($datum - $avg, 2);
			}

			$std_dev = $std_sum/$first_count;
			$sigma = pow($std_dev, .5);

			foreach($channel_data as $k=>$datum){
				if(abs($datum-$avg) > $sigma)
					unset($channel_data[$k]);
			}
			$channel_data = array_values($channel_data);

			//Ok, now lets do some calculations with the cleaned data
			$averages[] = array_sum($channel_data)/count($channel_data);
			
			$ch_min = min($channel_data);
			$ch_max = max($channel_data);
			$min = $min === false || $ch_min < $min ? $ch_min : $min;
			$max = $max === false || $ch_max > $max ? $ch_max : $max;
		}

		return array('averages' => $averages, 'min' => $min, 'max' => $max);
	}
}