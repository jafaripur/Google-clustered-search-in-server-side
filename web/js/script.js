/**
 * Clustered search over server side for shows items in google map
 * @author Araz J <mjafaripur@yahoo.com>
 */
var map;
var markersArray = new Array();
var gmap_ajax_obj;
var first_time_loading = true;
function initialize() {
    var mapOptions = {
        zoom: 4,
        center: new google.maps.LatLng(50.22916, 8.84847),
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
    google.maps.event.addListener(map, 'zoom_changed', function ()
    {
        getBubble();
    });
    google.maps.event.addListener(map, 'dragend', function ()
    {
        getBubble();
    });
    google.maps.event.addListener(map, 'idle', function ()
    {
        if (first_time_loading)
        {
            first_time_loading = false;
            getBubble();
        }
    });
}
google.maps.event.addDomListener(window, 'load', initialize);
/**
 * Get markers list to show marker
 * @param float lat_min
 * @param float lat_max
 * @param float lng_min
 * @param float lng_max
 * @returns {undefined}
 */
function getMarker(lat_min, lat_max, lng_min, lng_max){
    if (gmap_ajax_obj)
        gmap_ajax_obj.abort();
    $('#map_loading').show();
    deleteOverlays();
    gmap_ajax_obj = jQuery.ajax({
        type: 'POST',
        url: "marker.php",
        data: {min_lat: lat_min, max_lat: lat_max, min_lng: lng_min, max_lng: lng_max},
        dataType: 'JSON',
        async: true,
        cache: false,
        success: function (data)
        {
            for (var i = 0; i < data.markers.length; i++)
            {
                var marker = new google.maps.Marker({
                    position: new google.maps.LatLng(data.markers[i].latitude, data.markers[i].longitude),
                    map: map
                });
                markersArray.push(marker);
            }
        },
        complete: function () {
            $('#map_loading').hide();
        }
    });
}
/**
 * clustered search over map
 * @returns {Boolean}
 */
function getBubble() {
    var zoom = map.getZoom();
    var bounds = map.getBounds();
    var ne = bounds.getNorthEast();
    var sw = bounds.getSouthWest();
    var lat_max = ne.lat();
    var lat_min = sw.lat();
    var lng_min = sw.lng();
    var lng_max = ne.lng();
    // Show marker instead bubbles if map zoom more than 14
    if(zoom >= 14)
    {
        getMarker(lat_min, lat_max, lng_min, lng_max);
        return false;
    }
    if (gmap_ajax_obj)
        gmap_ajax_obj.abort();
    $('#map_loading').show();
    deleteOverlays(); //clear map from makers or bubbles
    gmap_ajax_obj = jQuery.ajax({
        type: 'POST',
        url: "cluster.php",
        data: {min_lat: lat_min, max_lat: lat_max, min_lng: lng_min, max_lng: lng_max, zoom: zoom},
        dataType: 'JSON',
        async: true,
        cache: false,
        success: function (data)
        {
            for (var i = 0; i < data.bubbles.length; i++)
            {
                var bubble_class = get_bubble_class(data.bubbles[i].count);
                var marker = new RichMarker({
                    position: new google.maps.LatLng(data.bubbles[i].lat, data.bubbles[i].lng),
                    map: map,
                    content: "<div id=\"cnt" + i + "\" class=\"bubble_container " + bubble_class + "\" onclick=\"map_zoom_center('" + data.bubbles[i].lat + "', '" + data.bubbles[i].lng + "')\">" + data.bubbles[i].count + "</div>"
                });
                markersArray.push(marker);
            }
        },
        complete: function () {
            $('#map_loading').hide();
        }
    });
}
/**
 * Clear map markers
 * @returns {undefined}
 */
function deleteOverlays()
{
    if (markersArray)
    {
        for (i = 0; i < markersArray.length; i++)
            markersArray[i].setMap(null);
        markersArray.length = 0;
    }
}
/**
 * detect class of bubbles marker by count of them
 * @param {type} countOfMarkers
 * @returns {String}
 */
function get_bubble_class(countOfMarkers)
{
    var bubble_class = 'bubble1';
    if (countOfMarkers < 100)
        bubble_class = 'bubble2';
    else if (countOfMarkers < 1000)
        bubble_class = 'bubble3';
    else if (countOfMarkers < 10000)
        bubble_class = 'bubble4';
    else if (countOfMarkers >= 10000)
        bubble_class = 'bubble5';

    return bubble_class;
}
/**
 * Zoom to marker position and set map to center on clicking in bubbles marker
 * @param float lat
 * @param float lng
 * @param integer add_zoom
 * @returns {undefined}
 */
function map_zoom_center(lat, lng, add_zoom)
{
    if (!add_zoom)
        add_zoom = 2;

    var zoom = map.getZoom();
    var new_zoom = zoom + add_zoom;

    map.setZoom(new_zoom);
    map.setCenter(new google.maps.LatLng(lat, lng));
}