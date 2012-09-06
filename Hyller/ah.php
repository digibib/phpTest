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

if (strlen($id) > 7) $id = substr($id,4,7);
$tnr=strval(floatval($id));

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
PREFIX fabio: <http://purl.org/spar/fabio/>
select ?title ?catName ?isbn where {
<$originUri> dct:title ?title ;
dct:creator ?creator .
?work fabio:hasManifestation <$originUri> ;
bibo:isbn ?isbn .
?creator radatana:catalogueName ?catName .}";

$originRoot = localQuery($originQuery);
$originList = $originRoot->getElementsByTagName("solution");
$firstSolution = $originList->item(0);
$originTitle = $firstSolution->getElementsByTagName("value")->item(0)->nodeValue;
$catName = $firstSolution->getElementsByTagName("value")->item(1)->nodeValue;
$authorName = normalize($catName);


// Hent forsidebilde og ingress fra Bokkilden

$bkString = $authorName." ".$originTitle;
$bkHandle = $bkBase.searchString($bkString).$bkTail;
$bkRoot = query($bkHandle);
$bkList = $bkRoot->getElementsByTagName("Produkt");

unset($innbinding);
unset($bkTitle);

if ($bkList->length > 0) {
	for ($n=0; $n<$bkList->length; $n++) {
		$produkt = $bkList->item($n);
		$innbinding = $produkt->getElementsByTagName("Innbinding")->item(0)->nodeValue;
		if ($innbinding == "Paperback" || $innbinding == "Innbundet") {
			$bkTitle = $produkt->getElementsByTagName("Tittel")->item(0)->nodeValue;
			if ($bkTitle == $originTitle) {
				$ingress =  $produkt->getElementsByTagName("Ingress")->item(0)->nodeValue;
				$imageUrl = $produkt->getElementsByTagName("BildeURL")->item(0)->nodeValue;
				$imageUrl = str_replace("width=80", "width=190", $imageUrl);
				if (!empty($ingress) && !empty($imageUrl)) break;
			}
		}
	}
}


if (!empty($imageUrl)) {
	echo "<td width='200' align='center' border='0'>";
	echo "<img src='$imageUrl'/></td>";
}

echo "<td border='0'>";
echo "<h3>$originTitle av $authorName</h3>";
if (!empty($ingress)) echo "<p>$ingress (bokkilden.no)</p>";
echo "</td></tr></table></td>";  // Avslutt felt 1


// Felt 2

echo "<td rowspan='2' width='33%'>";

// Hent Ønskebok-omtale og -tekstutdrag lokalt

$obQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
select ?text ?excerpt where {
?work fabio:hasManifestation <$originUri> ;
rev:hasReview ?rev .
?rev rev:text ?text .
?quote a bibo:Quote ;
dct:isPartOf ?work ;
rdf:value ?excerpt .
filter (lang(?text) = \"nb\")
filter (regex(?rev, \"onskebok\"))}";

$obRoot = localQuery($obQuery);
$obList = $obRoot->getElementsByTagName("value");
if ($obList->length > 0) {
	$obText = $obList->item(0)->nodeValue;
	$obExcerpt = $obList->item(1)->nodeValue;
	$obExcerpt = str_replace("<br />", " ", $obExcerpt);
}

if (!empty($obText)) {
	echo "<h3>Omtale og tekstutdrag fra &Oslash;nskebok.no</h3>";
	echo "<p><hr/></p><p>$obText</p>";
	echo "<p><hr/></p><p><i>&#171;&hellip;$obExcerpt&#187;</i></p>";
}
echo "</td>"; // Avslutt felt 2


// Felt 3

echo "<tr><td width='200'>";

// Hent ISBN for alle manifestasjoner

$edsNo = $originList->length;
for ($n=0; $n<$edsNo; $n++) {
	$isbn[$n] = $originList->item($n)->getElementsByTagName("value")->item(2)->nodeValue;
}
	
	
// Hent terningkast fra Bokelskere.no

for ($n=0; $n<$edsNo; $n++) {
	if (!empty($isbn[$n])) {
		$beHandle = $beBase.$isbn[$n].$beTail;
		$check_status = checkBE($beHandle);
		if ($check_status != "404") {
			$beRoot = query($beHandle);
			$rating = $beRoot->getElementsByTagName("gjennomsnittelig_terningkast")->item(0)->nodeValue;
			$ratingNo = $beRoot->getElementsByTagName("antall_terningkast")->item(0)->nodeValue;
			if ($rating != "None") {
				$rating = round($rating*2);
				$ratingImg = "images/rating_".$rating.".png";
				break;
			}
		}
	}
}

if (!empty($rating)) {
	echo "<p><img src='$ratingImg'/><br/>(basert på $ratingNo leservurdering";
	if ($ratingNo > 1) echo "er";
	echo " fra bokelskere.no)</p>";
}
echo "</td>"; // Avslutt felt 3


// Felt 4

echo "<td>";

// Hent Bokhylla-omtale lokalt

$bhQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
PREFIX dct: <http://purl.org/dc/terms/>
select ?text where {
?work fabio:hasManifestation <$originUri> .
?rev a rev:Review ;
dct:subject ?work ;
rev:text ?text .
filter (regex(?rev, \"bokhylla\"))}";

$bhRoot = localQuery($bhQuery);
$bhList = $bhRoot->getElementsByTagName("value");
if ($bhList->length > 0) {
	$bhText = $bhList->item(0)->nodeValue;
}

if (!empty($bhText)) {
	echo "<h3>Omtale fra Bokhylla</h3>";
	echo "<p>$bhText (Deichmanske bibliotek)</p>";
}
echo "</td></tr>"; // Avslutt felt 4


// Felt 5

echo "<tr><td colspan='3'><table border='0'>";

// Finn forfattere

$creatorQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX radatana: <http://def.bibsys.no/xmlns/radatana/1.0#>
select ?creator ?name where {
<$originUri> dct:creator ?creator .
?creator radatana:catalogueName ?name .}";

$creatorRoot = localQuery($creatorQuery);
$creatorList = $creatorRoot->getElementsByTagName("solution");
$creatorNo = $creatorList->length;

if ($creatorNo > 0) {
	for ($n=0; $n<$creatorNo; $n++) {
		$creatorSolution = $creatorList->item($n);
		$creator[$n] = $creatorSolution->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		$creatorCatName[$n] = $creatorSolution->getElementsByTagName("value")->item(1)->nodeValue;
		$creatorName[$n] = normalize($creatorCatName[$n]);
	}
}


// Finn emner

$topicQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
select ?topic ?label where {
<$originUri> dct:subject ?topic .
?topic skos:prefLabel ?label .}";

$topicRoot = localQuery($topicQuery);
$topicList = $topicRoot->getElementsByTagName("solution");
$topicNo = $topicList->length;

if ($topicNo > 0) {
	for ($n=0; $n<$topicNo; $n++) {
		$topicSolution = $topicList->item($n);
		$topic[$n] = $topicSolution->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		$topicTerm[$n] = $topicSolution->getElementsByTagName("value")->item(1)->nodeValue;
	}
}


// Finn sjangrer

$genreQuery = "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX dbont: <http://dbpedia.org/ontology/>
select ?genre ?label where {
<$originUri> dbont:literaryGenre ?genre .
?genre rdfs:label ?label .}";

$genreRoot = localQuery($genreQuery);
$genreList = $genreRoot->getElementsByTagName("solution");
$genreNo = $genreList->length;

if ($genreNo > 0) {
	for ($n=0; $n<$genreNo; $n++) {
		$genreSolution = $genreList->item($n);
		$genre[$n] = $genreSolution->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		$genreLabel[$n] = $genreSolution->getElementsByTagName("value")->item(1)->nodeValue;
	}
}

$listCount=0;

// Vis forfatterrelaterte titler

if ($creatorNo > 0) {
	for ($n=0; $n<$creatorNo; $n++) {
		$predicate = "http://purl.org/dc/terms/creator";
		$creatorRelatedList = listTitles($predicate, $creator[$n], $originTitle);
		if (!empty($creatorRelatedList)) {
			if (round($listCount/2) == $listCount/2) {
				echo "<tr>";
			} else echo "<td width='20%'/>";
			echo "<td width='40%'><h3>Andre titler av $creatorName[$n]</h3>";
			echo $creatorRelatedList;
			echo "</td>";
			$listCount++;
			if (round($listCount/2) == $listCount/2) echo "</tr>\n";
		}
	}
}


// Vis emnerelaterte titler

if ($topicNo > 0) {
	for ($n=0; $n<$topicNo; $n++) {
		$predicate = "http://purl.org/dc/terms/subject";
		$topicRelatedList = listTitles($predicate, $topic[$n], $originTitle);
		if (!empty($topicRelatedList)) {
			if (round($listCount/2) == $listCount/2) {
				echo "<tr>";
			} else echo "<td width='20%'/>";
			echo "<td width='40%'><h3>Andre titler med emne $topicTerm[$n]</h3>";
			echo $topicRelatedList;
			echo "</td>";
			$listCount++;
			if (round($listCount/2) == $listCount/2) echo "</tr>\n";
		}
	}
}


// Vis sjangerrelaterte titler

if ($genreNo > 0) {
	for ($n=0; $n<$genreNo; $n++) {
		$predicate = "http://dbpedia.org/ontology/literaryGenre";
		$genreRelatedList = listTitles($predicate, $genre[$n], $originTitle);
		if (!empty($genreRelatedList)) {
			if (round($listCount/2) == $listCount/2) {
				echo "<tr>";
			} else echo "<td width='20%'/>";
			echo "<td width='40%'><h3>Andre titler i sjangren $genreLabel[$n]</h3>";
			echo $genreRelatedList;
			echo "</td>";
			$listCount++;
			if (round($listCount/2) == $listCount/2) echo "</tr>\n";
		}
	}
} // Avslutt felt 5


// Avslutt ramme

echo "</table>";
echo "</td></tr>";
echo "</table>";

?>
	</body>
</html>