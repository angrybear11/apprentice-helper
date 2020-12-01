<?php

namespace Voronoi\Apprentice\Artisan\Concerns;

trait DecodesLaravelCommandOutput
{
	
	function decodeFormat($message)
	{
		// Check for <format>*</format> formatted messages
		// Extract format
		$formats = implode("|", ['info', 'comment', 'question', 'error', 'warning', 'note', 'caution', 'ok', 'success']);
		$startTagRegex = "/(?<=^<)($formats)*(?=\>)/i";
		if (!preg_match($startTagRegex, $message, $match) || count($match) == 0) {
			return ["text", $message];
		}
		$format = $match[0];

		// Extract body
		$bodyRegex = "/(?<=<($format)>).*(?=<\/($format)>)/is";
		if (!preg_match($bodyRegex, $message, $bodyMatches) || count($bodyMatches) == 0) {
			return ["text", $message];
		}
		$body = $bodyMatches[0];

		return [$format, $body];
	}
}