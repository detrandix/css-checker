#!/usr/bin/php
<?php

if ($argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

  Usage:
  <?php echo $argv[0]; ?> baseUrl [somePage1, somepage2, ...]

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
			$rules = array_merge($rules, findCssRulesInStylesheet(file_get_contents($file['url'])));

		$rules = array_unique($rules);

		print "Total CSS rules to check: " . count($rules) . "\n\n";

		if (count($rules) === 0)
		{
			exit;
		}

		print "Checking rules...\n\n";

		$totalFoundRules = array();
		$rulesTotalCount = count($rules);

		for ($i = 1, $filesCount = count($argv); $i < $filesCount; $i++)
		{
			$url = rtrim($baseUrl, '/') . '/' . str_replace($baseUrl, '', $argv[$i]) . '/';
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
}
