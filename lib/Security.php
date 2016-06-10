<?php
/**
 * UNDER CONSTRUCTION!!!
 */
use model\User;
use model\Cookie;

/*
 * This class provides functions for a secure application.
 * This class contains methods for hashing the password, generating tokens and login stuff.
 * With a database connection it performs checks against the database to maintain a secure request.
 */
class Security {

	const GENERATE_TOKEN_LENGTH = 24;
	const GENERATE_PASSWORD_HASH_LOOPS = 1024;
	const GENERATE_PASSWORD_HASH_ALGO = 'sha512';
	const GENERATE_PASSWORD_HASH_RAW_OUTPUT = true;

	public static function generateToken() {
		return base64_encode(openssl_random_pseudo_bytes(self::GENERATE_TOKEN_LENGTH));
	}

	public static function generateTokenWithoutSlash() {
		return str_replace('/', '-', base64_encode(openssl_random_pseudo_bytes(self::GENERATE_TOKEN_LENGTH)));
	}

	public static function generatePasswordHash($password, $salt) {
		$passwordHash = $password.$salt;
		for ($i = 0; $i < self::GENERATE_PASSWORD_HASH_LOOPS; $i++) {
			$passwordHash = hash(self::GENERATE_PASSWORD_HASH_ALGO, $passwordHash, self::GENERATE_PASSWORD_HASH_RAW_OUTPUT);
		}
		return base64_encode($passwordHash);
	}

	public static function verifyPassword(model\User $user, $password, $db) {
		return self::generatePasswordHash($password, $user->getSalt()) === $db;
	}

	public static function login(model\User $user, array $data = array()) {
		// echo __METHOD__.'<br />';
		// do sometimes clear the expired cookies
		if (time() % 1337 <= 42) {
			self::clearExpiredCookies();
		}
		$user = self::refreshUser($user);
		// if stay logged in -> set cookie
		if (isset($data['persistent'])) {
			static::setCookie($user, self::refreshCookie($user, true));
		// else set session
		} else {
			static::setSession($user);
		}
	}

	public static function getLoggedInUser() {
		// echo __METHOD__.'<br />';
		$user = null;
		$array = null;
		if (self::verifySession()) {
			$array = $_SESSION;
		} else if (self::verifyCookie()) {
			$array = $_COOKIE;
		}
		if (!empty($array)) {
			$user = new User(current(Database::resultToArray(Database::getDb('user')->where('username', $array['username']))));
		}
		return $user;
	}

	public static function isLoggedIn() {
		// echo __METHOD__.'<br />';
		return !empty(self::getLoggedInUser());
	}

	public static function logout() {
		// echo __METHOD__.'<br />';
		if (self::isLoggedIn()) {
			self::kill();
		}
	}

	public static function kill() {
		self::killCookie();
		self::killSession();
	}

	public static function killCookie() {
		// echo __METHOD__.'<br />';
		if (isset($_COOKIE)) {
			$expire = new \DateTime('now -'.Config::get('app.security.cookie.expire'));
			if (isset($_COOKIE['username'])) {
				setcookie('username', null, $expire->getTimestamp(), '/');
				unset($_COOKIE['username']);
			}
			if (isset($_COOKIE['series'])) {
				setcookie('series', null, $expire->getTimestamp(), '/');
				unset($_COOKIE['series']);
			}
			if (isset($_COOKIE['token'])) {
				setcookie('token', null, $expire->getTimestamp(), '/');
				unset($_COOKIE['token']);
			}
		}
	}

	public static function killSession() {
		session_destroy();
	}

	public static function doesCookieExist() {
		// echo __METHOD__.'<br />';
		return isset($_COOKIE['username'])
			&& isset($_COOKIE['series'])
			&& isset($_COOKIE['token']);
	}

	public static function doesSessionExist() {
		// echo __METHOD__.'<br />';
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		return isset($_SESSION['username'])
			&& isset($_SESSION['series'])
			&& isset($_SESSION['token'])
			&& session_status() === PHP_SESSION_ACTIVE;
	}

	public static function setCookie(model\User $user, model\Cookie $cookie) {
		// echo __METHOD__.'<br />';
		$expire = new \DateTime('now +'.Config::get('app.security.cookie.expire'));
		setcookie('username', $user->getUsername(), $expire->getTimestamp(), '/');
		setcookie('series', $cookie->getSeries(), $expire->getTimestamp(), '/');
		setcookie('token', $cookie->getToken(), $expire->getTimestamp(), '/');
		$_COOKIE['username'] = $user->getUsername();
		$_COOKIE['series'] = $cookie->getSeries();
		$_COOKIE['token'] = $cookie->getToken();
	}

