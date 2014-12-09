<?php
/**
 * Search for markers and return JSON formated output
 */
include __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cluster.php';

$minLat = filter_input(INPUT_POST, 'min_lat');
$maxLat = filter_input(INPUT_POST, 'max_lat');
$minLng = filter_input(INPUT_POST, 'min_lng');
$maxLng = filter_input(INPUT_POST, 'max_lng');

$cluster = new Cluster();
$results = $cluster->markerSearch($minLat, $maxLat, $minLng, $maxLng);

echo json_encode(array(
    'markers' => $results,
));