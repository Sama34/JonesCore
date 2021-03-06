<?php

class JB_Helpers
{
	/**
	 * @var postParser
	 */
	static private $parser = null;

	/**
	 * @var array
	 */
	static private $parser_options = array(
		"allow_html"		=> 0,
		"allow_mycode"		=> 1,
		"allow_smilies"		=> 1,
		"allow_imgcode"		=> 1,
		"allow_videocode"	=> 1,
		"filter_badwords"	=> 1
	);

	/**
	 * @var array
	 */
	static private $ugroupcache = array();

	/**
	 * Cache our parser class and the options
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	public static function parse($message)
	{
		if(static::$parser == null)
		{
			require_once MYBB_ROOT."inc/class_parser.php";
			static::$parser = new postParser;
		}

		return static::$parser->parse_message($message, static::$parser_options);
	}

	/**
	 * Shorten the line a bit
	 *
	 * @param int $date
	 *
	 * @return string
	 */
	public static function formatDate($date)
	{
		return my_date('relative', $date);
	}

	/**
	 * Helper to create a preview
	 *
	 * @param string $message
	 * @param int    $length
	 * @param string $append
	 * @param bool   $parse
	 *
	 * @return string
	 */
	public static function preview($message, $length = 100, $append = "...", $parse = true)
	{
		// Do we still need to parse our message?
		if($parse)
			$message = static::parse($message);

		// If it's short enough: return it
		if(strlen($message) <= $length)
			return $message;

		// Shorten the message and append what should be appended
		return my_substr($message, 0, $length-strlen($append)).$append;
	}

	/**
	 * Create a simple text with (avatar) Username
	 *
	 * @param array|int $user
	 * @param bool      $avatar
	 * @param bool      $formatName
	 *
	 * @return string
	 */
	public static function formatUser($user, $avatar = true, $formatName = false)
	{
		global $lang;

		if(!is_array($user))
			$user = get_user($user);

		$name = $user['username'];
		if(empty($name))
			$name = $lang->guest;
		if($formatName)
			$name = format_name($name, $user['usergroup'], $user['displaygroup']);

		if($avatar)
		{
			$favatar = format_avatar($user['avatar'], $user['avatardimensions'], "17x17");
			$name = "<img src=\"{$favatar['image']}\" {$favatar['width_height']} valign=\"middle\" /> {$name}";
		}

		return build_profile_link($name, $user['uid']);
	}

	/**
	 * @param int|string $string
	 *
	 * @return int|string
	 */
	public static function escapeOutput($string)
	{
		if(is_numeric($string))
			return (int)$string;
		return htmlspecialchars_uni($string);
	}

	/**
	 * @param int|string|array $string
	 *
	 * @return int|string
	 */
	public static function escapeDatabase($string)
	{
		global $db;

		if(is_numeric($string))
			return (int)$string;
		if(is_array($string))
			$string = my_serialize($string);
		return $db->escape_string($string);
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public static function escapeDatabaseArray(array $array)
	{
		foreach($array as &$el)
		{
			if(is_array($el))
				$el = static::escapeDatabaseArray($el);
			else
				$el = static::escapeDatabase($el);
		}

		return $array;
	}

	/**
	 * @param string $codename
	 * @param bool   $doCache
	 *
	 * @return bool|string
	 */
	public static function getVersion($codename, $doCache=true)
	{
		// First look whether the version is in our cache
		if($doCache === true)
		{
			global $cache;

			$jb = $cache->read("jb_plugins");
			if(isset($jb[$codename]))
				return $jb[$codename];
		}

		if(!file_exists(JB_PLUGINS."{$codename}.php"))
			return false;

		require_once JB_PLUGINS."{$codename}.php";

		$func = $codename."_info";
		if(!function_exists($func))
			return false;

		$info = $func();
		if(!isset($info['version']))
			return false;
		return $info['version'];
	}

	/**
	 * @param string $version
	 *
	 * @return string
	 */
	public static function createNiceVersion($version)
	{
		return str_replace(array(".", " ", "_"), "", $version);
	}

	/**
	 * @param string $version
	 *
	 * @return string
	 */
	public static function createFourDigitVersion($version)
	{
		$version = static::createNiceVersion($version);
		if(strlen($version) == 1)
			return $version."000";
		elseif(strlen($version) == 2)
			return $version."00";
		elseif(strlen($version) == 3)
			return $version."0";
		else
			return substr($version, 0, 4);
	}

	/**
	 * @param int $gid
	 *
	 * @return array
	 */
	public static function getUsersInGroup($gid)
	{
		if(isset(static::$ugroupcache[$gid]))
			return static::$ugroupcache[$gid];

		global $db;
		switch($db->type)
		{
			case "pgsql":
			case "sqlite":
				$query = $db->simple_select("users", "*", "','||additionalgroups||',' LIKE '%,{$gid},%' OR usergroup='{$gid}'", array('order_by' => 'username'));
				break;
			default:
				$query = $db->simple_select("users", "*", "CONCAT(',',additionalgroups,',') LIKE '%,{$gid},%' OR usergroup='{$gid}'", array('order_by' => 'username'));
		}

		$users = array();
		while($user = $db->fetch_array($query))
		{
			$users[] = $user;
		}
		static::$ugroupcache[$gid] = $users;
		return $users;
	}
}
