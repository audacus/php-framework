<?php

class RestRequest {

	private $urlElements;
	private $verb;
	private $parameters;

	public function __construct() {
		$this->verb = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		$this->urlElements = explode('/', substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME'])+1));
		if (empty(end($this->urlElements))) {
			array_pop($this->urlElements);
		}
		$this->parseIncomingParams();
		// initialise json as default format
		$this->format = 'json';
		if(isset($this->parameters['format'])) {
			$this->format = $this->parameters['format'];
		}
		return true;
	}

	public function parseIncomingParams() {
		$parameters = array();

		// first of all, pull the GET vars
		if (isset($_SERVER['QUERY_STRING'])) {
			parse_str($_SERVER['QUERY_STRING'], $parameters);
		}

		// now how about PUT/POST bodies? These override what we got from GET
		$body = file_get_contents('php://input');
		$contentType = false;
		if(isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = $_SERVER['CONTENT_TYPE'];
		}

		if (strpos($contentType, 'application/json') !== false) {
			$bodyParams = json_decode(json_encode(json_decode($body, true)));
			if($bodyParams) {
				foreach($bodyParams as $paramName => $paramValue) {
					$parameters[$paramName] = $paramValue;
				}
			}
			$this->format = 'json';
		}
		if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
			parse_str($body, $postvars);
			foreach($postvars as $field => $value) {
				$parameters[$field] = $value;

			}
			$this->format = 'html';
		}

		$this->parameters = $parameters;
	}

	public function getUrlElements() {
		return $this->urlElements;
	}

	public function setUrlElements($urlElements){
		$this->urlElements = $urlElements;
		return $this;
	}

	public function getVerb() {
		return $this->verb;
	}

	public function setVerb($verb){
		$this->verb = $verb;
		return $this;
	}

	public function getParameters() {
		return $this->parameters;
	}

	public function setParameters($parameters){
		$this->parameters = $parameters;
		return $this;
	}
}
