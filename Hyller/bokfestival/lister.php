<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Aktive hyller</title>
		<link rel="stylesheet" type="text/css" href="ah.css" />
	</head>
	<body onLoad="document.forms.ah.id.focus()">
	
<?php

include "functions.php";

$id = $_GET['id'];

if (strlen($id) > 7) {
	$tnr = strval(floatval(substr($id,4,7)));
} else $tnr=strval(floatval($id));

$originUri = "http://data.deichman.no/resource/tnr_".$tnr;

// Noe som ligner


$query = "PREFIX dct: <http://purl.org/dc/terms/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	PREFIX format: <http://data.deichman.no/format/>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	select ?doc ?title ?image where {
	?doc a bibo:Document ;
	dct:format format:Book ;
	dct:title ?title ;
	bibo:isbn ?isbn ;
	<$predicate> <$value> ;
	foaf:depiction ?image ;
	dct:language ?language .
	filter (?title != \"$originTitle\")
	filter (regex(?image, \"bokkilden\"))
	filter (?language = <http://lexvo.org/id/iso639-3/nob> || ?language = <http://lexvo.org/id/iso639-3/nno>)}
	
	
	

$query = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX deich: <http://data.deichman.no/>
select distinct ?book ?title where {
?work fabio:hasManifestation <$originUri> .
{?work deich:similarWork ?similarWork .} UNION {?work deich:autoGeneratedSimilarity ?similarWork .}
?similarWork fabio:hasManifestation ?book .
?book a bibo:Document ;
dct:title ?title ;
dct:language ?lang .
MINUS {?work fabio:hasManifestation ?similarBook .}
FILTER (?lang = <http://lexvo.org/id/iso639-3/nob> || ?lang = <http://lexvo.org/id/iso639-3/nno> )}";

$root = localQuery($query);
$list = $root->getElementsByTagName("value");
for ($n=0; $n<$list->length; $n++) {
	$books = array_push($books, $list->item($n)->getAttribute("rdf:resource"));
}

$similarList = generateList($books)
?>