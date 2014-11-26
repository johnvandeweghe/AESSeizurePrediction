<?php
class MatlabArray extends Matlab {
	private $length;
	private $offset;
	
	public $array_flags = [];

	public $dimensions = [];
	public $name = '';

	public $sub_arrays = [];

	protected static $ARRAYTYPES = [
		1 =>['name' => 'Cell array', 'type' => 'cell'],
		2 => ['name' => 'Structure', 'type' => 'struct'],
		3 => ['name' => 'Object', 'type' => 'object'],
		4 => ['name' => 'Character array', 'type' => 'char'],
		5 => ['name' => 'Sparse array', 'type' => 'sparse'],
		6 => ['name' => 'Double precision array', 'type' => 'double'],
		7 => ['name' => 'Single precision array', 'type' => 'single'],
		8 => ['name' => '8 bit signed array', 'type' => '8'],
		9 => ['name' => '8 bit unsigned array', 'type' => '8u'],
		10 => ['name' => '16 bit signed array', 'type' => '16'],
		11 => ['name' => '16 bit unsigned array', 'type' => '16u'],
		12 => ['name' => '32 bit signed array', 'type' => '32'],
		13 => ['name' => '32 bit unsigned array', 'type' => '32u'],
		14 => ['name' => '64 bit signed array', 'type' => '64'],
		15 => ['name' => '64 bit unsigned array', 'type' => '64u'],
	];

	private $data = null;
	
	public function __construct($filename, $offset, $length, $flip_endian = false){
		$this->offset = $offset;
		$this->length = $length;
		$this->flip_endian = $flip_endian;
		
		if(!file_exists($filename)){
			throw new Exception('File not found');
		} else {
			$this->filename = $filename;
		}

		if(!($this->fhandle = fopen($this->filename, 'r'))){
			throw new Exception('Unable to open file');
		}
		
		if(fseek($this->fhandle, $offset) !== 0){
			throw new Exception('Unable to seek in file');
		}
		
		if(!($array_flags = $this->nextSubElement())){
			throw new Exception('Unable to find array flags');
		} else {
			$split = unpack('C*', pack('N*', $array_flags[1]));

			$this->array_flags['flags'] = $split[3];
			
			$this->array_flags['class'] = $split[4];
			if(isset(self::$ARRAYTYPES[$this->array_flags['class']])){
				$this->array_flags['class'] = self::$ARRAYTYPES[$this->array_flags['class']];
			} else {
				throw new Exception('Unknown array type: ' . $this->array_flags['class']);
			}
		}

		if(!($this->dimensions = $this->nextSubElement())){
			throw new Exception("Unable to find array dimensions");
		} else {
			$this->dimensions = $this->dimensions;
		}

		if(!($this->name = $this->nextSubElement())){
			$this->name = null;
		} else {
			$this->name = implode('', array_map(function($e){return pack('c*',$e);}, $this->name));
		}
		
		switch($this->array_flags['class']['type']){
			case 'struct':
				if(!($fieldLength = $this->nextSubElement())){
					throw new Exception("Unable to find array field length");
				}

				if(!($fields = $this->nextSubElement())){
					throw new Exception("Unable to find array fields");
				} else {
					$this->fields = array_chunk($fields, $fieldLength);

					foreach($this->fields as &$field){
						$field = implode('',
							array_map(
								function($e){
									return pack('C*',$e);
								},
								array_filter($field, function($f){
									return (bool)$f;
								})
							)
						);
					}
					unset($field);

					$this->fields = array_values($this->fields);
				}

				foreach($this->fields as $field){
					if(!($this->sub_arrays[$field] = $this->nextSubElement())){
						throw new Exception('Missing sub array in structure');
					}
				}
				break;
			case 'cell':
				while($sub = $this->nextSubElement()){
					$this->data[] = $sub->data ?: $sub;
				}
				break;
			case 'object':
			case 'sparse':
			default:
				throw new Exception('unsupported array type: ' . $this->array_flags['class']['type']);

			//Numeric
			case 'char':
				for($i = 1; $i <= $this->dimensions[1]; $i++){
					$this->data[$i] = implode('', array_map(function($e){ return pack('n*', $e);}, $this->nextSubElement()));
					// foreach($this->data[$i] as $data){
					// 	var_dump(pack('n*', $data));
					// }
				}
				break;
			case '8':
			case '8u':
			case '16':
			case '16u':
			case '32':
			case '32u':
			case '64':
			case '64u':
			case 'single':
			case 'double':
				break;
		}
	}
	
	//Returns false when there are no more elements
	public function nextSubElement($offset = 0, $hard_read_length = 0){
		if(ftell($this->fhandle) >= $this->offset + $this->length){
			return false;
		}
		
		return $this->nextElement($offset, $hard_read_length);
	}

	public function getFieldsList(){
		return array_keys($this->sub_arrays);
	}

	public function getField($fieldName){
		return $this->sub_arrays[$fieldName];
	}

	public function getFieldData($fieldName){
		if(isset($this->sub_arrays[$fieldName])){
			$sub_array_data = $this->sub_arrays[$fieldName]->data;
			if($sub_array_data){
				if(is_array($sub_array_data) && reset($sub_array_data) instanceof MatlabArray){
					return array_map(function($e){return $e->nextSubElement();}, $sub_array_data);
				} elseif($sub_array_data instanceof MatlabArray) {
					return $sub_array_data->nextSubElement();
				} else {
					return $sub_array_data;
				}
			} else {
				return $this->sub_arrays[$fieldName]->nextSubElement();
			}
		} else {
			return null;
		}
	}
}