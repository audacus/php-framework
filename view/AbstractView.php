<?php

namespace view;

use \Config;
use \Helper;

abstract class AbstractView {

	const FILE_ENDING_CSS = 'css';
	const FILE_ENDING_JS = 'js';
	const SCRIPT_HEADER = 'header';
	const SCRIPT_CONTENT = 'content';
	const SCRIPT_FOOTER = 'footer';

	protected $header;
	protected $content;
	protected $footer;
	protected $data = array();
	protected $sidebar = array();
	protected $error = array();
	protected $cssFiles;
	protected $jsFiles;

	public function __construct($viewName = null) {
		$this->header = $this->getHeader();
		$this->content = $this->getContent($viewName);
		$this->footer = $this->getFooter();
		$this->setCssFiles(array_merge($this->getDefaultCssFiles(), $this->getControllerViewCssFiles()));
		$this->setJsFiles(array_merge($this->getDefaultJsFiles(), $this->getControllerViewJsFiles()));
	}

	public function getSiteContent() {
		$siteContent = null;
		ob_start();
		include_once($this->header);
		include_once($this->content);
		include_once($this->footer);
		$siteContent = ob_get_contents();
		ob_end_clean();
		return $siteContent;
	}

	public function getData($key = null) {
		$data = null;
		if (empty($key)) {
			$data = $this->data;
		} else if (is_array($this->data) && isset($this->data[$key])) {
			$data = $this->data[$key];
		}
		return $data;
	}

