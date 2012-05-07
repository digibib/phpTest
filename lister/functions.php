<?php

// Funksjon for generell spørring

function query($handle) {
	
	$proxy = "10.172.2.8:3128"; //<-optional proxy IP
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $handle); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 

	if($proxy){
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
		curl_setopt($ch, CURLOPT_PROXY, "$proxy"); 
	}

	$result = curl_exec($ch);
	echo curl_error($ch);
	curl_close($ch);
	
	$doc = new DomDocument();
	$doc->loadXML($result);
	$root = $doc->documentElement;
	
	return $root;
}


// Spørring mot data.deichman

function sparqlQuery($query) {
	
	$handle = "http://data.deichman.no/sparql/?default-graph-uri=&should-sponge=&query=".urlencode($query)."&debug=on&timeout=&format=application%2Frdf%2Bxml&save=display&fname=";
	$root = query($handle);
	return $root;
}


// Spørring mot Melville

function melvilleQuery($query) {
	$handle = "http://171.23.133.205:8890/sparql?default-graph-uri=&query=".urlencode($query)."&should-sponge=&format=application%2Frdf%2Bxml&timeout=0&debug=on";
	$root = query($handle);
	return $root;
}

?>