<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Aktive hyller</title>
		<link rel="stylesheet" type="text/css" href="ah.css" />
		<script>
        function init() {
          var Socket = "MozWebSocket" in window ? MozWebSocket : WebSocket;
          var ws = new Socket("ws://localhost:2000/");
          ws.onmessage = function(evt) { 
            //alert("Received tag: " + evt.data); 

            // make sure we do not send initial server handshake!
            if (evt.data != "hello server" && evt.data != "connected!") {
              document.ah.id.value = evt.data;
              document.forms.ah.submit();
            };
          };
          ws.onopen = function() {
            ws.send("hello server");
            ws.send("connected!");
          };
        };
        </script>		
	</head>
	<body onLoad="init()">
	
<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
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

?>
<table align='center' width='90%' border='0'>
<tr border='0'><td border='0'><a href='index.html'><img src='images/header.jpg'/></a></td>
<td align='right' border='0'><form name='ah' action='ah.php' method='get'>
<input type='hidden' name='id'/><img src='images/rfid-icon.jpg' width='70' height='70'/><br/>legg ny bok p&aring; leseren 
</td></tr></form>

<tr><td colspan='2'><table width='100%' border='5'><tr>

<td colspan='2'>
<table border='0'><tr>
<?php
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

$result = localQuery($originQuery);
$originList = $result["results"]["bindings"];
$firstSolution = $originList[0];
$originTitle = $firstSolution["title"]["value"];
$catName = $firstSolution["catName"]["value"];
$authorName = normalize($catName);


// Hent forsidebilde og ingress fra Bokkilden

$bkString = $authorName." ".$originTitle;
$bkHandle = $bkBase.searchString($bkString).$bkTail;
$bkRoot = query($bkHandle, "xml");
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

// Hent tekstutdrag fra ønskebok

$obQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
select ?excerpt where {
?work fabio:hasManifestation <$originUri> ;
dct:hasPart ?quote .
?quote a bibo:Quote ;
rdf:value ?excerpt .}";

$obRoot = localQuery($obQuery);
$obList = $obRoot["results"]["bindings"];
if ($obList) {
	$excerpt = $obList["excerpt"]["value"];
}

if (!empty($imageUrl)) {
	echo "<td width='200' align='center' border='0'>";
	echo "<img src='$imageUrl'/></td>";
}

echo "<td border='0'>";
echo "<h3>$originTitle av $authorName</h3>";

if (!empty($excerpt)) {
	echo "<p><hr/></p><p><i>&#171;&hellip;$excerpt&#187;</i></p>";
}

if (!empty($ingress)) echo "<p><hr/></p><p>$ingress (bokkilden.no)</p>";



echo "</td></tr></table></td>";  // Avslutt felt 1


// Felt 2

echo "<td rowspan='2' width='33%'>";

// Hent anbefalinger lokalt

$revQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
PREFIX dct: <http://purl.org/dc/terms/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
select distinct ?text ?sourceName where {
?work fabio:hasManifestation <$originUri> ;
rev:hasReview ?rev .
?rev rev:text ?text ;
dct:source ?source .
?source rdfs:label ?sourceName .
filter ((lang(?text) = \"nb\")||(lang(?text) = \"no\"))}";

$json = localQuery($revQuery);
$revList = $json["results"]["bindings"];
if ($revList) {
	foreach ($revList as $n=>$list) {
		$revText[$n] = $list["text"]["value"];
		$source[$n] = $list["sourceName"]["value"];
	}
}
if (!empty($revText[0])) {
	echo "<h3>Anbefaling fra ".$source[0]."</h3>";
	echo "<p>".$revText[0]."</p>";
}
echo "</td>"; // Avslutt felt 2


// Felt 3

echo "<tr><td width='200'>";

// Hent ISBN for alle manifestasjoner
foreach ($originList as $list) {
	$isbn = $list["isbn"]["value"];
	
// Hent terningkast fra Bokelskere.no
	if (!empty($isbn)) {
		$beHandle = $beBase.$isbn.$beTail;
		$check_status = checkBE($beHandle);
		if ($check_status != "404") {
			$result = query($beHandle, "xml");
			$rating = $result->getElementsByTagName("gjennomsnittelig_terningkast")->item(0)->nodeValue;
			$ratingNo = $result->getElementsByTagName("antall_terningkast")->item(0)->nodeValue;
			if ($rating != "None") {
				$rating = round($rating*2);
				$ratingImg = "images/rating_".$rating.".png";
				echo "<p><img src='$ratingImg'/><br/>(basert på $ratingNo leservurdering";
	            if ($ratingNo > 1) echo "er";
                echo " fra bokelskere.no)</p>";

				break;
			}
		}
    }
}
echo "</td>"; // Avslutt felt 3


// Felt 4

echo "<td>";

if (!empty($revText[1])) {
	echo "<h3>Anbefaling fra $source[1]</h3>";
	echo "<p>$revText[1]</p>";
}
echo "</td></tr>"; // Avslutt felt 4


// Felt 5

echo "<tr><td colspan='3'><table border='0'><tr>";

//Vis noe som ligner

$query = "PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX dct: <http://purl.org/dc/terms/>
select distinct ?simWork where {
?work fabio:hasManifestation <$originUri> ;
<http://data.deichman.no/similarWork> ?simWork ;
dct:creator ?creator1 .
?simWork dct:creator ?creator2 .
filter (?creator1 != ?creator2)
} limit 10";
	
$json = localQuery($query);
$result = $json["results"]["bindings"];
if ($result) {

	$similar = 1;
	$titleList = "<table border='0'><tr>";
	$listNo=0;

	foreach ($result as $works) {
		$work = $works["simWork"]["value"];

		$manQuery = "PREFIX fabio: <http://purl.org/spar/fabio/>
		PREFIX dct: <http://purl.org/dc/terms/>
		PREFIX bibo: <http://purl.org/ontology/bibo/>
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
		select ?doc ?title ?image where {
		<$work> fabio:hasManifestation ?doc.
		?doc dct:format <http://data.deichman.no/format/Book> ;
		dct:title ?title ;
		bibo:isbn ?isbn ;
		foaf:depiction ?image ;
		dct:language ?language .
		filter (regex(?image, \"bokkilden\"))
		filter (?language = <http://lexvo.org/id/iso639-3/nob> || ?language = <http://lexvo.org/id/iso639-3/nno>)
		}limit 1";
	
		// echo urlencode($manQuery);
	
		$man_result = localQuery($manQuery);
		$man_json = $man_result["results"]["bindings"];
		
		foreach ($man_json as $list) {
			$uri = $list["doc"]["value"];
			$tittelnummer[$listNo] = str_replace("http://data.deichman.no/resource/tnr_", "", $uri);
			$title[$listNo] = $list["title"]["value"];
			$image = $list["image"]["value"]."&width=80";
			$titleList .= "<td width='100' align='center'><a href='ah.php?id=$tittelnummer[$listNo]'><img src='$image'/></a><br/>";
			$listNo++;
		}
	}

	$titleList .= "</tr><tr>";
	for ($n=0; $n<$listNo; $n++) {
		$titleList .=  "<td align='center'><a href='ah.php?id=$tittelnummer[$n]'><b>$title[$n]</b></a></td>\n";
	}
	$titleList .= "</tr></table>";

	echo "<td width='40 %'><h3>Noe som ligner</h3>";
	echo $titleList;
	echo "</td>\n";
}	


// Finn forfattere

$creatorQuery = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
select ?creator ?name where {
<$originUri> dct:creator ?creator .
?creator foaf:name ?name .}";

$creatorRoot = localQuery($creatorQuery);
$creator_json = $creatorRoot["results"]["bindings"];

if ($creator_json) {
	foreach ($creator_json as $n=>$list) {
		$creators[$n] = $list["creator"]["value"];
		$creatorCatName[$n] = $list["name"]["value"];
		$creatorName[$n] = normalize($creatorCatName[$n]);
	}
}


$listCount=0;

// Vis forfatterrelaterte titler

if (!empty($creators)) {
	foreach ($creators as $n=>$creator) {
		$predicate = "http://purl.org/dc/terms/creator";
		$creatorRelatedList = listTitles($predicate, $creator, $originTitle, $listLen = "5");
		if (!empty($creatorRelatedList)) {
			echo "<td width='20%'/><td width='40 %'><h3>$creatorName[$n]</h3>";
			echo $creatorRelatedList;
			echo "</td>\n";
		}
	}
}

// Avslutt ramme

echo "</tr></table>";
echo "</td></tr>";
echo "</table>";

?>
	</body>
</html>
