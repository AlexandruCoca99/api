<?php
//CORS enable
header("Access-Control-Allow-Origin: *");
$qs = "indent=true&q.op=OR&q=logo%3A*&rows=10000&omitHeader=true&useParams=";


//get all the logo from SOLR
//https://solr.peviitor.ro/solr/#/auth/query?q=logo:*&q.op=OR&indent=true&rows=100000&useParams=

//remove _version_ 

//add total
//add companies [{name,logo}]


   function get_master_server(){
    $method = 'GET';
    $server = "https://api.peviitor.ro/";
    $core  = 'v0';
    $command ='/server/';
    $qs = '';
    $url =  $server.$core.$command.$qs;
   
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    $json = json_decode($result);
    foreach($json as $item)
        {
            if ($item->status=="up"){
                return $item->server;
                break;
            }
        }
}

$core ="auth";
$url =  get_master_server().$core.'/select?'.$qs;

$string = file_get_contents($url);
$json = json_decode($string, true);
$companies = $json['response']['docs'];


$results =  new stdClass();
$results->companies = array();
$results->companies = $companies;


$test = array();
foreach($companies as $company) 
{

    $item = $company["id"];
    $xurl  =  $company["logo"];
    $url  = $xurl[0];
    $test[$item] = $url;
    
}


$url = get_master_server().'/#/'.$core.'/query?q=logo:*&q.op=OR&indent=true&rows=100000&useParams=';
$string = file_get_contents($url);
$json = json_decode($string, true);

var_dump($json);
$companies = $json['response']['docs'];

$results =  new stdClass();
$results->total = count($companies);
$results->companies = array();

for($i=0;$i<count($companies);$i++) {
   
  
    $obj = new stdClass();
    $obj->name = $companies[$i];   
    $obj->logo = $test[$obj->name];
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
   

    
}

//echo json_encode($results);
?>