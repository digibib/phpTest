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

$bkBase = "http://partner.bokkilden.no/SamboWeb/partner.do?rom=MP&format=XML&uttrekk=5&pid=0&ept=3&xslId=117&antall=10&enkeltsok=";
$bkTail = "&profil=partner&sort=popularitet&order=DESC";
$beBase = "http://bokelskere.no/api/1.0/boker/info/";
$beTail = "/?format=xml";

$id = $_GET['id'];

if (strlen($id) > 7) {
	$tnr = strval(floatval(substr($id,4,7)));
} else $tnr=strval(floatval($id));

$originUri = "http://data.deichman.no/resource/tnr_".$tnr;


// Vis ramme

echo "<table align='center' width='90%' border='0'>";
echo "<tr border='0'><td border='0'><a href='index.html'><img src='images/header.jpg'/></a></td>";
echo "<td align='right' border='0'><form name='ah' action='ah.php' method='get'>";
echo "<input type='text' size='20' name='id'/>";
echo "</td></tr>";

echo "<tr><td colspan='2'><table width='100%' border='5'><tr>";


// Felt 1

echo "<td colspan='2'>";
echo "<table border='0'><tr>";

// Hent tittel, forfatternavn og ISBN lokalt

$originQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX radatana: <http://def.bibsys.no/xmlns/radatana/1.0#>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX rda: <http://rdvocab.info/Elements/>
select ?title ?responsibility ?catName where {
<$originUri> dct:title ?title ;
rda:statementOfResponsibility ?responsibility ;
dct:creator ?creator .
?creator radatana:catalogueName ?catName .}";

// echo urlencode($originQuery);

$originRoot = localQuery($originQuery);
$originList = $originRoot->getElementsByTagName("solution");
$firstSolution = $originList->item(0);
$originTitle = $firstSolution->getElementsByTagName("value")->item(0)->nodeValue;
$responsibility = $firstSolution->getElementsByTagName("value")->item(1)->nodeValue;
$catName = $firstSolution->getElementsByTagName("value")->item(2)->nodeValue;
$authorName = normalize($catName);


// Henter omslagsbilde
$imageUrl = coverImage($originUri);
$imageUrl = $imageUrl."&width=190";


// Hent ingress fra Bokkilden

$bkString = $authorName." ".$originTitle;
$bkHandle = $bkBase.searchString($bkString).$bkTail;
$bkRoot = query($bkHandle);
$bkList = $bkRoot->getElementsByTagName("Produkt");

unset($innbinding);
unset($bkTitle);

if ($bkList->length > 0) {
	for ($n=0; $n<$bkList->length; $n++) {
		$produkt = $bkList->item($n);
		$bkTitle = $produkt->getElementsByTagName("Tittel")->item(0)->nodeValue;
		if ($bkTitle == $originTitle) {
			$ingress =  $produkt->getElementsByTagName("Ingress")->item(0)->nodeValue;
			// $imageUrl = $produkt->getElementsByTagName("BildeURL")->item(0)->nodeValue;
			// $imageUrl = str_replace("width=80", "width=190", $imageUrl);
			if (!empty($ingress)) break;
		}
	}
}

// Hent tekstutdrag fra ønskebok

$obQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
select ?excerpt where {
?work fabio:hasManifestation <$originUri> .
?quote a bibo:Quote ;
dct:isPartOf ?work ;
rdf:value ?excerpt .}";

$obRoot = localQuery($obQuery);
$obList = $obRoot->getElementsByTagName("value");
if ($obList->length > 0) {
	$excerpt = $obList->item(0)->nodeValue;
}

if (!empty($imageUrl)) {
	echo "<td width='200' align='center' border='0'>";
	echo "<img src='$imageUrl'/></td>";
}

echo "<td border='0'>";
echo "<h2>$originTitle</h2><br/>($responsibility)";

/* if (!empty($excerpt)) {
	echo "<p><hr/></p><p><i>&#171;&hellip;$excerpt&#187;</i></p>";
} */

if (!empty($ingress)) echo "<p><hr/></p><p>$ingress (bokkilden.no)</p>";



echo "</td></tr></table></td>";  // Avslutt felt 1


// Felt 2

echo "<td rowspan='2' width='33%'>";

// Hentanbefalinger lokalt

$revQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?text ?sourceName where {
graph <http://data.deichman.no/books> {
?work fabio:hasManifestation <$originUri> ;
rev:hasReview ?rev .
}
graph <http://data.deichman.no/reviews> {
?rev rev:text ?text ;
dct:source ?source .
?source rdfs:label ?sourceName .
filter ((lang(?text) != \"nn\"))}}";

$revRoot = localQuery($revQuery);
$revList = $revRoot->getElementsByTagName("solution");
$revNo = $revList->length;
if ($revNo > 0) {
	for ($n=0; $n<$revNo; $n++) {
		$revText[$n] = $revList->item($n)->getElementsByTagName("value")->item(0)->nodeValue;
		$source[$n] = $revList->item($n)->getElementsByTagName("value")->item(1)->nodeValue;
	}
}

$review0 = 0;

if (!empty($excerpt)) {
	echo "<h3>Tekstutdrag</h3>";
	echo "<p><hr/></p><p><i>&#171;&hellip;$excerpt&#187;</i></p>";
}

if (!empty($revText[1])) {
	echo "<h3>Anbefaling fra $source[0]</h3>";
	echo "<p><hr/></p><p>$revText[0]</p>";
	$review0 = 1;
}

echo "</td>"; // Avslutt felt 2


// Felt 3

echo "<tr><td width='200'>";

// Hent terningkast fra Bokelskere.no

$isbnQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
SELECT DISTINCT ?isbn WHERE {
?work fabio:hasManifestation <$originUri> ;
bibo:isbn ?isbn .
FILTER ((regex(?isbn, \"^82\")) || (regex(?isbn, \"^97882\")))}";

$isbnRoot = localQuery($isbnQuery);
$isbnList = $isbnRoot->getElementsByTagName("value");
$isbnNo = $isbnList->length;
for ($n=0; $n<$isbnNo; $n++) {
	$isbn[$n] = $isbnList->item($n)->nodeValue;
	$beHandle = $beBase.$isbn[$n].$beTail;
	$check_status = checkBE($beHandle);
	if ($check_status != "404") {
		$beRoot = query($beHandle);
		$rating = $beRoot->getElementsByTagName("gjennomsnittelig_terningkast")->item(0)->nodeValue;
		$ratingNo = $beRoot->getElementsByTagName("antall_terningkast")->item(0)->nodeValue;
		if (($rating != "None") && ($rating != 0)) {
			$rating = round($rating*2);
			$ratingImg = "images/rating_".$rating.".png";
			echo "<p><img src='$ratingImg'/><br/>(basert på $ratingNo leservurdering";
			if ($ratingNo > 1) echo "er";
			echo " fra bokelskere.no)</p>";
			break;
		}
	}
}

echo "</td>"; // Avslutt felt 3


// Felt 4

echo "<td>";


if (!empty($revText[$review0])) {
		echo "<h3>Anbefaling fra $source[$review0]</h3>";
		echo "<p>$revText[$review0]</p>";
}	

echo "</td></tr>"; // Avslutt felt 4


// Felt 5

echo "<tr><td colspan='3'><table border='0'><tr>";


//Vis noe som ligner

echo "<td width='540' align='left'>";

$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX deich: <http://data.deichman.no/>
select distinct ?book ?title where {
?work fabio:hasManifestation <$originUri> ;
dct:creator ?creator .
{?work deich:similarWork ?similarWork .} UNION {?work deich:autoGeneratedSimilarity ?similarWork .}
?similarWork fabio:hasManifestation ?book .
MINUS {?similarWork dct:creator ?creator .}
?book dct:format <http://data.deichman.no/format/Book> ;
dct:title ?title ;
foaf:depiction ?image ;
dct:language ?lang .
MINUS {?work fabio:hasManifestation ?book .}
FILTER (?lang = <http://lexvo.org/id/iso639-3/nob> || ?lang = <http://lexvo.org/id/iso639-3/nno> )}";

$similarList = listTitles($query);
if (!empty($similarList)) {
	echo "<h3>Noe som ligner?</h3>";
	echo $similarList;
}

echo "</td>\n";
echo "<td width='100'>";


// Vis andre bøker av samme forfatter

echo "<td width='540' align='left'>";

$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX fabio: <http://purl.org/spar/fabio/>
SELECT DISTINCT ?book ?title where {
<$originUri> dct:creator ?creator .
?work fabio:hasManifestation <$originUri> .
?book dct:format <http://data.deichman.no/format/Book> ;
dct:creator ?creator ;
dct:title ?title ;
foaf:depiction ?image ;
dct:language ?lang .
FILTER (?lang = <http://lexvo.org/id/iso639-3/nob> || ?lang = <http://lexvo.org/id/iso639-3/nno> )
MINUS {?work fabio:hasManifestation ?book .}}";

$sameCreatorList = listTitles($query);
if (!empty($sameCreatorList)) {
	echo "<h3>Andre bøker av samme forfatter</h3>";
	echo $sameCreatorList;
}

echo "</td>\n";


// Avslutt ramme

echo "</tr></table>";
echo "</td></tr>";
echo "</table>";

?>
	</body>
</html>