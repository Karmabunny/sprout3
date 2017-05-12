<?php
if (empty($argv[1])) {
    echo "Usage:\n";
    echo "    php create_tarball.php [VERSION]\n";
    exit(1);
} else if (!preg_match('!^3\.[0-9]+\.[0-9]+$!', $argv[1])) {
    die("Version must be in the format 3.X.Y\n");
}

// Prepare the directory name.  Because the version number has already
// been validated, it's known that the directory name is safe to be used
// directly in shell_exec statements
$version = $argv[1];
$directory = 'sprout-' . $version;

// Complain if directory already exists
if (file_exists($directory) and is_dir($directory)) {
    die("Directory '{$directory}' already exists; exiting\n");
}

echo 'Preparing files...';
flush();

mkdir($directory);

// Set current working dir to be the repository root
chdir(__DIR__ . '/..');

// Recursive copy directories
shell_exec("cp -R deploy {$directory}");
shell_exec("cp -R documentation {$directory}");
shell_exec("cp -R src {$directory}");
shell_exec("cp -R tools {$directory}");

// Copy various top-level files
copy('INSTALL.md', $directory . '/INSTALL.md');
copy('README.md', $directory . '/README.md');
copy('LICENSE', $directory . '/LICENSE');
copy('nginx_sprout.conf', $directory . '/nginx_sprout.conf');
copy('phpunit.xml.dist', $directory . '/phpunit.xml.dist');
copy('.nstrack.php', $directory . '/.nstrack.php');

// Nuke the directories containing dynamic content
shell_exec("rm -rf {$directory}/src/sprout/cache");
shell_exec("rm -rf {$directory}/src/sprout/temp");
shell_exec("rm -rf {$directory}/src/files");

// Recreate these dirs
mkdir($directory . '/src/sprout/cache');
mkdir($directory . '/src/sprout/temp');
mkdir($directory . '/src/files');

// And copy the "go away" message into these new dirs
copy('src/sprout/cache/index.htm', $directory . '/src/sprout/cache/index.htm');
copy('src/sprout/temp/index.htm', $directory . '/src/sprout/temp/index.htm');
copy('src/files/index.htm', $directory . '/src/files/index.htm');

// Also files/.htaccess which has some caching rules
copy('src/files/.htaccess', $directory . '/src/files/.htaccess');

// Nuke this script; it probably won't work without a whole repo
unlink($directory . '/tools/create_tarball.php');

// Git is configured to ignore some top-level config
// but derived repos may still want to track these files
unlink($directory . '/src/config/.gitignore');

// This config may exist locally (non-committed) and should be removed
unlink($directory . '/src/config/database.php');
unlink($directory . '/src/config/dev_hosts.php');

// Cron job script to be executable
chmod($directory . '/src/cron_daily.sh', 0755);

// Generate a Sprout version config file and inject into tarball directory
$version_conf = "<?php\n\$config['version'] = '{$version}';\n";
file_put_contents($directory . '/src/sprout/version.php', $version_conf);

// Uncomment lines which have a leading comment (welcome module def)
$module_config = file_get_contents($directory . '/src/config/config.php');
$module_config = preg_replace('!^//(.+)!m', '$1', $module_config);
file_put_contents($directory . '/src/config/config.php', $module_config);

echo "Done.\nCreating tarball...";
flush();

shell_exec("tar -cjf {$directory}.tar.bz2 {$directory}");

echo "Done.\n";
