<?php

namespace controller;

/*
 * This class provides the standard functionality of a controller.
 * The controller is responsible for processing the request and providing the methods for database accessibility.
 * It is also responsible for rendering the view if demanded.
 * If a controller has a appropriate model it can be directly created through the controller.
 * If a site requires a login or not is decided within the controller initialization.
 * The core methods are named after the http methods.
 *
 * function calling order for processing request:
 * 1st method()					DO NOT OVERRIDE! does not exist -> force __call()
 * 3rd before<method>()			override to do something before the method call
 * 4th <method>Data()			override to modify default database actions
 * 5th modifyResult<method>()	override to change the way the result is modified
 * 6th after<method>()			override to do something after method call
 *
 * @author david.burkhart
 */
abstract class AbstractController {

	// database
	protected $tableName;

	// can be an array or a string
	// array: array('foreignfield' => 'field')
	// array: array('foreignfieldone', foreignfieldtwo') same as array('foreignfieldone' => 'id', 'foreignfieldtwo' => 'id')
	// array: array('foreignfieldone => 'field', foreignfieldtwo') same as array('foreignfieldone' => 'field', 'foreignfieldtwo' => 'id')
	// string: 'foreignfield' same as array('foreignfield' => 'id')
	protected $foreignKey;

	protected $noView = false; // if the view of the controller shall be rendered or not
	protected $loginRequired = false; // if the site requires a login to proceed or not
	protected $request; // a instance of RestRequest with all the parameters and data
	protected $view; // the instance of the view that will be rendered
	protected $model; // the name of the appropriate model
	protected $errors; // variable to save errors when no view is available

	// method params
	protected $id; // first argument after the controllers name e.g.: /signup/activation -> id = activation
	protected $data; // data of the request. including request body params and url params
	protected $previousValue; // result of the preceding request processing e.g.: /users/42/luainstances -> user with id 42 given to the luainstances
	protected $result; // result that will be returned

	/*
	 * Constructor of the controller.
	 * This method instances all the things needed for further processing (model, database, login, view).
	 * If as argument another controller instance is given, it will render the view of the given controller instead of the current controller.
	 *
	 * @param AbstractController $controller if given, its view will be rendered
	 */
	public function __construct(AbstractController $controller = null) {
		// set table name
		if (empty($this->tableName)) {
			$this->setTableName();
		}
		// set foreign key
		$this->setForeignKey($this->foreignKey);
		// set model
		if (empty($this->model)) {
			$this->setModel();
		}
		// set view
		if (!empty($controller)) {
			$this->setView($controller->getView());
		} else if (!$this->noView) {
			$this->setView();
		}
		// check login
		// if no user is logged in and the page requires a login
		$isLoggedIn = \Security::isLoggedIn();
		if (!\Helper::isCliCall() && !$isLoggedIn && $this->loginRequired) {
			\Helper::redirect('login?redirect='.\Helper::getLowerCaseClassName($this));
		} else if ($isLoggedIn) {
			// renew cookie/session
			$user = \Security::update();
			// set info
			$this->setInfo($user);
		}
	}

	/*
	 * This method will be called, if a non-existing method is being called e.g.: $controller->_post();
	 * It does distribute the given arguments to the appropriate place and calls the method method.
	 *
	 * @param $method the name of the non-existing method that was being called
	 * @param $args the arguments given to the non-existing method
	 */
	public function __call($method, $args) {
		$returnValue = null;
		switch ($method) {
			case 'get':
			case 'delete':
			case 'patch':
			case 'put':
				$returnValue = $this->method($method,
					isset($args[0]) ? $args[0] : null, // id
					isset($args[1]) ? $args[1] : array(), // data
					isset($args[2]) ? $args[2] : null); // previous value
				break;
			case 'post':
				$returnValue = $this->method($method,
					null, // id
					isset($args[0]) ? $args[0] : array(), // data
					isset($args[1]) ? $args[1] : null); // previous value
				break;
			default:

		}
		return $returnValue;
	}

