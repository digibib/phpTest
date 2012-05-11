<?php

// Funksjon for generelle spørringer etter xml

function query($handle, $format) {
	//$proxy = "10.172.2.8:3128"; //<-optional proxy IP
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $handle); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	/*
	 if($proxy){
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
		curl_setopt($ch, CURLOPT_PROXY, "$proxy"); 
	}
	*/
	$response = curl_exec($ch);
	if($format == "json"){
	  $result = json_decode($response,true); // true gives back php arrays, if not -> objects
	} elseif($format == "xml"){
	  $doc = new DomDocument();
	  $doc->loadXML($response);
	  $result = $doc->documentElement;
	} else {
      echo "wrong format";
	  $result = curl_error($ch);
	}
	curl_close($ch);
	return $result;
}


// Funksjon for sparql-spørringer til data.deichman.no

function localQuery($query) {
$handle = "http://data.deichman.no/sparql/?query=".urlencode($query)."&format=application%2Fjson";
$result = query($handle, "json");
return $result ;
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
	$transcribed = array("+", "ae", "o", "a", "e", "e", "e", "o", "ae", "o", "i", "a", "a", "u", "", "", "", "", "", "", "c", "s");
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

function listTitles($predicate, $value, $originTitle, $listLen = "5") {
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
	filter (?language = <http://lexvo.org/id/iso639-3/nob> || ?language = <http://lexvo.org/id/iso639-3/nno>)}";
	
	$rel_json = localQuery($query);
	$list = $rel_json["results"]["bindings"];
	$titleList = "";
	if ($list) {
		shuffle($list);
		$end = 0;
		$count = 0;
		$listNo = 0;
		$titleList = "<table border='0'><tr>";

		foreach ($list as $f=>$item) {
			$place[$f] = $f;
		
			$uri = $item["doc"]["value"];
			$tnr[$listNo] = str_replace("http://data.deichman.no/resource/tnr_", "", $uri);
			$title[$listNo] = $item["title"]["value"];
			$image = $item["image"]["value"]."&width=80";
			
			// Undersøk om tittelen allerede er tatt med
			
			$match = 0;
			for ($f=0; $f<$listNo; $f++) {
				if ($title[$f] == $title[$listNo]) $match=1;
			}
			
			if ($match == 0) {
				$listItem = "<td width='100' align='center'><a href='ah.php?id=$tnr[$listNo]'><img src='$image'/></a></td>\n";
				$titleList .= $listItem;
				$listNo++;
			}
			$count++;
			if ($listNo == 5 || $count == $listLen) $end = 1; // Undersøk om lista er komplett
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
