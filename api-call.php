<?php

require 'vendor/autoload.php';

use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;

// Get the $key_id / $secret variables that contain the Acquia API key/secret for your account
require_once("./api-config.php");

$auth = [$user_name, $key_id];

$container = [];
$history = Middleware::history($container);

$stack = HandlerStack::create();
// Add the history middleware to the handler stack.
$stack->push($history);

$clientHandler = new Client(['handler' => $stack]);

//$clientHandler = new Client();

/**
 * Obtain parameters
 */
$opts = array("api_call:",
              "site_url:",
              "site_name:",
              "site_id:",
              "domain:",
              "file:");
$params = getopt(null, $opts);
$app_name = (string) NULL;
$app_id = (string) NULL;
$env = (string) NULL;
try {
    if (empty($params['api_call'])){
      throw new Exception("\nPlease provide the api_call.\n\n");
    }
    if (empty($params['site_url'])){
        throw new Exception("\nPlease provide the site_url of the subscription.\n\n");
    }
}
catch( Exception $e ) {
    $message = $e->getMessage();

    die( $message );
}


if(isset($params['api_call'])) {
  switch ($params['api_call']) {
    case 'applications':
      get_applications($clientHandler, $auth, $params['site_url']);
      break;
    case 'site_create':
      if (isset($params['file'])) {
        site_create($clientHandler, $auth, $params['site_url'], '', $params['file']);
      }
      else if (isset($params['site_name'])){
        site_create($clientHandler, $auth, $params['site_url'], $params['site_name']);
      }
      else {
        print "Requires site_name or file parameters.";
      }
      break;
    case 'site_delete':
      if (isset($params['file'])) {
        site_delete($clientHandler, $auth, $params['site_url'], '', $params['file']);
      }
      else if (isset($params['site_id'])){
        site_delete($clientHandler, $auth, $params['site_url'], $params['site_id']);
      }
      else {
        print "Requires site_name or file parameters.";
      }
      break;
    case 'site_duplicate':
      if(isset($params['site_name'])) {
        site_create($clientHandler, $auth, $params['site_url'], $params['site_name']);
      } else {
        print "Requires site_name parameters.";
      }
      break;
    case 'site_details':
      if(isset($params['site_id'])) {
        site_details($clientHandler, $auth, $params['site_url'], $params['site_id']);
      } else {
        print "Requires site_id parameters.";
      }
      break;
    case 'site_db':
        site_db($clientHandler, $auth, $params['site_url']);
      break;
    case 'get_domains':
      if(isset($params['site_id'])) {
        get_domains($clientHandler, $auth, $params['site_url'], $params['site_id']);
      } else {
        print "Requires site_id parameters.";
      }
      break;
    case 'add_domains':
      if (isset($params['file'])) {
        add_domains($clientHandler, $auth, $params['site_url'], $params['site_id'], '', $params['file']);
      } else {
        add_domains($clientHandler, $auth, $params['site_url'], $params['site_id'], $params['domain']);
      }
      break;
    case 'delete_domains':
      if(isset($params['file'])) {
        delete_domains($clientHandler, $auth, $params['site_url'], $params['site_id'], '', $params['file']);
      } else {
        delete_domains($clientHandler, $auth, $params['site_url'], $params['site_id'], $params['domain']);
      }
      break;
    case 'environments':
      get_environments($clientHandler, $app_id, $env);
      break;
    case 'info':
      api_info();
      break;
  }
}

function api_info() {
  print "The API Call application is configured to provide integration to Acquia Cloud API\n\n" .
  "Options:\n" .
  "  applications\tDisplays the applications user has permissions to.\n\n" .
  "  crons\t\tMoves scheduled jobs from one environment to another.\n" .
  "\t\tRequires - docroot, realm, docroot_old, realm_old parameters\n\n" .
  "  config\tCompares configuration from one environment to another.\n" .
  "\t\tRequires - docroot, realm, docroot_old, realm_old parameters\n\n" .
  "  database\tCreates a new database or lists out databases.\n" .
  "\t\tCreate Database requires - file parameter\n\n" .
  "  domains-add\tCreates domains from a file or lists out domains.\n" .
  "\t\tCreate Domains requires - file parameter\n\n" .
  "  domains-delete\tDelete domains from a file or lists out domains.\n" .
  "\t\tDelete Domains requires - file parameter\n\n" .
  "  environments\tDisplays the environments for an application.\n\n" .
  "  info\t\tDisplays information about the application.\n\n";

}

