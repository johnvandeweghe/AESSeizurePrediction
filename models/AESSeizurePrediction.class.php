<?php
class AESSeizurePrediction {
	private $file_prefix = '';
	
	private $data = [];
	
	private $classifiers = [];
	private $weights = [];

	public function __construct($classifiers, $weights, $file_prefix=''){
		$this->classifiers = $classifiers;
		$this->weights = $weights;
		$this->file_prefix = $file_prefix;
	}

	public function add($channel_averages, $is_seizure){
		foreach($channel_averages as $average){
			$this->data[] = [($is_seizure ? 1 : -1), 1 => $average];
		}
	}

	public function process($use_save = false){
		foreach($this->classifiers as $classifier){
			if($use_save){
				$classifier->load($this->file_prefix . $classifier->name . '.sav');
			} else {
				$classifier->trainBulk($this->data);
				$classifier->save($this->file_prefix . $classifier->name . '.sav');
			}
		}
	}

	public function predict($averages){
		$result = 0;
		
		foreach($averages as $average){
			foreach($this->classifiers as $i => $classifier){
				$result += $this->weights[$i] * $classifier->predict($average);
			}
		}

		echo "Data prediction: $result\n";
		return $result > 0 ? 1 : -1;
	}

	public static function cleanAverages($data){
		$averages = [];
		foreach($data as $channel_data){
			// Lets clean this data up a bit before we do anything with it
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
				if(abs($datum-$avg) > $sigma*3)
					unset($channel_data[$k]);
			}
			$channel_data = array_values($channel_data);

			//Ok, now lets do some calculations with the cleaned data
			$averages[] = array_sum($channel_data)/count($channel_data);
		}

		return $averages;
	}
}