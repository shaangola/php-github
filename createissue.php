<?php
	/* php createissue.php do <username> <password> <repo> <title> <content>
	 * e.g. php createissue.php do shaangola <password> https://github.com/shaangola/demorep "test bug" "test bug description"
	 * same for bitbucket php createissue.php do shaangola <password> https://bitbucket.org/shaangola/demorep "test bug" "test bug description"
	 * here shaangola is my username, password not specify here for security concern.
	 * GitHub/BitBucket Issue Creator
	 * Test for Xerox assigned by Ashish
	 * (c) Shaan Gola <shaan.gola@gmail.com> 
	 */
	require_once 'gitvendor/autoload.php';
	require_once 'bitvendor/autoload.php';


	error_reporting(E_ALL);
	
	function error_send_usage() {
		exit("Usage: createissue.php do <username> <password> <repo> <title> <content>\r\n");
	}
	
	if(!function_exists("curl_init")) {
		exit("No curl found!");
	}
	
	if(!function_exists("json_decode")) {
		exit("No JSON found!");
	}
	
	if(!$argc > 0) { 
		error_send_usage();
	}
	 function is_json($str)
	{
		return is_array(json_decode($str,true));
	}
	
	// work out where the arguments are sitting 
	if($argv[0] == "do") {
		$i = 1;
	} elseif($argv[1] == "do") {
		$i = 2;
	} elseif($argv[3] == "do") {
		$i = 4;
	} else {
		error_send_usage();
	}
	
	// get the position of the arguments 
	
	$username	= $argv[$i];
	$password	= $argv[$i+1];
	$repo		= $argv[$i+2];
	$title		= $argv[$i+3];
	$content	= $argv[$i+4];
	
	
	if(strstr($repo, "github.com/")) {
	$client = new \Github\Client();
	try{
	$data = array("title" => $title, "body" => $content );
	$repo_data = str_replace("https://github.com/", "", $repo);
	$repo_split = explode("/", $repo_data);
	$http = new \Github\HttpClient\HttpClient();
	$client = new \Github\Client($http);
	$client->authenticate($username, $password, \Github\Client::AUTH_HTTP_PASSWORD);
	$issue=$client->api('issue')->create($username,$repo_split[1], $data);;
			echo("======================================".PHP_EOL);
			echo("post successfully".PHP_EOL);
			echo("======================================");
	}
	catch(Exception $e)
	{
	 echo (PHP_EOL);
	 echo (PHP_EOL);
	 echo ('Message: '.$e->getMessage());
	 echo (PHP_EOL);
	 echo (PHP_EOL);
	}
	}
	else{
	// login
	try{
	$data = array("title" => $title, "content" => $content );
	$repo_data = str_replace("https://bitbucket.org/", "", $repo);
	$repo_split = explode("/", $repo_data);
		$issue = new Bitbucket\API\Repositories\Issues;
		$issue->setCredentials( new Bitbucket\API\Authentication\Basic($username, $password) );
		$issueResult= $issue->create($username,$repo_split[1],$data);
		if(is_json($issueResult->getContent()))
			{
			echo("======================================".PHP_EOL);
			echo("post successfully".PHP_EOL);
			echo("======================================");
			}
			else 
			{
				echo("======================================".PHP_EOL);
				echo("Resource not Found".PHP_EOL);
				echo("======================================");
			}
	
	 	}
		
		catch(Exception $e)
		{
  			echo ('Message: '.$e->getMessage());
		}
	}


?>
