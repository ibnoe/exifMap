<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>exifMap</title>


<!-- stylesheets -->
<link rel="stylesheet" href="lib/css/style.css" type="text/css" media="screen" />

<!-- jQuery and Google Maps API -->
<script type="text/javascript" src="lib/js/jquery.js"></script>
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>

<?php
	// load lib/functions.php
	REQUIRE_ONCE('lib/functions.php');
?>

<?php
// set the photo directory in a variable for later use
$handler = opendir("photos");

// open directory and read through the filenames
while ($file = readdir($handler)) {

	// if file is jpg then add it to the list
	// this searches for jpg extensions only. this will need to be modified to include other images but as most cameras capture in jpg I don't see the point
	if(!is_dir($file) && stristr($file,".jpg") !== FALSE) {
		$results[] = $file;
	}
}
	   
	// close connection to the directory now we have the relevant data
	closedir($handler);

	// used for testing - prints out an array of all images within the given directory
	//print_r ($results);	
?>

<?php
// loop through the array and check each image
for ($i = 0; $i < count($results); ++$i) {
    
    // iterate through image files and read them one by one -- this is ultimately slow and needs to be improved. caching images might be an idea
    $PhotoExif = exif_read_data('photos/'.$results[$i]);

    // this is used for testing purposes. it lists all available EXIF data from each image
    //print_r($PhotoExif);


	// date and time photo was taken - NOT the computer modified time
	// the problem with the EXIF time is that it is in a format that date() does not like, so we have to grab the time and modify it so date() can process it
	$exifString = $PhotoExif["DateTimeOriginal"];
	$exifPieces = explode(":", $exifString);
	$newExifString = $exifPieces[0] . "-" . $exifPieces[1] . "-" . $exifPieces[2] . ":" .
		$exifPieces[3] . ":" . $exifPieces[4];
	$exifTimestamp = strtotime($newExifString);
	$intTakenDate = date('F j, Y @ H:i', $exifTimestamp);

	// image file properties
	$intFileName = $PhotoExif["FileName"];
	$intFileSize = $PhotoExif["FileSize"];

	// camera/capture device properties
	$intMake = $PhotoExif["Make"];
	$intModel = $PhotoExif["Model"];


	if (isset($PhotoExif["GPSLatitude"][0], $PhotoExif["GPSLongitude"][0], $PhotoExif["GPSLatitudeRef"], $PhotoExif["GPSLongitudeRef"]) == TRUE) {

	   	// grab and convert the gps units -- NEED TO CHAGE THIS TO CHECK IF THE IMAGE HAS THE REQUIRED DATA
	    $intLatDeg = GpsDivide($PhotoExif["GPSLatitude"][0]);
		$intLatMin = GpsDivide($PhotoExif["GPSLatitude"][1]);
		$intLatSec = GpsDivide($PhotoExif["GPSLatitude"][2]);
					 
		$intLongDeg = GpsDivide($PhotoExif["GPSLongitude"][0]);
		$intLongMin = GpsDivide($PhotoExif["GPSLongitude"][1]);
		$intLongSec = GpsDivide($PhotoExif["GPSLongitude"][2]);
					        
		// round to 5 = approximately 1 meter accuracy
		$intLatitude = round(DegToDec($PhotoExif["GPSLatitudeRef"],
			$intLatDeg,$intLatMin,$intLatSec),5);
					        
		$intLongitude = round(DegToDec($PhotoExif["GPSLongitudeRef"],
			$intLongDeg,$intLongMin,$intLongSec), 5);


		// create the markers array that will contain all data to display on the map

		// all info will appear in the array in this order:
		// 0 filename
		// 1 filesize
		// 2 latitude, longitude
		// 3 taken date
		// 4 make
		// 5 model

		$markers[] = array("$intFileName","$intFileSize","$intLatitude,$intLongitude","$intTakenDate","$intMake","$intModel");

	}

	if (!isset($PhotoExif["GPSLatitude"][0], $PhotoExif["GPSLongitude"][0], $PhotoExif["GPSLatitudeRef"], $PhotoExif["GPSLongitudeRef"]) == TRUE) {
		$markers2[] = array("$intFileName","$intFileSize","$intTakenDate","$intMake","$intModel");
		foreach ($markers2 as $key => $value) { 
			//echo $intFileName;
	    } 

	}

}

// used for testing purposes - will be removed in final draft
//print_r($markers);
//print_r($PhotoExif);
?>

<script type="text/javascript">
//<![CDATA[
  function initialize() {

  	var photographs = [
  	<?php foreach ($markers as $key => $value) { ?>
	['<?php echo $value[0] ?>', <?php echo $value[2]; ?>, 1, '<a href="photos/<?php echo $value[0] ?>" target="_blank"><img src="photos/<?php echo $value[0] ?>" width="200px" height="180px" /></a><br />Taken on <?php echo $value[3] ?> with <?php echo $value[4] ?> <?php echo $value[5] ?>'],
    <?php } ?>
    ];

    var myLatlng = new google.maps.LatLng(54.686534,-4.416504);
    
    var myOptions = {
      zoom: 6,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP 
    }

    var map = new google.maps.Map(document.getElementById("map"), myOptions);

	setMarkers(map, photographs);		
	setZoom(map, photographs);

	infowindow = new google.maps.InfoWindow({
		content: "Loading..."
	});

} //end initialise (spelled with a S)

/* This functions sets the markers (array) */
function setMarkers(map, markers) {
	for (var i = 0; i < markers.length; i++) {

		var photograph = markers[i];
		var photographLatLng = new google.maps.LatLng(photograph[1], photograph[2]);

		var marker = new google.maps.Marker({
			position: photographLatLng,
			map: map,
			title: photograph[0],
			zIndex: photograph[3],
			html: photograph[4],
			icon: 'lib/img/markers/photo.png',
			// Markers drop on the map
			animation: google.maps.Animation.DROP
		});

		google.maps.event.addListener(marker, "click", function () {
			infowindow.setContent(this.html);
			infowindow.open(map, this);
		});
	}
}

/* Set the zoom to fit comfortably all the markers in the map */
function setZoom(map, markers) {
	var boundbox = new google.maps.LatLngBounds();
	for ( var i = 0; i < markers.length; i++ )
	{
	  boundbox.extend(new google.maps.LatLng(markers[i][1], markers[i][2]));
	}
	map.setCenter(boundbox.getCenter());
	map.fitBounds(boundbox);
}
//]]>
</script>

</head>

<body onload="initialize()">


<div id="map"></div>


</body>
</html> 

