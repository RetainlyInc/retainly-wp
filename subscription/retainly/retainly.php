<?php

/**
 * Retainly API V2
 *
 * @author Rajesh Pandurangan
 * @version 1.0.0
 */
class Retainly_Rapidology {
	/** @var string  */
	private $api_key;

	/** @var string  */
	private $api_endpoint = "https://retainly.co/app/apiv2";

	/**
	 * Create a new instance
	 * @param string $api_key Your Retainly API key
	 */
	function __construct($api_key) {
		$this->api_key = $api_key;
	}

	/**
	 * Call an API method
	 *
	 * @param  string $endpoint The API endpoint to call, Example:  'tags'
	 * @param  array  $args     An array of arguments to pass to the method that will be json-encoded
	 * @param  string $method   GET OR POST
	 * @return array|WP_Error   associative array of json decoded API response or WP_ERROR
	 */
	public function call($endpoint, $args=array(), $method = 'GET') {
		return $this->makeRequest( $endpoint, $args, $method);
	}

	/**
	 * Performs the request
	 *
	 * @param  string           $endpoint The API method to be called
	 * @param  array            $args   Assoc array of parameters to be passed
	 * @param  string           $method  Either GET or POST
	 * @return array|WP_error   Assoc array of decoded result
	 */
	private function makeRequest( $endpoint, $args=array(), $method ) {

		$called_url = $this->api_endpoint."/".$endpoint;
        $ch = curl_init($called_url);
        $auth_header = 'ret-api-key:'.$this->api_key;
        $content_header = "Content-Type:application/json";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows only over-ride
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header,$content_header));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        $data = curl_exec($ch);
        if(curl_errno($ch))
        {
            echo 'Curl error: ' . curl_error($ch). '\n';
        }
        curl_close($ch);
        $temp = json_decode($data);//print_r($temp->status);die();
        if(isset($temp->status))
        {
        	if($temp->status == "true" || $temp->status === true)
        		return 'success';
        	if($temp->status == "false" || $temp->status === false)
        	{
        		//print_r($args);
        		echo $temp->message;die();
        	}
        	

        }

        return json_decode($data,true);
	}
}
