#!/usr/bin/php
<?php

if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

This is a command line PHP script with one option.

  Usage:
  <?php echo $argv[0]; ?> url

<?php
} else {
	require_once dirname('.') . '/DomQuery.php';

	$baseUrl = $argv[1];

	print "Downloading {$baseUrl}... ";

	$document = file_get_contents($baseUrl);

	print "[OK]\n";

	if (($count = preg_match_all('~<link.+?href=[\'"](.+\.css.*)[\'"].*?>~', $document, $matched)))
	{
		print "  Found stylesheets: {$count}\n";

		$stylesheetFiles = array();

		foreach ($matched[1] as $match)
		{
			print "    {$match}\n";
			$stylesheetFiles[] = array('url' => rtrim($baseUrl, '/') . $match);
		}

		print "\n";

		$rules = array();

		foreach ($stylesheetFiles as &$file)
		{
			print "Downloading {$file['url']}... ";

			$sheet = file_get_contents($file['url']);

			print "[OK]\n";

			$file['document'] = $sheet;
			$file['rules'] = array();

			if (($countRules = preg_match_all('~(?:^|})(.+?){~m', $sheet, $rulesMatched)))
			{
				print "  Found CSS rules: {$countRules}\n";

				foreach ($rulesMatched[1] as $ruleMatched)
					foreach (explode(',', $ruleMatched) as $rule)
						if (strpos($rule, ':') === FALSE)
						{
							$file['rules'][] = trim($rule);
							$rules[] = trim($rule);
						}

				print "  Found CSS rules without pseudo classes: " . count($file['rules']) . "\n";
			} else
				print "  No CSS rules in this file";

			print "\n";
		}

		$rules = array_unique($rules);

		print "Total CSS rules to check: " . count($rules) . "\n\n";

		if (count($rules) === 0)
		{
			exit;
		}

		print "Checking rules...\n";

		libxml_use_internal_errors(true); // solution for HMTL 5 tags
		$domQueryDocument = Tester\DomQuery::fromHtml($document);
		libxml_clear_errors();

		$foundRules = array();
		$noFoundRules = array();

		foreach ($rules as $rule)
		{
			try
			{
				if ($domQueryDocument->has($rule))
					$foundRules[] = $rule;
				else
					$noFoundRules[] = $rule;
			} catch (\Exception $e)
			{
				$noFoundRules[] = $rule;
			}
		}

		print "  Found: " . count($foundRules) . " (" . (round((count($foundRules)/count($rules))*100, 2)) . "%)\n";
		print "  No found: " . count($noFoundRules) . " (" . (round((count($noFoundRules)/count($rules))*100, 2)) . "%)\n";
	} else
	{
		print "\nNo found linked stylesheets.\n";
	}
}
