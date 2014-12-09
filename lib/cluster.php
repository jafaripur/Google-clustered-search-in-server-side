<?php

/**
 * Clustered search over map in server side
 * @author Araz J <mjafaripur@yahoo.com>
 */
class Cluster extends PDO {

    /**
     * constrcutor of class for connect to database
     */
    public function __construct() {
        $options = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true,
        );
        parent::__construct('mysql:host=localhost;dbname=cluster', 'jafaripur', '123456', $options);
    }
    /**
     * Fetch latitude and longitude from database for shows markers
     * 
     * @param float $minLat Minimum latitude
     * @param float $maxLat Maximum latitude
     * @param float $minLng Minimum langitude
     * @param float $maxLng Maximum langitude
     * @return array
     */
    public function markerSearch($minLat, $maxLat, $minLng, $maxLng) {
        $query = "SELECT `latitude`, `longitude`";
        $query .= " FROM `position` AS p ";
        $query .= " WHERE p.`latitude` BETWEEN :min_lat AND :max_lat AND p.`longitude` BETWEEN :min_lng AND :max_lng";
        
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':min_lat' => $minLat,
            ':max_lat' => $maxLat,
            ':min_lng' => $minLng,
            ':max_lng' => $maxLng,
        ));
        
        return $stmt->fetchAll();
    }
    /**
     * Search clustered markers
     * 
     * @param float $minLat Minimum latitude
     * @param float $maxLat Maximum latitude
     * @param float $minLng Minimum langitude
     * @param float $maxLng Maximum langitude
     * @param integer $zoomLevel zoom level of map
     * @param integer $number count of decimal point in AVG of latitude and longitude
     * @return array
     */
    public function bubblesSearch($minLat, $maxLat, $minLng, $maxLng, $zoomLevel, $number = 1) {
        
        $multiplyNumber = (1 / 500) * pow(2.4, $zoomLevel);
        $query = "SELECT COUNT(*) AS `count`, AVG(p.`latitude`) AS `lat`, AVG(p.`longitude`) AS `lng`, FORMAT(p.`latitude` *" . $multiplyNumber . "," . $number . ") AS `g_lt`, FORMAT(p.`longitude` *" . $multiplyNumber . "," . $number . ") AS `g_ln`";
        $query .= " FROM `position` AS p ";
        $query .= " WHERE p.`latitude` BETWEEN :min_lat AND :max_lat AND p.`longitude` BETWEEN :min_lng AND :max_lng";
        $query .= " GROUP BY `g_lt`,`g_ln`";
        //$query .= " ORDER BY `g_lt`,`g_ln` DESC";
        
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':min_lat' => $minLat,
            ':max_lat' => $maxLat,
            ':min_lng' => $minLng,
            ':max_lng' => $maxLng,
        ));
        
        //return $this->mergeBubbles($zoomLevel, $stmt->fetchAll());
        return $stmt->fetchAll();
    }
    /**
     * Merge bubbles
     * 
     * @param integer $zoomLevel
     * @param array $data fetched data from database with @see bubblesSearch()
     * @param float $a Precision of medium radius for merging
     * @return array
     */
    protected function mergeBubbles($zoomLevel, $data, $a = 1.1) {
        $data2 = $data;
        $minRadius = $a / (pow(2, $zoomLevel));

        $merge = array();

        foreach ($data as $key => $value) {
            $merged = false;

            foreach ($data2 as $key2 => $value2) {
                /** ignore duplicate bubble * */
                if ($key == $key2) {
                    unset($data2[$key2]);
                    continue;
                }

                $result = acos(cos(deg2rad($value['lat'])) * cos(deg2rad($value2['lat'])) * cos(deg2rad($value2['lng']) - deg2rad($value['lng'])) + sin(deg2rad($value['lat'])) * sin(deg2rad($value2['lat'])));

                if ($result < $minRadius) {
                    $merged = true;
                    $merge[] = array($key, $key2);

                    unset($data2[$key2]);
                }
            }
        }

        foreach ($merge as $key => $value) {
            if (!isset($data[$value[0]]))
                continue;

            $count = $data[$value[0]]['count'] + $data[$value[1]]['count'];

            $av_lat = ($data[$value[0]]['lat'] + $data[$value[1]]['lat']) / 2;
            $av_lng = ($data[$value[0]]['lng'] + $data[$value[1]]['lng']) / 2;

            $nvalue = array();
            $nvalue['count'] = $count;
            $nvalue['lat'] = $av_lat;
            $nvalue['lng'] = $av_lng;

            $data[$value[0]] = $nvalue;

            unset($data[$value[1]]);
        }

        //rsort($data);
        return $data;
    }

}