	public function setData($key, $value = null) {
		if (is_null($value)) {
			$value = $key;
			$this->data = $value;
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	public function getSideBar($key = null) {
		$sidebar = null;
		if (empty($key)) {
			$sidebar = $this->sidebar;
		} else if (is_array($this->data) && isset($this->data[$key])) {
			$sidebar = $this->sidebar[$key];
		}
		return $sidebar;
	}

	public function setSidebar(array $sidebar) {
		$this->sidebar = $sidebar;
		return $this;
	}

	public function appendData($key, $value = null) {
		return $this->appendToVar('data', $key, $value);
	}

	public function getError() {
		return $this->error;
	}

	public function setError($error) {
		$this->error = $error;
		return $this;
	}

	public function appendError($key, $value = null) {
		return $this->appendToVar('error', $key, $value);
	}

	public function getCssFiles() {
		if (is_null($this->cssFiles)) {
			$this->setCssFiles();
		}
		return $this->cssFiles;
	}

	public function setCssFiles(array $cssFiles = array()) {
		$this->cssFiles = $cssFiles;
		return $this;
	}

	public function pendFile($fileEnding, $folder, $file, $appendBaseUrl = true, $prepend = false) {
		if ($appendBaseUrl) {
			$parts = array(
				Config::get('app.url.base'),
				$folder,
				$file
			);
			$file = Helper::makePathFromParts($parts);
		}
		$files = strtolower($fileEnding).'Files';
		if (empty($this->$files)) {
			$this->$files = array();
		}
		$temp = $this->$files;
		if ($prepend) {
			array_unshift($temp, $file);
		} else {
			array_push($temp, $file);
		}
		$this->$files = $temp;
		return $this;
	}

	public function appendCssFile($cssFile, $appendBaseUrl = true) {
		return $this->pendCssFile($cssFile, $appendBaseUrl, false);
	}

	public function prependCssFile($cssFile, $appendBaseUrl = true) {
		return $this->pendCssFile($cssFile, $appendBaseUrl, true);
	}

	public function pendCssFile($cssFile, $appendBaseUrl = true, $prepend) {
		return $this->pendFile(self::FILE_ENDING_CSS, Config::get('app.url.css.default'), $jsFile, $appendBaseUrl, $prepend);
	}

	public function appendCssFiles(array $cssFiles, $appendBaseUrl = true) {
		foreach ($cssFiles as $cssFile) {
			$this->appendCssFile($cssFile, $appendBaseUrl);
		}
		return $this;
	}

	public function getJsFiles() {
		if (is_null($this->jsFiles)) {
			$this->setJsFiles(array());
		}
		return $this->jsFiles;
	}

	public function setJsFiles(array $jsFiles = array()) {
		$this->jsFiles = $jsFiles;
		return $this;
	}

	public function appendJsFile($jsFile, $appendBaseUrl = true) {
		return $this->pendJsFile($jsFile, $appendBaseUrl, false);
	}

	public function prependJsFile($jsFile, $appendBaseUrl = true) {
		return $this->pendJsFile($jsFile, $appendBaseUrl, true);
	}

	public function pendJsFile($jsFile, $appendBaseUrl = true, $prepend) {
		return $this->pendFile(self::FILE_ENDING_JS, Config::get('app.url.js.default'), $jsFile, $appendBaseUrl, $prepend);
	}

	public function appendJsFiles(array $jsFiles, $appendBaseUrl = true) {
		foreach ($jsFiles as $jsFile) {
			$this->appendJsFile($jsFile, $appendBaseUrl);
		}
		return $this;
	}

	public function appendToVar($variable, $key, $value = null) {
		if (empty($this->$variable)) {
			$this->$variable = array();
		}
		$variableTemp = $this->$variable;
		if (empty($value)) {
			$value = $key;
			if (Helper::isIterable($value)) {
				if (!is_array($value)) {
					$value = iterator_to_array($value);
				}
				array_merge($variableTemp, $value);
			} else {
				array_push($variableTemp, $value);
			}
		} else {
			$variableTemp[$key] = $value;
		}
		$this->$variable = $variableTemp;
		return $this;
	}

	public function setHeader($file = null) {
		return $this->setScript(self::SCRIPT_HEADER, $file);
	}

	public function setContent($file = null) {
		return $this->setScript(self::SCRIPT_CONTENT, $file);
	}

	public function setFooter($file = null) {
		return $this->setScript(self::SCRIPT_FOOTER, $file);
	}

	public function getCssPath() {
		return $this->getScriptsPath(self::FILE_ENDING_CSS);
	}

	public function getJsPath() {
		return $this->getScriptsPath(self::FILE_ENDING_JS);
	}

	private function getScript($script = null, $pathScripts = null, $filename = null, $prependApplicationPath = true) {
		$file = null;
		if (empty($this->$script) && !empty($script)) {
			$scriptPathParts = array(
				APPLICATION_PATH,
				empty($pathScripts) ? Config::get('app.path.viewscripts.scripts') : $pathScripts,
				empty($filename) ? Config::get('app.path.viewscripts.'.$script) : $filename
			);
			if (!$prependApplicationPath) {
				array_shift($scriptPathParts);
			}
			$file = new \SplFileInfo(Helper::makePathFromParts($scriptPathParts));
		} else if (!empty($this->$script)) {
			$file = $this->$script;
		}
		return $file;
	}

	private function getHeader() {
		return $this->getScript(self::SCRIPT_HEADER);
	}

	private function getContent($viewName = null) {
		return $this->getScript(self::SCRIPT_CONTENT, sprintf(
				Config::get('app.path.viewscripts.controller'),
				empty($viewName) ? Helper::getLowerCaseClassName($this) : $viewName));
	}

	private function getFooter() {
		return $this->getScript(self::SCRIPT_FOOTER);
	}

	private function setScript($script, $file = null) {
		if (empty($file) && empty($this->$script)) {
			$this->$script = $this->getScript($script);
		} else {
			if ($file instanceof \SplFileInfo) {
				$this->$script = $script;
			} else if (is_string($file)) {
				$this->$script = new \SplFileInfo($file);
			}
		}
		return $this;
	}

	private function getScriptsPath($fileEnding, $print = false) {
		$parts = array(
			APPLICATION_PATH,
			Config::get('app.path.public'),
			sprintf(Config::get('app.url.'.$fileEnding.'.controller'), Helper::getLowerCaseClassName($this))
		);
		$path = Helper::getRelativePath(Helper::makePathFromParts($parts));
		if ($print) {
			echo $path;
		}
		return $path;
	}

	private function getDefaultFiles($folder, $fileEnding = '') {
		/* $parts = array(APPLICATION_PATH);
		if (!empty(Config::get('app.path.public'))) {
			$parts[] = Config::get('app.path.public');
		}
		$parts = array_merge($parts, array($folder, '*.'.$fileEnding));
		*/ // TODO: remove, added for reference if app.path.public == ""
		$parts = array(
			APPLICATION_PATH,
			Config::get('app.path.public'),
			$folder,
			'*.'.$fileEnding
		);

		return Helper::getRelativePaths(glob(Helper::makePathFromParts($parts)));
	}

	private function getDefaultCssFiles() {
		return $this->getDefaultFiles(Config::get('app.url.css.default'), self::FILE_ENDING_CSS);
	}

	private function getDefaultJsFiles() {
		return $this->getDefaultFiles(Config::get('app.url.js.default'), self::FILE_ENDING_JS);
	}

	private function getControllerViewFiles($folder, $fileEnding = '', $viewName = '') {
		$viewFilesPathParts = array(
			APPLICATION_PATH,
			Config::get('app.path.public'),
			sprintf($folder, empty($viewName) ? Helper::getLowerCaseClassName($this) : $viewName),
			'[!_]*.'.$fileEnding
		);
		return Helper::getRelativePaths(glob(Helper::makePathFromParts($viewFilesPathParts)));
	}

	private function getControllerViewCssFiles($viewName = '') {
		return $this->getControllerViewFiles(Config::get('app.url.css.controller'), self::FILE_ENDING_CSS, $viewName);
	}

	private function getControllerViewJsFiles($viewName = '') {
		return $this->getControllerViewFiles(Config::get('app.url.js.controller'), self::FILE_ENDING_JS, $viewName);
	}
}
