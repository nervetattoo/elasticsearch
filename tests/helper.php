<?php // vim:set ts=4 sw=4 et:

$path = split("/",pathinfo(__FILE__, PATHINFO_DIRNAME));
array_pop($path);
$path = join("/", $path) . "/";

require_once $path . "ElasticSearchClient.php";
require_once $path . "tests/ElasticSearchParent.php";
