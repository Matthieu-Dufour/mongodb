<?php
    function appel($url){
        // $opts = array('http' => array('proxy' => 'tcp://www-cache.iutnc.univ-lorraine.fr:3128/', 'request_fulluri' => true));

        // $context = stream_context_create($opts);

        // $str = file_get_contents($url, false, $context);
        
        $str = file_get_contents($url);
        if(http_response_code() === 200){
            return $str;
        } else {
            echo "Errors";
            return null;
        }
    }

    //coords GPS de nancy
    $lat = 48.6843900;
    $lon = 6.1849600;
    $velos = appel('https://geoservices.grand-nancy.org/arcgis/rest/services/public/VOIRIE_Parking/MapServer/0/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=nom%2Cadresse%2Cplaces%2Ccapacite&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=4326&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=pjson');

    $json_velos = json_decode($velos);
    $markers = [];


    
    $client = new MongoDB\Client;
    
    $nancydb = $client->nancydb;

    $velos = $nancydb->createCollection('velos');

    for($nbInfos = 0; $nbInfos < sizeof($json_velos->{'features'}); $nbInfos++){

        $marker_lat = $json_velos->{'features'}[$nbInfos]->{'geometry'}->{'y'};
        $marker_lon = $json_velos->{'features'}[$nbInfos]->{'geometry'}->{'x'};
        
        $marker_nom = $json_velos->{'features'}[$nbInfos]->{'attributes'}->{'NOM'};
        $marker_places= $json_velos->{'features'}[$nbInfos]->{'attributes'}->{'PLACES'};
        $marker_capacite = $json_velos->{'features'}[$nbInfos]->{'attributes'}->{'CAPACITE'};

        $tableau = [$marker_lon,$marker_lat,$marker_nom,$marker_places,$marker_capacite];
        array_push($markers, $tableau);

        $document = array( "latitude" => $marker_lat, "longitude" => $marker_lon, "nom" => $marker_nom, "places" => $marker_places, "capacite" => $marker_capacite);
        $velos->insert($document);
    }

    $jsonmarkers = json_encode($markers);

    $batiments = $nancydb->createCollection('batiments');

    $cathedrale = array("nom" => "cathedrale", "latitude" => 48.684357299999995, "longitude" => 6.178623099999999, "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");
    $json_cathedrale = json_encode($cathedrale);

    $stSebastien = array("nom" => "Saint sebastien", "latitude" => 48.68825, "longitude" => 6.17944, "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");
    $json_stSebastien = json_encode($stSebastien);
    
    $IUT = array("nom" => "IUT Charlemagne", "latitude" => 48.6828361, "longitude" => 6.161133999999947, "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");
    $json_IUT = json_encode($IUT);

    $batiments->insert($cathedrale);
    $batiments->insert($stSebastien);
    $batiments->insert($IUT);

$html = <<<HTML
        <!doctype html>
        <html>
        <head>
            <style>
                #map { height: 100vh; width: 100vh; }
            </style>
             <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css"
   integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
   crossorigin=""/>
   <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"
   integrity="sha512-nMMmRyTVoLYqjP9hrbed9S+FzjZHW5gY1TWCHA5ckwXZBadntCNs8kEqAWdrb9O7rxbCaA4lKTIWjDXZxflOcA=="
   crossorigin=""></script>
        </head>
        <body>

        <div id="map"></div>

        <script type="text/javascript">

            markers = {$jsonmarkers};
            cathedrale = {$json_cathedrale};
            stseb = {$json_stSebastien}
            iut = {$json_IUT}

            //coords nancy
            var xy = [{$lat}, {$lon}];

            // création de la map avec niveau de zoom
            var map = L.map('map').setView(xy, 13);
            
            // création du calque images
            L.tileLayer('http://korona.geog.uni-heidelberg.de/tiles/roads/x={x}&y={y}&z={z}', {
                maxZoom: 20
            }).addTo(map);

            L.marker([ cathedrale.latitude , cathedrale.longitude ]).addTo(map).bindPopup("<h3>" + cathedrale.nom + "</h3>");
            
            L.marker([ stseb.latitude , stseb.longitude ]).addTo(map).bindPopup("<h3>" + stseb.nom + "</h3>");
            
            L.marker([ iut.latitude , iut.longitude ]).addTo(map).bindPopup("<h3>" + iut.nom + "</h3>");
            

            markers.forEach(function(marker) {
            L.marker([ marker[1] , marker[0] ]).addTo(map).bindPopup("<h3>" + marker[2] + "</h3><br/>" + "<h3>Places : " + marker[3] + "</h3><br/>" +"<h3>Capacite : " + marker[4] + "</h3>" );
            })
            

        </script>
HTML;

    
echo $html . "</body></html>";

