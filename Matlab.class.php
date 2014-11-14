<?php
class Matlab {
	protected $filename = '';
	protected $fhandle;

	public $header = array(
		'description' =>'',
		'version' => 5,
		'endian' => ''
	);

	protected $flip_endian = false;

	protected static $DATATYPES = [
		1 => ['name' => '8 bit, signed', 'type' => '8'],
		2 => ['name' => '8 bit, unsigned', 'type' => '8u'],
		3 => ['name' => '16 bit, signed', 'type' => '16'],
		4 => ['name' => '16 bit, unsigned', 'type' => '16u'],
		5 => ['name' => '32 bit, signed', 'type' => '32'],
		6 => ['name' => '32 bit, unsigned', 'type' => '32u'],
		7 => ['name' => 'IEEE 754 single format', 'type' => '64u'],
		8 => ['name' => 'Reserved', 'type' => ''],
		9 => ['name' => 'IEEE 754 double format', 'type' => '64u'],
		10 => ['name' => 'Reserved', 'type' => ''],
		11 => ['name' => 'Reserved', 'type' => ''],
		12 => ['name' => '64 bit, signed', 'type' => '64'],
		13 => ['name' => '64 bit, unsigned', 'type' => '64u'],
		14 => ['name' => 'Matlab array', 'type' => 'array'],
		15 => ['name' => 'Compressed', 'type' => 'gzip'],
		16 => ['name' => 'UTF-8', 'type' => 'utf8'],
		17 => ['name' => 'UTF-16', 'type' => 'utf16'],
		18 => ['name' => 'UTF-32', 'type' => 'utf32'],
	];

	public function __construct($filename){
		if(file_exists($filename)){
			$this->filename = $filename;
		} else {
			throw new Exception('File not found');
		}

		if(!($this->fhandle = fopen($this->filename, 'r'))){
			throw new Exception('Unable to open file');
		}

		//Get the description
		if(!($this->header['description'] = fread($this->fhandle, 116))){
			throw new Exception('Invalid file format');
		} else {
			$this->header['description'] = trim($this->header['description']);
		}
		//skip past the offset
		fseek($this->fhandle, 8, SEEK_CUR);

		//Get the version
		if(!($this->header['version'] = fread($this->fhandle, 2))){
			throw new Exception('Invalid file format');
		} else {
			$this->header['version'] = unpack('v*', $this->header['version'])[1];
		}

		//Get the byte order
		if(!($this->header['endian'] = fread($this->fhandle, 2))){
			throw new Exception('Invalid file format');
		} else {
			$n = $this->header['endian'];

			if($n == 'IM'){
				$this->flip_endian = true;
				$n = $this->flip_endian($n);
			}

			$this->header['endian'] = $n;
		}
	}

	public function close(){
		fclose($this->fhandle);
	}

	private function flip_endian($value, $type = 16){
		$types = [
			16 => [
				'v', 'n'
			],
			32 => [
				'V', 'N'
			],
			64 => [
				'd', 'd'
			]
		];

		if(!isset($types[$type])){
			throw new Exception('Invalid type passed');
		}

		if(!$this->flip_endian){
			return $value;
		}

		$unpacked = unpack($types[$type][0] . '*', $value);

		return implode('', array_map(function($v) use ($types, $type){
			return pack($types[$type][1] . '*', $v);
		}, $unpacked));
	}

	//Returns false when there are no more elements
	public function nextElement($offset = 0, $hard_read_length = 0){
		$header = [
			'type' => 0,
			'length' => 0
		];

		$data = null;

		$smallData = false;

		if(!($type = fread($this->fhandle, 4))){
			if(feof($this->fhandle)){
				return false;
			}
			throw new Exception('Invalid file format');
		} else {

			//Check for small data format
			$n = unpack('n*', $this->flip_endian($type, 16));
			if($n[2] == 0){
				$n = unpack('N*', $this->flip_endian($type, 32))[1];
			} else {
				$smallData = true;
				$header['length'] = 4;
				$n = $n[1];
			}

			if(isset(self::$DATATYPES[$n])){
				$n = self::$DATATYPES[$n];
			} else {
				throw new Exception('Type not found: ' . $n);
			}

			$header['type'] = $n;
		}

		if(!$smallData){
			if(!($length = fread($this->fhandle, 4))){
				throw new Exception('Invalid file format');
			} else {
				$header['length'] = unpack('N*', $this->flip_endian($length, 32))[1];
			}
		}

		if($header['length'] != 0 || $hard_read_length){
			$read = $hard_read_length ?: $header['length'];

			if($offset){
				fseek($this->fhandle, $offset, SEEK_CUR);
			}

			switch($header['type']['type']){
				case '8':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('c*', $n);
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '8u':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('C*', $n);
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '16':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('n*', $this->flip_endian($n, 16));
						foreach($data as &$d){
							if($d >= pow(2, 15)){
								$d -= pow(2, 16);
							} 
						}
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '16u':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('n*', $this->flip_endian($n, 16));
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '32':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('N*', $this->flip_endian($n, 32));
						foreach($data as $k => $d){
							if($d >= pow(2, 31)){
								$data[$k] = $d - pow(2, 32);
							} 
						}
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '32u':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('N*', $this->flip_endian($n, 32));
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '64':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('d*', $this->flip_endian($n, 64));
						foreach($data as $k => $d){
							if($d >= pow(2, 63)){
								$data[$k] = $d - pow(2, 64);
							} 
						}
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case '64u':
					if(!($n = fread($this->fhandle, $read))){
						throw new Exception('Invalid file format');
					} else {
						$data = unpack('d*', $this->flip_endian($n, 64));
						if(count($data) == 1){
							$data = $data[1];
						}
					}
					break;
				case 'gzip':
				case 'utf8':
				case 'utf16':
					// if(!($n = fread($this->fhandle, $header['length']))){
					// 	throw new Exception('Invalid file format');
					// } else {
					// 	$data = unpack('S*', $n);
					// 	if(count($data) == 1){
					// 		$data = $data[1];
					// 	}
					// }
					// break;
				case 'utf32':
				default:
					fseek($this->fhandle, $header['length'], SEEK_CUR);
					$data = 'type unsupported';
					break;
				case 'array':
					$data = new MatlabArray($this->filename, ftell($this->fhandle), $header['length'], $this->flip_endian);
					fseek($this->fhandle, $header['length'], SEEK_CUR);
			}

		}

		if(!$smallData && ($header['length'] % 8) != 0){
			//echo "Skipping " . (8 - ($header['length'] % 8)) . " bytes (read length of " . $header['length'] . ")\n";
			//skip past the padding
			fseek($this->fhandle,  8 - ($header['length'] % 8), SEEK_CUR); 
		}

		return $data;
	}

	public function getOffset(){
		return ftell($this->fhandle);
	}

	public function setOffset($offset){
		fseek($this->fhandle, $offset);
	}
}