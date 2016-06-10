<?php

abstract class AbstractStaticList {

	public static function get($id = null) {
		if (empty(static::$list)) {
			static::reset();
		}
		$return = null;
		if (empty($id)) {
			$return = static::$list;
		} else if (!empty($id) && isset(static::$list[$id])) {
			$return = static::$list[$id];
		}
		return $return;
	}

	public static function set($id, $element) {
		if (empty(static::$list)) {
			static::reset();
		}
		$listTemp = static::$list;
		$listTemp[$id] = $element;
		static::$list = $listTemp;
	}

	public static function remove($id) {
		$element = array();
		if (!empty(static::$list) && isset(static::$list[$id])) {
			$element = static::$list[$id];
			unset(static::$list[$id]);
		}
		return $element;
	}

	public static function add($element) {
		if (empty(static::$list)) {
			static::reset();
		}
		array_push(static::$list, $element);
	}

	public static function reset() {
		static::$list = array();
	}

    public static function size(){
        return count(static::$list);
    }
}