	/*
	 * This is the so called "method method".
	 * This method is calling all the other methods and defines the order of calling.
	 * It is like the controller of request processing within the controller.
	 *
	 * @param $method name of the method (get, post, patch, delete, put)
	 * @param $id first argument after the controllers name e.g.: /signup/activation -> id = activation
	 * @param array $data data of the request. including request body params and url params
	 * @param $previousValue result of the preceding request processing e.g.: /users/42/luainstances -> user with id 42 given to the luainstances
	 * @return returns the result of the whole processing
	 */
	public function method($method, $id = null, array $data = array(), $previousValue = null) {
		// set request values
		$this->id = $id;
		$this->data = $data;
		$this->previousValue = $previousValue;

		// method
		$this->{'before'.$method}();
		$this->{$method.'Data'}();
		$this->{'modifyResult'.$method}();
		$this->{'after'.$method}();
		return $this->result;
	}

	/*
	 * before methods
	 * These methods will be called before the parameters will be used for getting the result.
	 */
	public function beforeGet() {}

	public function beforePost() {}

	public function beforePut() {}

	public function beforePatch() {}

	public function beforeDelete() {}

	/*
	 * methods
	 * These methods contain the defualt database access functionality for the given method.
	 */
	// returns rows
	public function getData() {
		$this->result = $this->getDb();
		if (!empty($this->id) && is_numeric($this->id)) {
			$this->result = $this->result[$this->id];
		}
		// check previous value
		if (!empty($fkMatch = $this->getForeignKeyMatch())) {
			$this->result = $this->result->where($fkMatch['field'], $fkMatch['value']);
		}
	}

	// returns inserted data
	public function postData() {
		if (!empty($this->data)) {
			// TODO 2016-01-09 david: unset every NON-DB field from data
			$this->result = $this->getDb()->insert($this->data);
		}
	}

	// returns number of affected rows
	public function putData() {
		if (!empty($this->id)) {
			$row = $this->getDb()[$this->id];
			if ($row) {
				$this->result = $row->update($this->data);
			}
		}
	}

	// returns number of affected rows
	public function patchData() {
		$this->putData();
	}

	// returns number of deleted rows
	public function deleteData() {
		$this->result = 0;
		if (!empty($this->id)) {
			$row = $this->getDb()[$this->id];
			if (!empty($row)) {
				$this->result = $row->delete();
			}
		}
	}

	/*
	 * modify result methods
	 * These methods are used to modify the result after the core request methods are called.
	 */
	public function modifyResultGet() {
		$this->result = \Helper::checkNumericValues(\Database::resultToArray($this->result, false));
	}

	public function modifyResultPost() {
		$this->result = \Helper::checkNumericValues(\Database::resultToArray($this->result));
	}

	public function modifyResultPut() {}

	public function modifyResultPatch() {}

	public function modifyResultDelete() {}

	/* after method */
	public function afterGet() {
		if (isset($this->data['logout'])) {
			\Helper::redirect('logout');
		}
	}

	/*
	 * after methods
	 * These methods will be called before returning the result.
	 */
	public function afterPost() {}

	public function afterPut() {}

	public function afterPatch() {}

	public function afterDelete() {}

	public function renderView() {
		if ($this->noView && empty($this->view)) {
			echo 'no view';
		} else if (!empty($this->view)) {
			// set sidebar before rendering
			$this->setSidebar();
			echo $this->view->getSiteContent();
		}
	}

	/*
	 * Set the controller's request.
	 *
	 * @param RestRequest $request the request instance to be set on the controller
	 * @return AbstractController returns the controller
	 */
	public function setRequest(\RestRequest $request) {
		$this->request = $request;
		return $this;
	}

	/*
	 * Returns the view of the controller. If the view is empty it will be set first.
	 *
	 * @return AbstractVeiw returns the view of the controller
	 */
	public function getView() {
		if (empty($this->view)) {
			$this->setView();
		}
		return $this->view;
	}

	/*
	 * Get the appropriate database connection for the table that belongs to the controller.
	 * If a table name is given, instead of the appropriate table of the controller, the table with the given name is returned.
	 *
	 * @param $tableName name of the table that will be returned
	 * @return NotORM returns NotORm database connection
	 */
	public function getDb($tableName = null) {
		return \Database::getDb(empty($tableName) ? $this->getTableName() : $tableName);
	}

	/*
	 * Search in the controller table with the given field and value.
	 *
	 * @param $field name of the field that will be searched in
	 * @param $value search value of the field
	 * @return array returns array with models from the search result
	 */
	public function getBy($field, $value) {
		$models = array();
		$result = \Database::resultToArray($this->getDb()->where($field, $value));
		if (!empty($result)) {
			foreach ($result as $row) {
				$models[] = new $this->model($row);
			}
		}
		return $models;
	}

