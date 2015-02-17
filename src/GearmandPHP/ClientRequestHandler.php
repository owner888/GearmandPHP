<?php

namespace GearmandPHP;

class ClientRequestHandler
{
	// Request Types
	const SUBMIT_JOB = 7;
	const GET_STATUS = 15;
	const ECHO_REQ = 16;
	const SUBMIT_JOB_BG = 18;
	const SUBMIT_JOB_HIGH = 21;
	const OPTION_REQ = 26;
	const SUBMIT_JOB_HIGH_BG = 32;
	const SUBMIT_JOB_LOW = 33;
	const SUBMIT_JOB_LOW_BG = 34;
	const SUBMIT_JOB_SCHED = 35;
	const SUBMIT_JOB_EPOCH = 36;

	// Response Types
	const JOB_CREATED = 8;
	const WORK_STATUS= 12;
	const WORK_COMPLETE = 13;
	const WORK_FAIL = 14;
	const ECHO_RES = 17;
	const ERROR = 19;
	const STATUS_RES = 20;
	const WORK_EXCEPTION = 25;
	const OPTION_RES = 27;
	const WORK_DATA = 28;
	const WORK_WARNING = 29;

	private $bev;

	public function __construct($bev){
		$this->bev = $bev;
	}

	public function handle($type,$data){
		switch($type){
			case self::SUBMIT_JOB:
				$this->handleSubmitJob($data);
				break;
			case self::GET_STATUS:
				$this->handleGetStatus($data);
				break;
			case self::ECHO_REQ:
				$this->handleEchoReq($data);
				break;
			case self::SUBMIT_JOB_BG:
				$this->handleSubmitJobBg($data);
				break;
			case self::SUBMIT_JOB_HIGH:
				$this->handleSubmitJobHigh($data);
				break;
			case self::OPTION_REQ:
				$this->handleOptionReq($data);
				break;
			case self::SUBMIT_JOB_HIGH_BG:
				$this->handleSubmitJobHighBg($data);
				break;
			case self::SUBMIT_JOB_LOW:
				$this->handleSubmitJobLow($data);
				break;
			case self::SUBMIT_JOB_LOW_BG:
				$this->handleSubmitLowBg($data);
				break;
			case self::SUBMIT_JOB_SCHED:
				$this->handleSubmitJobSched($data);
				break;
			case self::SUBMIT_JOB_EPOCH:
				$this->handleSubmitJobEpoch($data);
				break;
			// WORK_* requests are relayed from worker
			case self::WORK_DATA:
				$this->handleWorkData($data);
				break;
			case self::WORK_WARNING:
				$this->handleWorkWarning$data);
				break;
			case self::WORK_STATUS:
				$this->handleWorkStatus($data);
				break;
			case self::WORK_COMPLETE:
				$this->handleWorkComplete($data);
				break;
			case self::WORK_FAIL:
				$this->handleWorkFail($data);
				break;
			case self::WORK_EXCEPTION:
				$this->handleWorkException($data);
				break;
			default:
				//INVALID WORKER REQUEST TYPE
				break;
		}
	}

	// WORK_* requests to client are WORK_* requests
	//  received from worker
	private function handleWorkData($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_DATA,$data);
	}

	private function handleWorkWarning($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_WARNING,$data);
	}

	private function handleWorkStatus($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_STATUS,$data);
	}

	private function handleWorkComplete($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_COMPLETE,$data);
	}

	private function handleWorkFail($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_FAIL,$data);
	}

	private function handleWorkException($data){
		list(,$data) = explode(0x00,$data,2);
		$this->sendResponse(self::WORK_EXCEPTION,$data);
	}

	private function handleSubmitJob($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// WILL update client with status
	}

	private function handleGetStatus($data){
		$handle = $data;
		// client is requesting status on a particular job
		$data = implode(0x00, array(
			$handle,
			$this->knowJobStatus() ? 1 : 0,
			$this->isRunning() ? 1 : 0,
			$percent_complete_numerator,
			$percent_complete_denominator
		));
		$this->sendResponse(self::STATUS_RES,$data);
	}

	private function handleEchoReq($data){
		$this->sendResponse(self::ECHO_RES,$data);
	}

	private function handleSubmitJobBg($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// will NOT update client with status
	}

	private function handleSubmitJobHigh($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// WILL update client with status
	}

	private function handleOptionReq($data){
		$option = $data;
		// currently only "exceptions" is a possibility here
		switch($option){
			case 'exceptions':
				// notify server it should forward "WORK_EXCEPTION" packets to client
				$this->sendResponse(self::OPTION_RES,$option);
				break;
		}
	}

	private function handleSubmitJobHighBg($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// will NOT update client with status
	}

	private function handleSubmitJobLow($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// WILL update client with status
	}

	private function handleSubmitJobLowBg($data){
		list($function_name,$unique_id,$data) = explode(0x00,$data);
		// respond with 'JOB_CREATED' packet
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
		// will NOT update client with status
	}

	private function handleSubmitJobSched($data){
		$data = explode(0x00,$data);
		$function_name = $data[0];
		$unique_id = $data[1];
		$minute = $data[2];
		$hour = $data[3];
		$day_of_month = $data[4];
		$month = $data[5];
		$day_of_week = $data[6];
		$data = $data[7];
		// above data tells server at what time to run the job
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
	}

	private function handleSubmitJobEpoch($data){
		list($function_name,$unique_id,$epoch_time,$data) = explode(0x00,$data);
		// like "SUBMIT_JOB_BG", but run job at $epoch_time instead of immediately
		// $handle = $this->assignHandle();
		$this->sendResponse(self::JOB_CREATED,$handle);
	}


	public function sendResponse($type, $message){

		$response = pack('c4',0x00,ord('R'),ord('E'),ord('S'));
		$response.= pack('N',$type);
		$response.= pack('N',strlen($message));
		$response.= $message;

		$output = $this->bev->output;
		return $output->add($response);
	}

}
