<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Display</title>
		<link rel="stylesheet" type="text/css" href="scout_2.css" />
	</head>
	<body">
	
<?php

include "functions.php";

$audience = $_GET['audience'];
$format = $_GET['format'];
$genre = $_GET['genre'];
$subject = $_GET['subject'];

$query = "PREFIX foaf:<http://xmlns.com/foaf/0.1/>
PREFIX bibo:<http://purl.org/ontology/bibo/>
PREFIX dct:<http://purl.org/dc/terms/>
PREFIX fabio:<http://purl.org/spar/fabio/>
PREFIX rev:<http://purl.org/stuff/rev#>
SELECT DISTINCT ?title (sql:GROUP_DIGEST(?author, ', ', 1000, 1) AS ?author) (sql:SAMPLE(?text) AS ?text) (sql:SAMPLE(?img) AS ?img) WHERE {
?book dct:format <http://data.deichman.no/format/Book> ;\n";

if ($audience != "Alle") $query .= "dct:audience <$audience> ;\n";
if ($format != "Alle") $query .= "<http://data.deichman.no/literaryFormat> <$format> ;\n";
if ($genre != "Alle") $query .= "<http://dbpedia.org/ontology/literaryGenre> <$genre> ;\n";
if ($subject != "Alle") $query .= "dct:subject <$subject> ;\n";

$query .= "dct:title ?title ;
foaf:depiction ?img .
?work fabio:hasManifestation ?book ;
rev:hasReview ?review .
?review rev:text ?text .
optional {?book dct:creator ?creator .
?creator foaf:name ?author .}
FILTER(lang(?text) = \"nb\")
} LIMIT 10";

// echo "<p>".urlencode($query)."</p>";

$root = melvilleQuery($query);
$list = $root->getElementsByTagName("solution");

echo "<table width='75%'>";

for ($n=0; $n<$list->length; $n++) {
	$solution = $list->item($n);
	$title = $solution->getElementsByTagName("value")->item(0)->nodeValue;
	$author = $solution->getElementsByTagName("value")->item(1)->nodeValue;
	$review = $solution->getElementsByTagName("value")->item(2)->nodeValue;
	$image = $solution->getElementsByTagName("value")->item(3)->getAttribute("rdf:resource");
	if (strpos($image, "bokkilden")>0) $image .= "&width=120";
	$image = str_replace("-L.jpg", "-M.jpg", $image);
	$image = str_replace("-S.jpg", "-M.jpg", $image);
	
	
	echo "<tr><td valign='top'><img src='$image'/></td>\n";
	echo "<td valign='top'><h3>$title</h3>($author)\n";
	echo "<p>$review</p></td></tr>\n";
}

echo "</table>";


?>

	</body>
</html>