	public static function setSession(model\User $user) {
		// echo __METHOD__.'<br />';
		if (!self::doesSessionExist()) {
			session_start();
		}
		session_regenerate_id(true);
		$_SESSION['username'] = $user->getUsername();
		$_SESSION['series'] = $user->getSeries();
		$_SESSION['token'] = $user->getToken();
	}

	public static function update(model\User $user = null) {
		// echo __METHOD__.'<br />';
		$updateCookie = false;
		$updateSession = false;
		if (empty($user)) {
			$user = self::getLoggedInUser();
		}
		if (!empty($user)) {
			if (self::doesSessionExist() && self::verifySession()) {
				$updateSession = true;
			} else if (self::doesCookieExist() && self::verifyCookie()) {
				$updateCookie = true;
			}
			$user = self::refreshUser($user);
			if ($updateCookie) {
				$cookie = self::refreshCookie($user);
				self::setCookie($user, $cookie);
			}
			if ($updateSession) {
				self::setSession($user);
			}
		}
		return $user;
	}

	public static function verifyCookie() {
		// echo __METHOD__.'<br />';
		return self::verify($_COOKIE);
	}

	public static function verifySession() {
		// echo __METHOD__.'<br />';
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
		return self::verify($_SESSION);
	}

	public static function refreshUser(model\User $user) {
		// echo __METHOD__.'<br />';
		$user->setLoginattempts(0)
			->setLastlogin((new \DateTime('now'))->format(Config::get('app.date.format.long')));
		if (!empty($id = $user->getId())) {
			// persist updated user to db
			$tableUser = Database::getDb('user');
			$tableUser[$id]->update(array(
					'loginattempts' => $user->getLoginattempts(),
					'lastlogin' => $user->getLastlogin()
				)
			);
		}
		return $user;
	}

	public static function refreshCookie(model\User $user, $refreshSeries = false) {
		// echo __METHOD__.'<br />';
		$cookies = array();
		$tableCookie = Database::getDb('cookie');
		if (isset($_COOKIE['token'])) {
			$cookies = $tableCookie->where('user_id = ?', $user->getId())->where('token', $_COOKIE['token']);
		}
		// no cookies :(
		if (count($cookies) < 1) {
			$cookie = new model\Cookie(array(
				'user_id' => $user->getId(),
				'token' => static::generateToken(),
				'series' => static::generateToken()
			));
			$tableCookie->insert($cookie->toArray());
		// cookies! :D
		} else if (count($cookies) > 0) {
			$cookie = new model\Cookie(current(Database::resultToArray($cookies)));
			$cookie->setToken(static::generateToken());
			if ($refreshSeries) {
				$cookie->setSeries(static::generateToken());
			}
			if (!empty($id = $cookie->getId())) {
				$tableCookie[$id]->update(array(
					'token' => $cookie->getToken(),
					'series' => $cookie->getSeries()
				));
			}
		}
		return $cookie;
	}

	private static function verify(array $global) {
		// echo __METHOD__.'<br />';
		$valid = false;
		$tableUser = Database::getDb('user');
		$tableCookie = Database::getDb('cookie');
		if (isset($global['username'])) {
			$result = Database::resultToArray($tableUser->where('username', $global['username']));
			// username
			if (!empty($result)) {
				$user = new User(current($result));
				$result = Database::resultToArray($tableCookie->where('user_id = ?', $user->getId())->where('series = ?', $global['series']));
				if (!empty($result)) {
					$cookie = new Cookie(current($result));
// echo 'TOKEN<br />';
// echo 'global: '.$global['token'].'<br />';
// echo 'cookie: '.$cookie->getToken().'<br />';
// echo 'SERIES<br />';
// echo 'global: '.$global['series'].'<br />';
// echo 'cookie: '.$cookie->getSeries().'<br />';
					// series
					if ($global['series'] === $cookie->getSeries()) {
						// series
						if ($global['token'] === $cookie->getToken()) {
							$valid = true;
						} else {
							//TODO Gabriel Hug 2016-05-26: uncomment
							//echo 'token does not match!!!<br />';
							//self::kill();
						}
					} else {
						// do nothing, but don't be logged in either
					}
				} else {
					// cookie not found
				}
			} else {
				// echo 'security: username does not match!!!<br />';
			}
		}
		return $valid;
	}

	public static function clearExpiredCookies() {
		$expire = new \DateTime('now -'.Config::get('app.security.cookie.expire'));
		Database::getDb('cookie')->where('timestamp < ?', $expire)->delete();
	}

	public static function clearCookies(\model\User $user) {
		Database::getDb('cookie')->where('user_id = ?', $user->getId())->delete();
	}

}
