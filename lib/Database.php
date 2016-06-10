<?php

class Database {

	private static $db;

	public static function getDb($tableName = null) {
		if (empty(self::$db)) {
			self::setDb();
		}
		$db = self::$db;
		if (!empty($tableName)) {
			$db = self::$db->$tableName;
		}
		return $db;
	}

	public static function setDb(NotORM $db = null) {
		if (empty($db)) {
			$structure = new NotORM_Structure_Convention(
				$primary = Config::get('db.notorm.structure.primary'),
				$foreign = Config::get('db.notorm.structure.foreign'),
				$table = Config::get('db.notorm.structure.table'),
				$prefix = Config::get('db.notorm.structure.prefix')
			);
			$type = Config::get('db.pdo.type');
			// sqlite
			if ($type == 'sqlite') {
				$parts = array(
					APPLICATION_PATH,
					Config::get('db.filepath'),
					Config::get('db.name')
				);
				$dsn = $type.':'.Helper::makePathFromParts($parts).'.sqlite';
				self::$db = new NotORM(new PDO($dsn), $structure);
			} else {
				$dsn = Config::get('db.pdo.type')
					.':dbname='.Config::get('db.name')
					.';host='.Config::get('db.host');
				$user = Config::get('db.user');
				$password = Config::get('db.password');
				self::$db = new NotORM(new PDO($dsn, $user, $password), $structure);
			}
		} else {
			self::$db = $db;
		}
	}

	public static function getDbConfig() {
		return Config::get('db');
	}

	public static function resultToArray($result, $useKeys = true) {
		if (!empty($result)) {
			// if row
			if ($result instanceof NotORM_Row) {
				$result = iterator_to_array($result);
			// if result
			} else if ($result instanceof NotORM_Result) {
				$result = array_map('iterator_to_array', iterator_to_array($result, $useKeys));
			}
		}
		return $result;
	}

	public static function innerJoin($table, $whereTable,
		$tableField, $foreignTable, $whereForeignTable, $foreignField) {
		$objects = array();
		$tablevals = array();
		if (!empty($whereTable)) {
			$tablevals = Database::getDb($table)->where($whereTable);
		} else {
			$tablevals = Database::getDb($table);
		}
		foreach ($tablevals as $row) {
			$tableRow = Database::resultToArray($row);
			$whereForeignTable = array_merge(empty($whereForeignTable) ? array() : $whereForeignTable, array($foreignField => $tableRow[$tableField]));
			$foreignTableRows = Database::resultToArray(Database::getDb($foreignTable)->where($whereForeignTable));
			$tableRow = Helper::prependStringToKeys($tableRow, $table);
			$foreignTableRows = Helper::prependStringToKeys($foreignTableRows, $foreignTable);
			foreach($foreignTableRows as $foreignTableRow) {
				$objects[] = array_merge($foreignTableRow, $tableRow);
			}
		}
		return $objects;
	}

	public static function innerJoin3(
		$table, $whereTable,
		$tableField, $foreignTable, $whereForeignTable, $foreignField,
		$tableField2, $foreignTable2, $whereForeignTable2, $foreignField2) {
		$objects = array();
		$tablevals = array();
		if (!empty($whereTable)) {
			$tablevals = Database::getDb($table)->where($whereTable);
		} else {
			$tablevals = Database::getDb($table);
		}
		foreach ($tablevals as $row) {
			$tableRow = Database::resultToArray($row);
			$whereForeignTable = array_merge(empty($whereForeignTable) ? array() : $whereForeignTable, array($foreignField => $tableRow[$tableField]));
			$foreignTableRows = Database::resultToArray(Database::getDb($foreignTable)->where($whereForeignTable));
			$whereForeignTable2 = array_merge(empty($whereForeignTable2) ? array() : $whereForeignTable2, array($foreignField2 => $tableRow[$tableField2]));
			$foreignTableRows2 = Database::resultToArray(Database::getDb($foreignTable2)->where($whereForeignTable2));
			$tableRow = Helper::prependStringToKeys($tableRow, $table);
			$foreignTableRows = Helper::prependStringToKeys($foreignTableRows, $foreignTable);
			$foreignTableRows2 = Helper::prependStringToKeys($foreignTableRows2, $foreignTable2);
			foreach($foreignTableRows as $foreignTableRow) {
				foreach($foreignTableRows2 as $foreignTableRow2) {
					$objects[] = array_merge($foreignTableRow2, $foreignTableRow, $tableRow);
				}
			}
		}
		return $objects;
	}
}
