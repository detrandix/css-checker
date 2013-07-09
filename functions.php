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
 * @param  string  $stylesheet
 * @param  bool    $excludePseudoClasses
 *
 * @return string[]
 */
function findCssRulesInStylesheet($stylesheet, $excludePseudoClasses = TRUE)
{
	if (!preg_match_all('~(?:^(?!@)|})([^{}]+?){~m', $stylesheet, $matched))
		return array();

	$return = array();

	foreach ($matched[1] as $match)
		if (!($excludePseudoClasses && strpos($match, ':') !== FALSE))
			$return[] = trim($match);

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
