<?php

// This file is only supposed to do some general checks (eg Core installed)
if(!file_exists(MYBB_ROOT."inc/plugins/jones/core/Core.php"))
    define("JB_CORE_INSTALLED", false);
else
{
	define("JB_CORE_INSTALLED", true);
	// Require the core and get the instance once - mainly to setup the auto loader in that class
	require_once MYBB_ROOT."inc/plugins/jones/core/Core.php";
	JB_Core::i();
}

// Called on installation when the core isn't set up
function jb_install_core()
{
	// We don't want to have any problems guys
	if(JB_CORE_INSTALLED === true)
	    return;

	$auto = jb_download_core();

	// Still nothing here? Poke the user!
	if($auto === false)
	{
		global $page;

		$page->output_header("Jones Core not installed");

		$table = new Table;
		$table->construct_header("Attention");
		$table->construct_cell("Jones Core classes are missing. Please load them from <a href=\"https://github.com/JN-Jones/JonesCore\">GitHub</a> and follow the instractions in the ReadMe. Afterwards you can reload this page.");
		$table->construct_row();
		$table->output("Jones Core not installed");

		$page->output_footer();
		exit;	
	}
}

function jb_update_core()
{
	$auto = jb_download_core();

	if($auto === false)
	{
		global $page;

		$page->output_header("Auto Update failed");

		$table = new Table;
		$table->construct_header("Attention");
		$table->construct_cell("Not able to auto update the core. Please load it from <a href=\"https://github.com/JN-Jones/JonesCore\">GitHub</a> and follow the instractions in the ReadMe.");
		$table->construct_row();
		$table->output("Auto Update failed");

		$page->output_footer();
		exit;
	}
}

function jb_download_core()
{
	// No need to try anything if we can't unzip the file at the end
	if(!class_exists("ZipArchive"))
		return false;

	$content = fetch_remote_file("https://codeload.github.com/JN-Jones/JonesCore/zip/master");

	// Wasn't able to get the zip from github
	if($content === false || empty($content))
	    return false;

	// Now save the zip!
	$file = @fopen(MYBB_ROOT."inc/plugins/jones/core/temp.zip", "w");

	// Wasn't able to create the file
	if($file === false)
		return false;

	@fwrite($file, $content);
	@fclose($file);

	// We got the file - now unzip it
	$zip = new ZipArchive();
	$zip->open(MYBB_ROOT."inc/plugins/jones/core/temp.zip");
	$success = $zip->extractTo(MYBB_ROOT."inc/plugins/jones/core/temp/");
	$zip->close();

	// Something went wrong
	if($success === false)
	    return false;

	// Now move the core recursive and then delete everything
	jb_move_recursive(MYBB_ROOT."inc/plugins/jones/core/temp/JonesCore-master/");
	jb_remove_recursive(MYBB_ROOT."inc/plugins/jones/core/temp/");
	@unlink(MYBB_ROOT."inc/plugins/jones/core/temp.zip");

	return true;
}

function jb_move_recursive($direction)
{
	global $mybb;
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die("Something went wrong!");
	$dir = opendir($direction);
	while(($new = readdir($dir)) !== false) {
		if($new == "." || $new == "..")
		    continue;

		if(is_file($direction.$new)) {
			if(substr($new, 0, 4) == ".git" || strtolower(substr($new, 0, 6)) == "readme") {
				unlink($direction.$new);
			    continue;
			}
			$old_dir = $direction.$new;
			$t = str_replace(MYBB_ROOT, "", $old_dir);
			$t2 = strpos($t, "/");
			$t2 = strpos($t, "/", $t2+1);
			$t2 = strpos($t, "/", $t2+1);
			$t2 = strpos($t, "/", $t2+1);
			$t2 = strpos($t, "/", $t2+1);
			$t2 = strpos($t, "/", $t2+1);
			$start = strlen(MYBB_ROOT)+$t2;
			$relative = substr($old_dir, $start+1);
			if(substr($relative, 0, 6) == "admin/")
			    $relative = $mybb->config['admin_dir']."/".substr($relative, 6);

			$new_dir = MYBB_ROOT.$relative;
			$cdir = substr($new_dir, 0, strrpos($new_dir, "/"));

			if(!is_dir($cdir))
			    mkdir($cdir, 0777, true);

			rename($old_dir, $new_dir);
		} elseif(is_dir($direction.$new)) {
			jb_move_recursive($direction.$new);
		}
	}
	closedir($dir);
}

function jb_remove_recursive($direction)
{
	if(substr($direction, -1, 1) != "/")
	    $direction .= "/";
	if(!is_dir($direction))
	    die("Something went wrong");
	$dir = opendir($direction);
	while(($new = readdir($dir)) !== false) {
		if($new == "." || $new == "..")
		    continue;

		if(is_file($direction.$new)) {
			unlink($direction.$new);
		} elseif(is_dir($direction.$new)) {
			jb_remove_recursive($direction.$new);
		}
	}
	closedir($dir);
	rmdir($direction);
}