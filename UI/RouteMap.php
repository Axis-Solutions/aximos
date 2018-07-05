<?php
require '../dbapi/DB.php';
require '../dbapi/ReportsAPI.php';

function getCoordinates($lat, $Lon) {
    
    $arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
);  
    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$Lon&sensor=true&key=AIzaSyBXYnIsUWziNd4F48t63Nh9irV2sskLPhY";
    $response = file_get_contents($url,false, stream_context_create($arrContextOptions));
    $json = json_decode($response, TRUE); //generate array object from the response from the web
   // echo $url;
    return $json;
}



$Routesheet = $_GET["RtShtNum"];
$TotalCustomer = sizeof(GetRouteCustomers($Routesheet));
$FirstInv = getFirstInvCoord($Routesheet);





$LastInv = GetLastInvCoord($Routesheet);

if(!empty($LastInv) && (!empty($FirstInv))){
    
    @$StartLat = $FirstInv[0]["GPSLatitude"];
@$StartLon = $FirstInv[0]["GPSLongitude"];
@$Origin = getCoordinates($StartLat, $StartLon);
@$OriginAddr = $Origin["results"][0]["formatted_address"];

$EndLon = $LastInv[0]["GPSLongitude"];
$EndLat = $LastInv[0]["GPSLatitude"];
$InvoiceNum = $LastInv[0]["InvoiceNum"];

$End = getCoordinates($EndLat, $EndLon);
$EndAddr = $End["results"][0]["formatted_address"];

$Addresses = array();
$Invoices = array();
$InvDet = GetWayPointsInvoices($Routesheet, "");
foreach ($InvDet as $Data) {
    $WayLat = $Data["GPSLatitude"];
    $WayLon = $Data["GPSLongitude"];
    $WayPointInTot = $Data["InvoiceTotal"];
	if($WayLat!="0.0"){
    $WayPointAddr = getCoordinates($WayLat, $WayLon);
    $WyPntAd = $WayPointAddr["results"][0]["formatted_address"];
    
	}
	else{
	$WyPntAd="Rekayi Tangwena Ave, Harare, Zimbabwe";
	}
	array_push($Addresses, $WyPntAd);
         array_push($Invoices, $WayPointInTot);
}
$Array["fdbk"] = $Addresses;
$Array["InvTot"] = $Invoices;
}
else{
    echo '<div class="alert alert-danger"> No GPS Information found for this loadsheet yet. Please ensure the GPS is activated for all your devices. Click <a href="manage_route_sheets.php">here</a> to return to loadsheets.</div>';
    die();
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
        <meta charset="utf-8">
        <title>Routemap</title>
        <style>
            #floating-panel {
                position: absolute;
                top: 10px;
                right: 2%;
                z-index: 5;
                background-color: #999;
                padding: 5px;
                border:#999;
                text-align: center;


                font-family: 'Roboto','sans-serif';
                line-height: 30px;
                padding-left: 10px;
            }

            #right-panel {
                font-family: 'Roboto','sans-serif';
                line-height: 30px;
                padding-left: 10px;
            }

            #right-panel select, #right-panel input {
                font-size: 15px;
            }

            #right-panel select {
                width: 100%;
            }

            #right-panel i {
                font-size: 12px;
            }
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
            }
            #map {
                height: 100%;
                float: left;
                width: 100%;
                height: 100%;
            }
          
            #directions-panel {
                margin-top: 10px;
                background-color: #FFEE77;
                height: 174px;
            }
        </style>

        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        <link rel="stylesheet" id="css-main" href="assets/css/oneui.css">

    </head>
    <body>
        <div  id="floating-panel">
            <button class="Summary btn btn-default">Summary</button>
            <!--<button class="btnfinancials btn btn-danger">Financials</button>-->
            <a class="btn btn-primary" href="manage_route_sheets.php">Back</a>
        </div>
        <div id="map"></div>
       <div class="modal summary" id="modal-sumary" tabindex="-1" role="dialog" aria-hidden="true">
          
           <div class="modal-dialog">
                  <div class="modal-content">
                 <div id="blk-cont" class="block-content">
                   
              </div>
           </div>
           </div>
        
</div>
        
        <div class="modal financials" id="modal-sumary" tabindex="-1" role="dialog" aria-hidden="true">
          
           <div class="modal-dialog">
                  <div class="modal-content">
                 <div id="ResponseDiv" class="block-content ResponseDiv">
                   
              </div>
           </div>
           </div>
        
