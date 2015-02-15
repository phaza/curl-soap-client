<?php namespace CurlSoapClient;

use \Monolog\Logger;

/**
 * A wrapper around \SoapClient that uses cURL to make the requests
 */
class CurlSoapClient extends \SoapClient
{

	protected $logger;
	protected $curlOptions;

	function __construct($wsdl, array $options, array $curlOptions, Logger $logger)
	{
		$this->logger = $logger;
		$this->curlOptions = $curlOptions;

		$this->logger->debug($wsdl, array('options' => $options, 'curl Options' => $curlOptions));

		parent::__construct($wsdl, $options);
	}


	/**
	 * We override this function from parent to use cURL.
	 *
	 * @param string $request
	 * @param string $location
	 * @param string $action
	 * @param int $version
	 * @param int $one_way
	 * @return string
	 * @author Peter Haza
	 */
	public function __doRequest($request, $location, $action, $version, $one_way = 0) {

		$this->__last_request = $request;
        
		$headers = array(
			'Connection: Close',
			'Content-Type: application/soap+xml',
			sprintf('SOAPAction: "%s"', $action),
			sprintf('Content-Length: %d', strlen($request))
		);
        
		$this->logger->debug('Request: '.$request, $headers);

		$ch = curl_init($location);

		$options = $this->curlOptions + array(
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYHOST => true,
			CURLOPT_SSL_VERIFYPEER => true
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_POSTFIELDS     => $request
		);

		if($this->logger->isHandling(Logger::DEBUG)) {
			$tmp = fopen('php://temp', 'r+');
			$options[CURLOPT_VERBOSE] = true;
			$options[CURLOPT_STDERR]  = $tmp;
		}

		curl_setopt_array($ch, $options);

		$output = curl_exec($ch);

		if(isset($tmp) && is_resource($tmp)) {
			rewind($tmp);
			$this->logger->debug(stream_get_contents($tmp));
			fclose($tmp);
		}

		$this->logger->debug('Response: '.$output);

		return $output;
	}
}
