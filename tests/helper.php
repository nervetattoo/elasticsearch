<?php // vim:set ts=4 sw=4 et:
/**
 * This file is part of the ElasticSearch PHP client
 *
 * (c) Raymond Julin <raymond.julin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$path = split("/",pathinfo(__FILE__, PATHINFO_DIRNAME));
array_pop($path);
$path = join("/", $path) . "/";

require_once $path . "ElasticSearchClient.php";
require_once $path . "tests/ElasticSearchParent.php";