function get_applications($client, $auth, $site_url) {

  try {
    $response = $client->request('GET', 'https://' . $site_url . '/api/v1/sites?limit=100&page=3', ['auth' => $auth ]);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  foreach($responseDetails as $itemDetails) {
    foreach ($itemDetails as $itemDetail) {
      print "Site ID: " .$itemDetail->id . "       Site Name: " .$itemDetail->site . "       Domain: " . $itemDetail->domain  . "       Database: " . $itemDetail->db_name . "\t\n";
    }
  }
  return ;
}

function site_create(Client $client, $auth, $site_url, $site_name = null, $file = null) {
  if (isset($file)) {
    $file = fopen($file, 'r');
    while (($line = fgetcsv($file)) !== FALSE) {
      try {
        $json = [
          'headers'=> ['Content-Type'=>'application/json'],
          'json'=> ['site_name' => $line[0], 'group_ids' => 4091, 'stack_id' => 3],
          'auth' => $auth,
        ];
        $response = $client->request('POST', 'https://' . $site_url . '/api/v1/sites', $json);
      } catch (ClientException $e) {
        print $e->getMessage();
        $response = $e->getResponse();
      }
      print "Successfully submitted request to create a new site: " . $line[0] . ". Please check after some time \n\n";
      sleep(80);
    }
    fclose($file);
    return;
  }

  try {
    $json = [
      'headers'=> ['Content-Type'=>'application/json'],
      'json'=> ['site_name' => $site_name, 'group_ids' => 4091, 'stack_id' => 3],
      'auth' => $auth,
    ];
    $response = $client->request('POST', 'https://' . $site_url . '/api/v1/sites', $json);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  if (isset($responseDetails->id)) {
    print "Successfully submitted request to create a new site. Please check after some time \n\n";
  }
  else {
    print "There was some issue submitting this request.\n\n";
  }

  return ;
}

function site_delete(Client $client, $auth, $site_url, $site_id = null, $file = null) {
  if (isset($file)) {
    $file = fopen($file, 'r');
    while (($line = fgetcsv($file)) !== FALSE) {
      try {
        $json = [
          'auth' => $auth,
        ];
        $response = $client->request('DELETE', 'https://' . $site_url . '/api/v1/sites/'. $line[0], $json);
      } catch (ClientException $e) {
        print $e->getMessage();
        $response = $e->getResponse();
        sleep(10);
      }
      print "Successfully submitted request to delete a site: " . $line[0] . ". Please check after some time \n\n";
    }
    fclose($file);
    return;
  }

  try {
    $json = [
      'auth' => $auth,
    ];
    $response = $client->request('DELETE', 'https://' . $site_url . '/api/v1/sites/'. $site_id , $json);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  if (isset($responseDetails->id)) {
    print "Successfully submitted request to delete a site:. Please check after some time \n\n";
  }
  else {
    print "There was some issue submitting this request.\n\n";
  }

  return ;
}

function site_duplicate(Client $client, $auth, $site_url, $site_name, $site_id) {

  try {
    $json = [
      'headers'=> ['Content-Type'=>'application/json'],
      'json'=> ['site_name' => $site_name, "exact_copy" => true],
      'auth' => $auth,
    ];
    $response = $client->request('POST', 'https://' . $site_url . '/api/v1/sites/' . $site_id . '/duplicate', $json);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  if (isset($responseDetails->id)) {
    print "Successfully submitted request to duplicate the site. Please check after some time \n\n";
  }
  else {
    print "There was some issue submitting this request.\n\n";
  }

  return ;
}

function site_details(Client $client, $auth, $site_url, $site_id) {

  try {
    $response = $client->request('GET', 'https://' . $site_url . '/api/v1/sites/' . $site_id, ['auth' => $auth ]);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  print_r($responseDetails);


  return ;
}

function site_db(Client $client, $auth, $site_url) {
  try {
    $myfile = fopen("testfile.csv", "w");
    $response = $client->request('GET', 'https://' . $site_url . '/api/v1/sites?limit=100&page=8', ['auth' => $auth ]);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  foreach($responseDetails as $itemDetails) {
    foreach ($itemDetails as $itemDetail) {
      fwrite($myfile, $itemDetail->id . ',' . $itemDetail->site . ',' . $itemDetail->db_name . "\n\n");
      print "Site ID: " .$itemDetail->id . "       Site Name: " .$itemDetail->site . "       Domain: " . $itemDetail->domain  . "       Database: " . $itemDetail->db_name . "\t\n";
    }
  }
  fclose($myfile);
  return ;
}

function get_domains(Client $client, $auth, $site_url, $site_id) {

  try {
    $response = $client->request('GET', 'https://' . $site_url . '/api/v1/domains/' . $site_id, ['auth' => $auth ]);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  print "Domain List:\n";
  foreach($responseDetails->domains->protected_domains as $protected_domain) {
    print "Protected domain: " . $protected_domain . "\n\n";
  }

  foreach($responseDetails->domains->custom_domains as $custom_domains) {
    print "Custom domain: " . $custom_domains . "\n\n";
  }

  return ;
}

function set_domain(Client $client, $auth, $site_url, $site_id, $domain) {

  try {
    $json = [
      'headers'=> ['Content-Type'=>'application/json'],
      'json'=> ['domain_name' => $domain],
      'auth' => $auth,
    ];
    $response = $client->request('POST', 'https://' . $site_url . '/api/v1/domains/' . $site_id . '/add', $json);
  } catch (ClientException $e) {
    print $e->getMessage();
    $response = $e->getResponse();
  }

  $responseDetails = json_decode($response->getBody());
  print_r($responseDetails->messages[0] . "\n\n");

  return ;
}


function add_domains(Client $client, $auth, $site_url, $site_id, $domain = null,  $domainFile = null) {

  if (isset($domainFile)) {
    $lines = file($domainFile);
    if (($h = fopen($domainFile, "r")) !== FALSE) {
      while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
        print_r($data);
        try {
          $json = [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['domain_name' => $data[0]],
            'auth' => $auth,
          ];
          $response = $client->request('POST','https://' . $site_url . '/api/v1/domains/' . $data[1] . '/add', $json);
        } catch (ClientException $e) {
          print $e->getMessage();
          $response = $e->getResponse();
        }
        sleep(3);
      }
    }
  }

}

function delete_domains(Client $client, $auth, $site_url, $site_id, $domain=null,  string $domainFile = null) {

  if (isset($domainFile)) {
    $lines = file($domainFile);
    foreach ($lines as $line_num => $line) {

      try {
        $json = [
          'headers'=> ['Content-Type'=>'application/json'],
          'json'=> ['domain_name' => $line],
          'auth' => $auth,
        ];
        $response = $client->request('POST','https://' . $site_url . '/api/v1/domains/' . $site_id . '/remove', $json);
      } catch (ClientException $e) {
        print $e->getMessage();
        $response = $e->getResponse();
      }
      sleep(3);
    }
  }
  else {
    try {
      $json = [
        'headers'=> ['Content-Type'=>'application/json'],
        'json'=> ['domain_name' => $domain],
        'auth' => $auth,
      ];
      $response = $client->request('POST', 'https://' . $site_url . '/api/v1/domains/' . $site_id . '/remove', $json);
    } catch (ClientException $e) {
      print $e->getMessage();
      $response = $e->getResponse();
    }

    $responseDetails = json_decode($response->getBody());
  }
  print "Domain deletion request processed succesfully \n\n";
}
