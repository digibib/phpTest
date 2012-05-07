<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Velg verdier</title>
		<link rel="stylesheet" type="text/css" href="scout_2.css" />
	</head>
	<body>
	
<?php

include "functions.php";

function dropDown($query) {	
	$root = sparqlQuery($query);
	$list = $root->getElementsByTagName("solution");
	$dropDown = "<select name='audience'>\n";
	$dropDown .= "<option selected='selected'>Alle</option>\n";
	for ($f=0; $f<$list->length; $f++) {
		$value = $list->item($f)->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		$dropDown .= "<option>$value</option>\n";
	}
	$dropDown .= "</select>\n";
	return $dropDown;
}

$query = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
SELECT DISTINCT ?o WHERE {
?book dct:format <http://data.deichman.no/format/Book> ;
dct:title ?title ;
dct:audience ?o ;
foaf:depiction ?img .
?work fabio:hasManifestation ?book ;
rev:hasReview ?review .
} ORDER BY str(?o)";

$dropDown = dropDown($query);

echo "<form action='lister_2.php' method='get'>";
echo "<table><tr><td>Målgruppe: </td>";
echo "<td>$dropDown</td></tr>";
echo "<tr><td/><td><input type='submit'></td></tr>";
echo "</table></form>";

?>

	</body>
</html>