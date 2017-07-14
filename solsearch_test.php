<?php

/**
 * @file
 *
 * This script is supposed to be run from anywhere but within the solsearch
 * directory it is published in.
 */
?>

<form method = "get">
  Put the url to test: http://<input type="textfield" name="url"  value = "<?php print @$_GET['url']; ?>"/><br />
  The user 1 apikey: <input type="textfield" name="apikey" value = "<?php print @$_GET['apikey']; ?>"/><br />
  Type: <input type="textfield" name="type" value = "<?php print @$_GET['type']?: 'offer'; ?>"/><br />
  <input type="submit" />
</form>

<?php

set_exception_handler('solsearch_test_exception_handler');
//start by creating a client, as user 1
global $apikey;
$apikey = solsearch_rest_request('POST', 'groups', [], '{"name": "test group", "url": "testgroup.org"}')->apikey;

if (empty($apikey)) {
  throw new \Exception('Failed to create a test group.');
}
else print "New group registered with apikey '$apikey'<br />";

//Now add a listing.
$result = solsearch_rest_request('PUT', 'ads', [], getTestItem());

// And see if we can get it
$params = ['type' => 'offer', 'fragment' => 'Mind adventures'];
$result = solsearch_rest_request('GET', 'ads', $params);
if (!$result->total) {
  throw new \Exception('Failed either to add or retrieve the test ad:'.print_r($result, 1));
}
if ($result->items[0]->id != 99) {
  print_r($result->items);
  throw new \Exception("Retrieved bad results");
}
else print "saved and retrieved a single item<br />";

//see if we can update that item
$updated = str_replace('blah.jpg', 'foo.jpg', getTestItem());
solsearch_rest_request('PUT', 'ads', [], $updated);
$result = solsearch_rest_request('GET', 'ads', $params);
if ($result->items[0]->image != 'foo.jpg') {
  throw new \Exception("Failed to retrieve modified item");
}
else print "Modified item successfuly retrieved<br />";

//Delete that item
solsearch_rest_request('DELETE', 'ads/99', []);
$result = solsearch_rest_request('GET', 'ads', $params);
if ($result->total) {
  throw new \Exception("Failed to delete the example item. (check for relics)");
}
else print "Deleted item<br />";

//Bulk post items
$items = '['.getTestItem() .','.str_replace('99', '999', $updated).']';
solsearch_rest_request('PUT', 'bulk', [], $items);
$result = solsearch_rest_request('GET', 'ads', $params);
if ($result->total != 2) {
  throw new \Exception("Failed to bulk-add 2 items: $result->total retreived.");
}
else print "Bulk added 2 items<br />";

//Bulk delete items
solsearch_rest_request('DELETE', 'bulk', [], json_encode([99, 999]));
$result = solsearch_rest_request('GET', 'ads', $params);
if ($result->total) {
  throw new \Exception("Failed to bulk delete the example items");
}
else print "Bulk deleted items<br />";


solsearch_rest_request('DELETE', 'groups', []);
print "<font color=green>Success!</font><br />";
exit;



function solsearch_rest_request($method, $path, array $parameters = array(), $body = '') {
  global $apikey;
  $options = ['http' => [
    'method' => $method,
    'content' => $body,
    'header' => [
      'Accept: application/json
apikey: ' .($apikey ?: $_GET['apikey']) .'
Content-type: application/json'
    ]
  ]];
  $path = 'http://'. $_GET['url'] .'/'.$path;
  //prepare the url with the query parameters
  if ($parameters) {
    $str = '';
    foreach ($parameters as $param => $val) {
      $str[] .= "$param=$val";
    }
    $path .= '?'.implode('&', $str);
  }
  $context = stream_context_create($options);

  $response = file_get_contents($path, FALSE, $context);
  if ($response and $result = json_decode($response)) {
    return $result;
  }
  elseif($response == "false" or $response == 'true') {
    return;
  }
  elseif($response == "null") {
    return;
  }
echo "rest response to $method $path:";  print_r($response);
  die('unknown response');


  $fp = fopen($path, 'rb', false, $context);

  if (!$fp) {
    throw new Exception("Problem with Url");
  }

  $response = stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $sUrl, $php_errormsg");
  }
  return json_decode($response);
}

function getTestItem() {
  return '
  {
    "id": "99",
    "title": "Mind adventures",
    "body": "litle bit of escaped <strong>html</strong>",
    "keywords": "blah, blue, blow",
    "location": "POINT (0 50)",
    "scope": "3",
    "path": "node/99",
    "directexchange": "true",
    "indirectexchange": "true",
    "money": "false",
    "expires": 15000000000,
    "type": "offer",
    "lang": "en",
    "url": "http:\/\/matslats.net\/ad\/38",
    "image": "blah.jpg"
  }';
}

function solsearch_test_exception_handler(\Exception $e) {
  solsearch_rest_request('DELETE', 'groups', []);
  trigger_error($e->getMessage(), E_USER_ERROR);
}

