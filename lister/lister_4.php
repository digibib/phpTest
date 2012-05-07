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

$audience = $_GET['audience'];
$format = $_GET['format'];
$genre = $_GET['genre'];

// echo "<p>$audience</p>";

function dropDown($query) {	
	$root = sparqlQuery($query);
	$list = $root->getElementsByTagName("solution");
	$dropDown = "<select name='subject'>\n";
	$dropDown .= "<option selected='selected'>Alle</option>\n";
	for ($f=0; $f<$list->length; $f++) {
		$value = $list->item($f)->getElementsByTagName("value")->item(0)->getAttribute("rdf:resource");
		$label = $list->item($f)->getElementsByTagName("value")->item(1)->nodeValue;
		$dropDown .= "<option value='$value'>$label</option>\n";
	}
	$dropDown .= "</select>\n";
	return $dropDown;
}

$query = "PREFIX dct: <http://purl.org/dc/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX rev: <http://purl.org/stuff/rev#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
SELECT DISTINCT ?o ?label WHERE {
?book dct:format <http://data.deichman.no/format/Book> ;
dct:title ?title ;\n";

if ($audience != "Alle") $query .= "dct:audience <$audience> ;\n";
if ($format != "Alle") $query .= "<http://data.deichman.no/literaryFormat> <$format> ;\n";
if ($genre != "Alle") $query .= "<http://dbpedia.org/ontology/literaryGenre> <$genre> ;\n";

$query .= "dct:subject ?o ;
foaf:depiction ?img .
?work fabio:hasManifestation ?book ;
rev:hasReview ?review .
?o skos:prefLabel ?label .
} ORDER BY ?label";

// echo "<p>".urlencode($query)."</p>";

$dropDown = dropDown($query);

echo "<form action='display.php' method='get'>";
echo "<table><tr><td>Målgruppe: </td>";
echo "<td><select name='audience'><option>$audience</option></select></td></tr>";
echo "<tr><td>Litterær form: </td>";
echo "<td><select name='format'><option>$format</option></select></td></tr>";
echo "<tr><td>Sjanger: </td>";
echo "<td><select name='genre'><option>$genre</option></select></td></tr>";
echo "<tr><td>Emne: </td>";
echo "<td>$dropDown</td></tr>";
echo "<tr><td/><td><input type='submit'></td></tr>";
echo "</table></form>";

?>

	</body>
</html>