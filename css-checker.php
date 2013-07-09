#!/usr/bin/php
<?php

if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

This is a command line PHP script with one option.

  Usage:
  <?php echo $argv[0]; ?> url

<?php
} else {
	require_once dirname('.') . '/functions.php';

	$baseUrl = $argv[1];

	print "Downloading {$baseUrl}... ";

	$document = file_get_contents($baseUrl);

	print "[OK]\n";

	$foundStylesheetFiles = findCssFilesInDocument($document);

	if (($count = count($foundStylesheetFiles)) > 0)
	{
		print "  Found stylesheets: {$count}\n";

		$stylesheetFiles = array();

		foreach ($foundStylesheetFiles as $file)
		{
			print "    {$file}\n";
			$stylesheetFiles[] = array('url' => rtrim($baseUrl, '/') . $file);
		}

		print "\n";

		$rules = array();

		foreach ($stylesheetFiles as &$file)
		{
			print "Downloading {$file['url']}... \n";

			$sheet = file_get_contents($file['url']);

			$rules = array_merge($rules, findCssRulesInStylesheet($sheet));
		}

		$rules = array_unique($rules);

		print "Total CSS rules to check: " . count($rules) . "\n\n";

		if (count($rules) === 0)
		{
			exit;
		}

		print "Checking rules...\n";

		$foundRules = findCssRulesInDocument($document, $rules);

		$foundRulesTotal = count($rules);
		$foundRulesCount = count($foundRules);
		$noFoundRulesCount = $foundRulesTotal - $foundRulesCount;

		print "  Found: " . $foundRulesCount . " (" . (round(($foundRulesCount/$foundRulesTotal)*100, 2)) . "%)\n";
		print "  No used: " . $noFoundRulesCount . " (" . (round(($noFoundRulesCount/$foundRulesTotal)*100, 2)) . "%)\n";
	} else
	{
		print "\nNo found linked stylesheets.\n";
	}
}
