#!/usr/bin/php -q 
<?

$latitude = null;
$longitude = null;
$altitude = null;
$time = null;

$in_element = null;

function characterData($parser, $data) {
	GLOBAL $latitude, $longitude, $altitude, $time;
	GLOBAL $in_element;

	switch($in_element) {
		case "TIME":
			$time = $data;
			break;

		case "LATITUDEDEGREES":
			$latitude = $data;
			break;

		case "LONGITUDEDEGREES":
			$longitude = $data;
			break;

		case "ALTITUDEMETERS":
			$altitude = $data;
			break;		
	}
}

function startElement($parser, $name, $attrs) {
	GLOBAL $in_element;

	$in_element = $name;
}

function endElement($parser, $name) {
	GLOBAL $latitude, $longitude, $altitude, $time;
	GLOBAL $in_element;

	$in_element = null;

	if($name == "TRACKPOINT")
		echo "$time, $latitude, $longitude, $altitude\n";

}

$xml_parser = xml_parser_create("UTF-8");

xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");

xml_parse($xml_parser, file_get_contents($_SERVER['argv'][1]));
xml_parser_free($xml_parser);

?>
