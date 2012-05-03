#!/usr/bin/php -q 
<?

ini_set("auto_detect_line_endings", "true");

$location_data = array();

$other_data = array();
$other_data_output = array();

function load_data($filename, &$dst) {
	$header = array();

	$file = fopen($filename, "r");

	$l = 0;
	while(! feof($file)) {
		$line = fgets($file);
		$l++;

		$line_s = split(",", $line);

		$c = 0;
		$a = array();
		foreach($line_s as $v) {
			$v = trim($v);

			if($l == 1) {
				$header[$c] = $v;
			} else {
				if(count($line_s) != count($header))
					break;

				$a[$header[$c]] = $v;
			}

			$c++;
		}

		if($line > 1)
			$dst[strtotime($a['Time'])] = $a;
	}

	ksort($dst);

	fclose($file);
}

function inside_interval($lower, $upper, $array) {
	$r = array();

	foreach($array as $time=>$values) {
		if($time > $lower) {
			if($time < $upper)
				$r[$time] = $values;
			else
				break;
		}
	}

	return $r;
}

function location_interpolate($lat_1, $long_1, $lat_2, $long_2, $ratio) {
	$r = array();

	$r['Latitude'] = $lat_1 + (($lat_2 - $lat_1) * $ratio);
	$r['Longitude'] = $long_1 + (($long_2 - $long_1) * $ratio);

	return $r;
}

function step_locations($current, $next) {
	GLOBAL $other_data, $location_data, $other_data_output;

	$location_current = $location_data[$current];
	$location_next = $location_data[$next];

	foreach($other_data as $file=>$data) {
		$data_i = inside_interval($current, $next, $data);

		foreach($data_i as $time=>$values) {
			$ratio = $time / $current;
			$new_location = location_interpolate($location_current['Latitude'], $location_current['Longitude'], $location_next['Latitude'], $location_next['Longitude'], $ratio);

			if($new_location['Latitude'] == 0 || $new_location['Longitude'] == 0)
				continue;

			foreach($values as $column_title=>$value) {
				if(strtolower($column_title) == "time")	// don't export time
					continue;

				$a = array();
				$a['Latitude'] = $new_location['Latitude'];
				$a['Longitude'] = $new_location['Longitude'];
				$a[$column_title] = trim($value);				

				$other_data_output[$file][$column_title][$time] = $a;
			}
		}
	}
}

// load location data
load_data($_SERVER['argv'][1], $location_data);

// load other data
foreach(array_slice($_SERVER['argv'], 2) as $v) {
	$other_data[$v] = array();
	load_data($v, $other_data[$v]);
}

// merge and process data
foreach($location_data as $k=>$v) {
	$current = $k;

	next($location_data);
	$next = key($location_data);

	step_locations($current, $next);
}



foreach($other_data_output as $file=>$column) {
	foreach($column as $column_title=>$values) {
		ksort($values); // just to be sure--values are associative array with time as key

		echo "Longitude, Latitude, " . $column_title . "\n";

		foreach($values as $time=>$v) {
			echo $v['Longitude'] . "," . $v['Latitude'] . "," . $v[$column_title] . "\n";
		}

	}
}

/*
echo "</Document>\n";
echo "</kml>\n";
*/
?>