</div>
        
       

        <script>
            function initMap() {
                var directionsService = new google.maps.DirectionsService;
                var directionsDisplay = new google.maps.DirectionsRenderer();
                var map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 6,
                    center: {lat: -17.837312, lng: 31.028369}
                });
                directionsDisplay.setMap(map);


                calculateAndDisplayRoute(directionsService, directionsDisplay);

            }

            var waypts = [];
            var adr = '<?php echo json_encode($Array); ?>';
            var parse = JSON.parse(adr);
            console.log(adr);

            for (var i = 0; i < parse.fdbk.length; i++) {

                waypts.push({
                    location: parse.fdbk[i],
                    stopover: true
                });


            }
            console.log(waypts);


            function calculateAndDisplayRoute(directionsService, directionsDisplay) {
                var waypts = [];
                var adr = '<?php echo json_encode($Array); ?>';
                var parse = JSON.parse(adr);


                for (var i = 0; i < parse.fdbk.length; i++) {

                    waypts.push({
                        location: parse.fdbk[i],
                        stopover: true
                    });


                }



    //give route summary
                directionsService.route({
                    origin: '<?php echo $OriginAddr; ?>',
                    destination: '<?php echo $EndAddr; ?>',
                    waypoints: waypts,
                    optimizeWaypoints: true,
                    travelMode: 'DRIVING'
                }, function (response, status) {

                    if (status === 'OK') {
                        directionsDisplay.setDirections(response);
                        var route = response.routes[0];
                    var summaryPanel = document.getElementById('blk-cont');
                    summaryPanel.innerHTML = '';
                    var TotDist = 0;
                    var NetSale = 0;
                    // For each route, display summary information.
                    for (var i = 0; i < route.legs.length; i++) {
                        var routeSegment = i + 1;
                        var total_distance = route.legs[i].distance.value;
                        TotDist += total_distance;
                        var distSumm = parseFloat(total_distance / 1000);
                        var SegRevPerKm = parseFloat(parse.InvTot[i] / distSumm).toFixed(2);
                        var numofcustomers = '<?php echo $TotalCustomer; ?>';
                        var numofsales = '<?php echo sizeof($InvDet); ?>';
                        
                        var hitrate = parseFloat((numofsales / numofcustomers) * 100);
                        summaryPanel.innerHTML += '<b>Route Segment: ' + routeSegment +
                                '</b><br>';
                        summaryPanel.innerHTML += route.legs[i].start_address + ' <b>to</b> ';
                        summaryPanel.innerHTML += route.legs[i].end_address + '<br>';
                        summaryPanel.innerHTML += route.legs[i].distance.text + '<br><br>';
                        if (i < (route.legs.length - 1)) {
                            NetSale += parseFloat(parse.InvTot[i]);
                            summaryPanel.innerHTML += '<b><font class="badge badge-success">Total Sales</font>: </b> <font class="badge badge-default">' + parse.InvTot[i];
                            summaryPanel.innerHTML += '</font> &nbsp &nbsp &nbsp &nbsp &nbsp    <b> <font class="badge badge-success">    Rev/Km  </font></b>:  <font class="badge badge-default"> $' + SegRevPerKm + '/km</font> <br> <br>';
                        }
                    }
                    var TotDistVal = parseFloat(TotDist / 1000)
                    var TotRevPerKm = parseFloat(NetSale / TotDistVal);
                    summaryPanel.innerHTML += '<b>ROUTE DISTANCE MATRIX </b><br>';
                    summaryPanel.innerHTML += '<b><font class="badge badge-success">Tot Dist  </font></b>:   <font class="badge badge-default">' + TotDistVal.toFixed(2) + ' km</font>';
                    summaryPanel.innerHTML += ' &nbsp &nbsp &nbsp &nbsp &nbsp    <b> <font class="badge badge-success"> Tot Sales </font></b>:  <font class="badge badge-default">$' + NetSale.toFixed(2);
                    summaryPanel.innerHTML += '</font> &nbsp &nbsp &nbsp &nbsp &nbsp    <b> <font class="badge badge-success">   Route rev/Km </font></b>:  <font class="badge badge-default">$' + TotRevPerKm.toFixed(2) + '/km</font>';
                    summaryPanel.innerHTML += '<br><br><b> CUSTOMER HIT RATE </b><br>';
                    summaryPanel.innerHTML += '<b><font class="badge badge-success"># of Customers  </font></b>:  <font class="badge badge-default">' + numofcustomers+'</font>';
                    summaryPanel.innerHTML += ' &nbsp &nbsp &nbsp &nbsp &nbsp    <b> <font class="badge badge-success"> Customers Sold to </font></b>:  <font class="badge badge-default">' + numofsales;
                    summaryPanel.innerHTML += '</font> &nbsp &nbsp &nbsp &nbsp &nbsp    <b> <font class="badge badge-success"> Customer hit Rate </font></b>:  <font class="badge badge-default">' + hitrate.toFixed(2) + '%</font><br><br>';
                 console.log(TotDistVal);
                          } else {
                        window.alert('Directions request failed due to ' + status);
                    }
                });
            }
        </script>
        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBXYnIsUWziNd4F48t63Nh9irV2sskLPhY&callback=initMap">
        </script>
        <script src="assets/js/core/jquery.min.js"></script>
        <script src="assets/js/core/bootstrap.min.js"></script>
        <script>
           $(document).ready(function () {
             //  $("#right-panel").hide();
               $(".Summary").click(function (e) {
                   e.preventDefault();
                   $('.summary').modal("show");
               });
              /* 
               $.post('../report_engines/SalesLoadsheetMaps.php',{
            Lod:'<?php echo $Routesheet; ?>'            
        },
        function(response){
            $(".ResponseDiv").html(response);
           
           console.log(response);
        });
             
               
               $(".btnfinancials").click(function(){
         $(".financials").modal("show");
    });*/
           });
        </script>
    </body>
</html>