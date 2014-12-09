<?php
/**
 * Search with clustered mode file and return JSON formated output
 */
include __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cluster.php';

$zoom = filter_input(INPUT_POST, 'zoom');
$minLat = filter_input(INPUT_POST, 'min_lat');
$maxLat = filter_input(INPUT_POST, 'max_lat');
$minLng = filter_input(INPUT_POST, 'min_lng');
$maxLng = filter_input(INPUT_POST, 'max_lng');

$cluster = new Cluster();
$results = $cluster->bubblesSearch($minLat, $maxLat, $minLng, $maxLng, $zoom, 0);

echo json_encode(array(
    'bubbles' => $results,
));