<?php
//ann
class ANNClassifier implements Classifier {

	private $ann = false;
	private $settings = [
		'num_input' => 1,
		'num_output' => 1,
		'num_layers' => 3,
		'num_neurons_hidden' => 10,
		'desired_error' => 0.001,
		'max_epochs' => 500000,
		'epochs_between_reports' => 1000,
	];

	public $name = 'ANN';

	public function trainBulk($data){
		if(!$this->ann){
			$this->ann = fann_create_standard($this->settings['num_layers'], $this->settings['num_input'], $this->settings['num_neurons_hidden'], $this->settings['num_output']);
			if(!$this->ann){
				throw new Exception('Failled to initialize fann');
			}
			fann_set_activation_function_hidden($this->ann, FANN_SIGMOID_SYMMETRIC);
    		fann_set_activation_function_output($this->ann, FANN_SIGMOID_SYMMETRIC);
		}

		$contents = count($data) . " 1 1\n";
		foreach($data as $datum){
			$contents .= $datum[1] . "\n" . $datum[0] . "\n";
		}
		file_put_contents('temp.tmp', $contents);
		fann_train_on_file($this->ann, 'temp.tmp', $this->settings['max_epochs'], $this->settings['epochs_between_reports'], $this->settings['desired_error']);
		unlink('temp.tmp');
		echo "MSE: " . fann_get_MSE($this->ann) . "\n";
	}
	public function train($input, $output){
		//NOPE
	}
	
	public function predict($input){
		return fann_run($this->ann, [$input])[0];
	}
	
	public function load($filename){
		if(!file_exists($filename)){
			throw new Exception('File not found');
		}

		$this->ann = fann_create_from_file($filename);
	}
	public function save($filename){
		fann_save($this->ann, $filename);
	}
}