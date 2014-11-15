<?php
class AESSeizurePrediction {
	private $exampleFile = null;
	private $exampleFilePath = null;
	private $model = null;

	public function __construct($exampleFile){
		$this->exampleFilePath = $exampleFile;
		$this->exampleFile = fopen($exampleFile, 'a');
	}

	public function add($data, $is_seizure){
		$scaled_avg = self::scaled_avg($data);

		$row = ($is_seizure ? '1' : '-1') . ' 1:' . $scaled_avg . "\n";

		fwrite($this->exampleFile, $row);
	}

	public function process($use_save = false){
		$svm = new SVM();
		$this->model = $use_save && file_exists('model.sav') ? new SVMModel('model.sav') : $svm->train(fopen($this->exampleFilePath, 'r'));
		$this->model->save('model.sav');
	}

	public function predict($data){
		$scaled_avg = self::scaled_avg($data);

		$row = array(1 => $scaled_avg);

		return $this->model->predict($row);
	}

	public static function scaled_avg($data){
		$sum = 0;
		$count = 0;
		$min = false;
		$max = false;
		foreach($data as $channel_data){
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

			$sum += array_sum($channel_data);
			$count += count($channel_data);
			$ch_min = min($channel_data);
			$ch_max = max($channel_data);
			$min = $min === false || $ch_min < $min ? $ch_min : $min;
			$max = $max === false || $ch_max > $max ? $ch_max : $max;
		}

		return (($sum / $count) - $min) / ($max - $min);
	}
}