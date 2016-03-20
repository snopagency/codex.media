<?php

$backupDir = getcwd() . '/setup/backups/';
$updatesDir = getcwd() . '/setup/updates/';
$siteDir = getcwd() . '/setup/site/';
$rootDir = getcwd();

$CLIENT_SITE_DIR = 'setup/site/';
$CLIENT_UPDATES_DIR = 'setup/updates/';

$CURRENT_LISTING = array();
$UPDATED_LISTING = array();
$CURRENT_FILES = array();
$UPDATED_FILES = array();
$CURRENT_FILES_MD5 = array();
$UPDATED_FILES_MD5 = array();


function calculate_status($site_directory)
{
	$prefix_len = strlen($site_directory);
	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($site_directory, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST,
		RecursiveIteratorIterator::CATCH_GET_CHILD
	);

    $stats = array(
        'directories' => array(),
        'files' => array(),
        'md5' => array()
    );
	foreach ($iter as $path => $dir) {
		if ($dir->isDir()) {
            $stats['directories'][] = substr($path, $prefix_len);
		}
		else {
            $nfn = substr($path, $prefix_len);
            $stats['files'][] = $nfn;
            $stats['md5'][$nfn] = md5_file($path);
		}
	}

    return $stats;
}

function initial($root)
{
	global $CURRENT_LISTING, $CURRENT_FILES, $CURRENT_FILES_MD5;

    $stats = calculate_status($root);
	$CURRENT_LISTING = $stats['directories'];
	$CURRENT_FILES = $stats['files'];
    $CURRENT_FILES_MD5 = $stats['md5'];
}

function update($root)
{
    global $UPDATED_LISTING, $UPDATED_FILES, $UPDATED_FILES_MD5;

    $stats = calculate_status($root);
    $UPDATED_LISTING = $stats['directories'];
    $UPDATED_FILES = $stats['files'];
    $UPDATED_FILES_MD5 = $stats['md5'];
}

# remove old dirs whose are not in new
function remove_site_directories()
{
	global $CURRENT_LISTING, $UPDATED_LISTING, $CLIENT_SITE_DIR;
	$commands = array();

	$for_delete = array();
	foreach ($CURRENT_LISTING as $dir)
	{
		if (!in_array($dir, $UPDATED_LISTING))
		{
			$for_delete[] = $dir;
		}
	}

	foreach (array_reverse($for_delete) as $dir)
	{
		$commands[] = "rm -rf ".escapeshellarg($CLIENT_SITE_DIR . $dir);
	}
	return $commands;
}

# create new dirs whose are not in old
function create_new_directories()
{
	global $CURRENT_LISTING, $UPDATED_LISTING, $CLIENT_SITE_DIR;
	$commands = array();

	$for_add = array();
	foreach ($UPDATED_LISTING as $dir)
	{
		if (!in_array($dir, $CURRENT_LISTING))
		{
			$for_add[] = $dir;
		}
	}

	foreach ($for_add as $dir)
	{
		$commands[] = "mkdir " . escapeshellarg($CLIENT_SITE_DIR . $dir);
	}
	return $commands;
}

# remove old files whose are not in new
function remove_old_files()
{
	global $CURRENT_FILES, $UPDATED_FILES, $CLIENT_SITE_DIR;
	$commands = array();
	
	$for_del = array();
	foreach ($CURRENT_FILES as $file)
	{
		if (!in_array($file, $UPDATED_FILES))
		{
			$for_del[] = $file;
		}
	}

	foreach ($for_del as $file)
	{
		$commands[] = "rm " . escapeshellarg($CLIENT_SITE_DIR . $file);
	}
	return $commands;
}

# create\update new files whose are not\modified from old
function create_new_files()
{
    global $CURRENT_FILES, $UPDATED_FILES, $CURRENT_FILES_MD5, $UPDATED_FILES_MD5, $CLIENT_SITE_DIR,
           $CLIENT_UPDATES_DIR, $backupDir, $updatesDir;

    $commands = array();

    foreach ($UPDATED_FILES as $file)
    {
        if (!in_array($file, $CURRENT_FILES))
        {
            $commands[] = "cp ./" . $CLIENT_UPDATES_DIR . $UPDATED_FILES_MD5[$file] . " " . escapeshellarg($CLIENT_SITE_DIR . $file);
            copy($updatesDir . $file, $backupDir . $UPDATED_FILES_MD5[$file]);
        }
        else
        {
            if ($CURRENT_FILES_MD5[$file] != $UPDATED_FILES_MD5[$file])
            {
                $commands[] = "cp ./" . $CLIENT_UPDATES_DIR . $UPDATED_FILES_MD5[$file] . " " . escapeshellarg($CLIENT_SITE_DIR . $file);
                copy($updatesDir . $file, $backupDir . $UPDATED_FILES_MD5[$file]);
            }
        }
    }

    return $commands;
}

function tarBuilds()
{
    system("tar zvcf setup/archive.tar.gz setup/backups/*");
}

initial($siteDir);
update($updatesDir);

$commands = array();
$commands = array_merge($commands, remove_site_directories());
$commands = array_merge($commands, create_new_directories());
$commands = array_merge($commands, remove_old_files());
$commands = array_merge($commands, create_new_files());

$file = fopen("setup/commands.sh", "w");
fwrite($file, implode("\n", $commands));
fclose($file);
var_dump($commands);

tarBuilds();

?>
