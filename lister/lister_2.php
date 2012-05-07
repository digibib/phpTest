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

// echo "<p>$audience</p>";

function dropDown($query) {	
	$root = sparqlQuery($query);
	$list = $root->getElementsByTagName("solution");
	$dropDown = "<select name='format'>\n";
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
dct:title ?title ;\n";

if ($audience != "Alle") $query .= "dct:audience <$audience> ;\n";

$query .= "<http://data.deichman.no/literaryFormat> ?o ;
foaf:depiction ?img .
?work fabio:hasManifestation ?book ;
rev:hasReview ?review .
} ORDER BY str(?o)";

// echo "<p>".urlencode($query)."</p>";

$dropDown = dropDown($query);

echo "<form action='lister_3.php' method='get'>";
echo "<table><tr><td>Målgruppe: </td>";
echo "<td><select name='audience'><option>$audience</option></select></td></tr>";
echo "<tr><td>Litterær form: </td>";
echo "<td>$dropDown</td></tr>";
echo "<tr><td/><td><input type='submit'></td></tr>";
echo "</table></form>";

?>

	</body>
</html>