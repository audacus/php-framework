<?php

/*
 * This class is responsible for managing the request processing.
 * It initializes the controller and calls the demanded method with the given parameters.
 * When an AJAX request is made it will return the result of the controller method formated as JSON.
 * Depending on the http method used it will call a controller method with the same name.
 *
 * @author david burkhart
 */
class Rest {

	const METHOD_METHOD = 'method';
	const METHOD_GET = 'get';
	const METHOD_POST = 'post';
	const METHOD_DELETE = 'delete';
	const METHOD_PUT = 'put';
	const METHOD_PATCH = 'patch';
	const DEFAULT_METHOD = self::METHOD_GET;
	const FORMAT_JSON = 'json';
	const FORMAT_XML = 'xml';
	const DEFAULT_FORMAT = self::FORMAT_JSON;

	private $request;
	private $controller;
	private $methods = array();
	private $formats = array(
		self::FORMAT_JSON,
		self::FORMAT_XML
	);

	/*
	 * Constructor of the the class.
	 * It initializes the rest request and prints the result of the request processing.
	 */
	public function __construct() {
		// include RestRequest
		include_once Config::get('app.rest.request');
		$this->request = new RestRequest();
		$urlElements = $this->request->getUrlElements();
		echo $this->dispatch($this->processParams($urlElements));
	}

	/*
	 * Here happens the magic.
	 * With the given parameters it initializes the demanded controller and calls the method for getting the result.
	 * The requests are chainable. If chained the result of the first controller will be given to the second one, etc.
	 *
	 * @param array $urlParams the url parameters from the request. containing data or information about what is requested
	 * @param $previousvalue if request was chained this is the result of the previous processing
	 * @return returns the result of the processing through the controllers
	 */
	private function processParams(array &$urlParams, $previousValue = null) {
		// controller
		$controllerName = Config::get('app.defaultcontroller');
		if (isset($urlParams[0])) {
			$controllerName = $urlParams[0];
		}
		$controllerClassName = \Helper::getFullClassNameController($controllerName);
		try {
			$this->controller = new $controllerClassName();
			$this->controller->setRequest($this->request);
		} catch (\Exception $e) {
			if ($e instanceof \ClassNotFoundException) {
				throw new ControllerNotFoundException($e->getMessage());
			} else {
				throw $e;
			}
		}

		// method method
		$methodMethod = null;
		if (method_exists($this->controller, self::METHOD_METHOD)) {
			$methodMethod = self::METHOD_METHOD;
		}

		// method
		if (count($urlParams) > 2) {
			$method = self::DEFAULT_METHOD;
		} else {
			$method = strtolower($this->request->getVerb());
		}
		// param
		$methodParam = null;
		if (isset($urlParams[1])) {
			$methodParam = $urlParams[1];
		}
		// process
		$returnValue = null;
		if (method_exists($this->controller, $methodMethod)) {
			$data = $this->request->getParameters();
			$returnValue = $this->controller->$methodMethod($method, $methodParam, $data, $previousValue);
		} else {
			throw new ClassMethodNotFoundException($this->controller, $methodMethod);
		}

		// prepare for next loop
		$urlParams = array_slice($urlParams, 2);
		if (!empty($urlParams)) {
			$returnValue = $this->processParams($urlParams, $returnValue);
		}
		return $returnValue;
	}

	/*
	 * This method decides if a view has to be rendered or not.
	 * It can detect a cli call and returns the result not formatted.
	 * If an AJAX request has been made the result will be return formatted as JSON.
	 *
	 * @param $value the result to dispatch
	 * @return returns formatted or raw result
	 */
	private function dispatch($value = null) {
		$returnValue = null;
		if (!Helper::isCliCall()) {
			if (Helper::isAjaxRequest()) {
				$returnValue = $this->formatJson($value);
			} else {
				if (!Config::get('app.view.default.render') || $this->wantFormat()) {
					$returnValue = $this->formatValue($value);
				} else {
					$this->controller->renderView();
				}
			}
		}
		return $returnValue;
	}

	private function getFormat() {
		$format = null;
		if (isset($_REQUEST['format']) && in_array($_REQUEST['format'], $this->formats)) {
			$format = $_REQUEST['format'];
		} else {
			foreach (array_keys($_REQUEST) as $param) {
				if (in_array($param, $this->formats)) {
					$format = $param;
				}
			}
		}
		return $format;
	}

	private function wantFormat() {
		return is_null($this->getFormat()) ? false : true;
	}

	private function formatValue(&$value) {
		$setFormat = $this->getFormat();
		$format = is_null($setFormat) ? Config::get('app.view.default.format') : $setFormat;
		switch ($format) {
			case self::FORMAT_XML:
				$this->formatXml($value);
				break;
			case self::FORMAT_JSON:
			default:
				$this->formatJson($value);
				break;
		}
		return $value;
	}

	private function formatJson(&$value) {
		if (is_array($value) || is_object($value)) {
			$value = addslashes(json_encode($value, JSON_NUMERIC_CHECK));
		}
		header('Content-Type: application/json');
		return $value;
	}

	private function formatXml(&$value) {
		header('Content-Type: text/xml');
		return $value = 'XML not yet supported.<br />'.print_r($value, true);
	}
}
