<?php
class SeizureData implements JsonSerializable {
	public $isSeizure = null;
	public $channelData = [];
	public $sequence = 0;
	
	public function __construct($channelData, $sequence, $isSeizure = null){
		$this->isSeizure = $isSeizure;
		$this->channelData = $channelData;
		$this->sequence = $sequence;
	}
	
	public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}