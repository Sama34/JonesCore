<?php

class JB_Activate_Tasks extends JB_Activate_Base
{
	/**
	 * {@inheritdoc}
	 */
	static function activate($codename)
	{
		global $db;

		require JB_Packages::i()->getPath($codename)."install/tasks.php";

		if(!empty($tasks))
		{
			$names = array();

			foreach($tasks as $task)
			{
				if(!isset($task['file']))
				    $names[] = $codename;
				else
					$names[] = $task['file'];
			}

			$names = array_unique($names);

			$db->update_query("tasks", array("enabled" => "1"), "file IN ('".implode("','", $names)."')");
		}
	}

	/**
	 * {@inheritdoc}
	 */
	static function deactivate($codename)
	{
		global $db;

		require JB_Packages::i()->getPath($codename)."install/tasks.php";

		if(!empty($tasks))
		{
			$names = array();

			foreach($tasks as $task)
			{
				if(!isset($task['file']))
				    $names[] = $codename;
				else
					$names[] = $task['file'];
			}

			$names = array_unique($names);

			$db->update_query("tasks", array("enabled" => "0"), "file IN ('".implode("','", $names)."')");
		}
	}

	/**
	 * {@inheritdoc}
	 */
	static function isNeeded($codename)
	{
		return file_exists(JB_Packages::i()->getPath($codename)."install/tasks.php");
	}
}
