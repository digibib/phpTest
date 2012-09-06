<?php		


// Funksjon for å finne forsidebilde

function coverImage($book) {
	
	$query ="PREFIX dct: <http://purl.org/dc/terms/>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX fabio: <http://purl.org/spar/fabio/>
	SELECT
		sql:sample(?image) as ?image
		sql:sample(?sameLanguageImage) as ?sameLanguageImage
		sql:sample(?anyImage) as ?anyImage
	WHERE {
		GRAPH <http://data.deichman.no/books> {
			<$book> dct:format ?format ;
				dct:language ?language .
			OPTIONAL {
				<$book> foaf:depiction ?image .
				FILTER (regex(?image, \"bokkilden.no\"))
			}
			OPTIONAL {
				?work fabio:hasManifestation <$book> ;
					fabio:hasManifestation ?sameLanguageBook .
				?sameLanguageBook dct:format ?format ;
					dct:language ?language ;
					foaf:depiction ?sameLanguageImage .
				FILTER (regex(?sameLanguageImage, \"bokkilden.no\"))
			}
			OPTIONAL {
				?work fabio:hasManifestation <$book> ;
					fabio:hasManifestation ?anyBook .
				?anyBook foaf:depiction ?anyImage .
				FILTER (regex(?anyImage, \"bokkilden.no\"))
			}
		}
	}";
	
	$root = localQuery($query);
	$list = $root->getElementsByTagName("binding");
	
	for ($n=0; $n<$list->length; $n++) {
		$binding = $list->item($n);
		$variable = $binding->getElementsByTagName("variable")->item(0)->nodeValue;
		$value = $binding->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		
		if ($variable == "image") $image = $value;
		if ($variable == "sameLanguageImage") $sameLanguageImage = $value;
		if ($variable == "anyImage") $anyImage = $value;
	}
	
	if (empty($image)) {
		if (empty($sameLanguageImage)) {
			if (empty($anyImage)) {
				$image = "http://www.bokkilden.no/SamboWeb/servlet/VisBildeServlet?produktId=1872738";
			} else $image = $anyImage;
		} else $image = $sameLanguageImage;
	}
	
	return $image;
}



// Funksjon for generelle spørringer etter xml

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


// Funksjon for sparql-spørringer til data.deichman.no

function localQuery($query) {
$handle = "http://data.deichman.no/sparql/?default-graph-uri=&should-sponge=&query=".urlencode($query)."&debug=on&timeout=&format=application%2Frdf%2Bxml&save=display&fname=";
$root = query($handle);
return $root ;
}


// Funksjon for å normalisere inverterte personnavn

function normalize($inverted) {
	$pos = strpos($inverted, ", ");
	if ($pos != false) {
		$split = $pos + 2;
		$len = strlen($inverted);
		$firstPart =  substr($inverted, $split, ($len-$split));
		$secondPart = substr($inverted, 0, $pos);
		$normalized = $firstPart." ".$secondPart;
	} else $normalized = $inverted;
	return $normalized;
}


//Funksjon for å erstate tegn i søkestreng

function searchString($string) {
	$chars = array(" ", "æ", "ø", "å", "é", "è", "ë", "ö", "ä", "ó", "í", "á", "ã", "ü", "(", ")", "/", ",", ".", "'", "č", "š");
	$transcribed = array("+", "ae", "o", "a", "e", "e", "e", "o", "a", "o", "i", "a", "a", "u", "", "", "", "", "", "", "c", "s");
	$string = mb_strtolower($string, 'UTF-8');
	$string = str_replace($chars, $transcribed, $string);
	return $string;
}


// Funksjon for å sjekke respons

function checkBE($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = "";
	curl_exec($ch);
	if(!curl_errno($ch)) {
		$info = curl_getinfo($ch);
		$retur = $info['http_code'];
	}
	curl_close($ch);
	return $retur;
}


// Funksjon for å lage lister med relaterte titler

function listTitles($query) {
	
	$root = localQuery($query);
	$list = $root->getElementsByTagName("solution");
	$listLen = $list->length;
	
	if ($listLen > 0) {
	
		for ($f=0; $f<$listLen; $f++) {
			$place[$f] = $f;
		}
	
		shuffle($place);
		$end = 0;
		$count = 0;
		$listNo = 0;
		$titleList = "<table border='0'><tr>";
		
		while ($end == 0) {
			$solutions = $list->item($place[$count])->getElementsByTagName("value");
			$uri = $solutions->item(0)->getAttribute("rdf:resource");
			$tnr[$listNo] = str_replace("http://data.deichman.no/resource/tnr_", "", $uri);
			$title[$listNo] = $solutions->item(1)->nodeValue;
			
			
			// Undersøk om tittelen allerede er tatt med
			
			$match = 0;
			for ($f=0; $f<$listNo; $f++) {
				if ($title[$f] == $title[$listNo]) $match=1;
			}
			
			if ($match == 0) {
				$image = coverImage($uri);
				$image = $image."&width=80";
				$listItem = "<td width='90' align='center'><a href='ah.php?id=$tnr[$listNo]'><img src='$image'/></a></td>\n";
				$titleList .= $listItem;
				$listNo++;
			}
			$count++;
			if ($listNo == 6 || $count == $listLen) $end = 1; // Undersøk om lista er komplett
		}
		
		$titleList .= "</tr><tr>";
		
		for ($n=0; $n<$listNo; $n++) {
			$titleList .=  "<td align='center'><a href='ah.php?id=$tnr[$n]'><b>$title[$n]</b></a></td>\n";
		}
		
		$titleList .= "</tr></table>";
	}
	return $titleList;
}

?>