	/*
	 * Get the table name of the controller.
	 *
	 * @return string the name of the table
	 */
	public function getTableName() {
		return $this->tableName;
	}

	public function setTableName($tableName = null) {
		if (empty($tableName)) {
			$className = \Helper::getLowerCaseClassName($this);
			$length = (strrpos($className, 's') === strlen($className)-1 ? -1 : strlen($className));
			$this->tableName = substr($className, 0, $length);
		} else {
			$this->tableName = $tableName;
		}
		return $this;
	}

	public function getModel() {
		return $this->model;
	}

	/*
	 * This method returns an instance of the appropriate model of the controller.
	 *
	 * @param array $data data to put into the model instance
	 * @return AbstractModel returns an instance of the appropriate model
	 */
	public function newModel(array $data = array()) {
		return empty($this->model) ? null : new $this->model($data);
	}

	public function setModel($modelName = null) {
		if (empty($modelName)) {
			$modelName = \Helper::getFullClassNameModel(\Helper::getLowerCaseClassName($this));
			$length = (strrpos($modelName, 's') === strlen($modelName)-1 ? -1 : strlen($modelName));
			$this->model = substr($modelName, 0, $length);
		} else {
			$this->model = $modelName;
		}
		return $this;
	}

	protected function setView(\view\AbstractView $view = null) {
		if (empty($view)) {
			$className = \Helper::getFullClassNameView(\Helper::getLowerCaseClassName($this));
			try {
				$this->view = new $className();
			} catch (\Exception $e) {
				if ($e instanceof \ClassNotFoundException) {
					throw new \ViewNotFoundException($e->getMessage());
				} else {
					throw $e;
				}
			}
		} else {
			$this->view = $view;
		}
		return $this;
	}

	protected function setSidebar() {
		$user = \Security::getLoggedInUser();
		$this->view->setSidebar(\Helper::getSidebar(empty($user) ? null : $user->getRole_id()));
		return $this;
	}

	private function setInfo(\model\User $user = null) {
		// set info
		if (!empty($user) && !empty($this->view)) {
			// userinfo
			if (!empty($user)) {
				$this->view->appendData('user', array(
					'username' => $user->getUsername(),
					'email' => $user->getEmail(),
					'token' => $user->getToken(),
					'series' => $user->getSeries(),
					'lastlogin' => $user->getLastlogin()
				));
			}
			// cookieinfo
			if (\Security::doesCookieExist()) {
				$this->view->appendData('cookie', array(
					'username' => $_COOKIE['username'],
					'token' => $_COOKIE['token'],
					'series' => $_COOKIE['series']
				));
			}
			// session
			if (\Security::doesSessionExist()) {
				$this->view->appendData('session', array(
					'username' => $_SESSION['username'],
					'token' => $_SESSION['token'],
					'series' => $_SESSION['series'],
					'sessionid' => session_id()
				));
			}
		}
	}

	private function setForeignKey($foreignKey = null) {
		$fk = array();
		if (is_string($foreignKey) && !empty($foreignKey)) {
			$fk[$foreignKey] = 'id';
		} else if (is_array($foreignKey) && !\Helper::hasEmptyValue($foreignKey)) {
			if (\Helper::isAssociativeArray($foreignKey)) {
				foreach ($foreignKey as $foreignField => $field) {
					if (is_numeric($foreignField)) {
						$fk[(string) $field] = 'id';
					} else {
						$fk[$foreignField] = (string) $field;
					}
				}
			} else {
				foreach ($foreignKey as $foreignField) {
					$fk[$foreignField] = 'id';
				}
			}
		}
		$this->foreignKey = empty($fk) ? null : $fk;
		return $this;
	}

	private function getForeignKeyMatch() {
		$fkMatch = array();
		if (!empty($this->previousValue)) {
			foreach ($this->foreignKey as $foreignField => $field) {
				if (array_key_exists($foreignField, $this->previousValue)) {
					$fkMatch = array(
						'foreignfield' => $foreignField,
						'field' => $field,
						'value' => $this->previousValue[$foreignField]
					);
				}
			}
		}
		return empty($fkMatch) ? null : $fkMatch;
	}
}
