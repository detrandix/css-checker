#!/usr/bin/php
<?php

$options = getopt("u:p:i:h", array(
	"pages-file:",
	"ignore-file:"
));

if (!array_key_exists('u', $options) || array_key_exists('h', $options))
{
	print <<<DOC
Usage: {$argv[0]} -u <url to check> [options]

  -p <page>            Add page to check (can be used multiple times)
  -i <term>            Ignore CSS rules with <term> within
  --pages-file <path>  Load pages from file separated by new line
  --ignore-file <path> Load ignore CSS rules separated by new line

DOC;

	exit(0);
}

require_once dirname('.') . '/functions.php';

$baseUrl = $options['u'];

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

	$ignore = array(':');
	if (array_key_exists('i', $options))
		$ignore = array_unique(array_merge($ignore, (array) $options['i']));
	if (array_key_exists('ignore-file', $options))
	{
		foreach ((array) $options['ignore-file'] as $path)
		{
			$lines = parseLineFile(file_get_contents($path));

			if (count($lines))
				$ignore = array_unique(array_merge($ignore, $lines));
		}
	}

	foreach ($stylesheetFiles as &$file)
		$rules = array_merge($rules, findCssRulesInStylesheet(file_get_contents($file['url']), $ignore));

	$rules = array_unique($rules);

	print "Total CSS rules to check: " . count($rules) . "\n\n";

	if (count($rules) === 0)
	{
		exit;
	}

	print "Checking rules...\n\n";

	$totalFoundRules = array();
	$rulesTotalCount = count($rules);

	$pages = array($baseUrl);
	if (array_key_exists('p', $options))
		$pages = array_unique(array_merge($pages, (array) $options['p']));
	if (array_key_exists('pages-file', $options))
	{
		foreach ((array) $options['pages-file'] as $path)
		{
			$lines = parseLineFile(file_get_contents($path));

			if (count($lines))
				$pages = array_unique(array_merge($pages, $lines));
		}
	}

	foreach ($pages as $page)
	{
		$url = rtrim($baseUrl, '/') . '/' . str_replace($baseUrl, '', $page) . '/';
		$url = rtrim($url, '/') . '/';

		print "$url\n";

		$foundRules = findCssRulesInDocument(file_get_contents($url), $rules);


		$foundRulesCount = count($foundRules);
		$noFoundRulesCount = $rulesTotalCount - $foundRulesCount;

		print "  Found: " . $foundRulesCount . " (" . (round(($foundRulesCount/$rulesTotalCount)*100, 2)) . "%)\n";
		print "  No used: " . $noFoundRulesCount . " (" . (round(($noFoundRulesCount/$rulesTotalCount)*100, 2)) . "%)\n";

		$totalFoundRules = array_unique(array_merge($totalFoundRules, $foundRules));
	}

	print "\n-----------------\n\n";

	$totalFoundRulesCount = count($totalFoundRules);
	$totalNoFoundRulesCount = $rulesTotalCount - $totalFoundRulesCount;

	print "Found: " . $totalFoundRulesCount . " (" . (round(($totalFoundRulesCount/$rulesTotalCount)*100, 2)) . "%)\n";
	print "No used: " . $totalNoFoundRulesCount . " (" . (round(($totalNoFoundRulesCount/$rulesTotalCount)*100, 2)) . "%)\n";

	if ($totalNoFoundRulesCount > 0)
	{
		$handle = fopen('no-found-css-rules.txt', 'w');

		$totalNoFoundRules = array_diff($rules, $totalFoundRules);

		foreach ($totalNoFoundRules as $rule)
		{
			fwrite($handle, $rule . "\n");
		}

		fclose($handle);

		print "\nRules saved in no-found-css-rules.txt\n";
	}
} else
{
	print "\nNo found linked stylesheets.\n";
}
