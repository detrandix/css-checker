<?php

require_once dirname('.') . '/DomQuery.php';

/**
 * @param  string $document
 *
 * @return string[] Founded CSS files
 */
function findCssFilesInDocument($document)
{
	preg_match_all('~<link.+?href=[\'"](.+\.css.*)[\'"].*?>~', $document, $matched);

	if (count($matched))
		return $matched[1];
	else
		return array();
}

/**
 * @param  string   $stylesheet
 * @param  string[] $ignore
 *
 * @return string[]
 */
function findCssRulesInStylesheet($stylesheet, $ignore = array())
{
	if (!preg_match_all('~(?:^(?!@)|})([^{}]+?){~m', $stylesheet, $matched))
		return array();

	$return = array();

	foreach ($matched[1] as $match)
	{
		$ignoreMatch = FALSE;
		foreach ($ignore as $ignoreRule)
			if (strpos($match, $ignoreRule) !== FALSE)
			{
				$ignoreMatch = TRUE;
				break;
			}

		if (!$ignoreMatch)
			$return[] = trim($match);
	}

	return $return;
}

/**
 * @param  string   $document
 * @param  string[] $rules
 *
 * @return string[] Founded rules
 */
function findCssRulesInDocument($document, $rules)
{
	libxml_use_internal_errors(true); // solution for HMTL 5 tags
	$domQueryDocument = Tester\DomQuery::fromHtml($document);
	libxml_clear_errors();

	$foundRules = array();

	foreach ($rules as $rule)
	{
		try
		{
			if ($domQueryDocument->has($rule))
				$foundRules[] = $rule;
		} catch (\Exception $e)
		{
		}
	}

	return $foundRules;
}

function parseLineFile($file)
{
	$lines = preg_split('/\r|\n/', $file);

	$return = array();

	foreach ($lines as $line)
	{
		$trimLine = trim($line);

		if (strlen($trimLine))
			$return[] = $trimLine;
	}

	return $return;
}
