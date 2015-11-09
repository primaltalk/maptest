<?php
/**
 * Google Maps Javascript API Test
 *
 * This is a quick implementation of the Google Maps Javascript API, taking a
 * series of coordinates (most recent for a given UID), finding the center
 * of the coordinates to center the map on, and placing markers for each
 * coordinate on the map.  Each marker has the UID text within.
 *
 */

class mapTest {

    public function run() {
        // Connect to the database and retrieve the coordinates we need.  We'd normally
        // place a framework around this and do all sorts of fancy stuff with settings,
        // but as the header says, this is a quick implementation.
        
        $dbh = new PDO('mysql:host=localhost;dbname=<database name goes here>', '<username>', '<password>');
        
        $query = "SELECT mt1.* "
            . "FROM mobile_tech AS mt1 "
            . "LEFT OUTER JOIN mobile_tech AS mt2 "
            . "ON mt1.uid = mt2.uid AND mt1.`timestamp` < mt2.`timestamp` "
            . "WHERE mt2.uid IS NULL";
        
        $coordinates = array();
        $uid_coordinates = array();
        foreach( $dbh->query( $query ) as $row )
        {
            $coordinates[] = array($row['latitude'], $row['longitude'] );
            $uid_coordinates[$row['uid']] = array($row['latitude'], $row['longitude'] );
        }
        
        // Now we have the coordinates, let's get the center.
        $center = $this->getCenterFromDegrees( $coordinates );
        
        return array( 'center' => $center, 'coordinates' => $uid_coordinates );
    }

    /**
     * Get a center latitude,longitude from an array of like geopoints
     *
     * @param array data 2 dimensional array of latitudes and longitudes
     * For Example:
     * $data = array
     * (
     *   0 = > array(45.849382, 76.322333),
     *   1 = > array(45.843543, 75.324143),
     *   2 = > array(45.765744, 76.543223),
     *   3 = > array(45.784234, 74.542335)
     * );
     *
     * Thanks to Gio: http://stackoverflow.com/questions/6671183/calculate-the-center-point-of-multiple-latitude-longitude-coordinate-pairs
    */
    private function getCenterFromDegrees($data)
    {
        if (!is_array($data)) return FALSE;
    
        $num_coords = count($data);
    
        $X = 0.0;
        $Y = 0.0;
        $Z = 0.0;
    
        foreach ($data as $coord)
        {
            $lat = $coord[0] * pi() / 180;
            $lon = $coord[1] * pi() / 180;
    
            $a = cos($lat) * cos($lon);
            $b = cos($lat) * sin($lon);
            $c = sin($lat);
    
            $X += $a;
            $Y += $b;
            $Z += $c;
        }
    
        $X /= $num_coords;
        $Y /= $num_coords;
        $Z /= $num_coords;
    
        $lon = atan2($Y, $X);
        $hyp = sqrt($X * $X + $Y * $Y);
        $lat = atan2($Z, $hyp);
    
        return array($lat * 180 / pi(), $lon * 180 / pi());
    }

}

$mapTest = new mapTest();
try {
    echo json_encode( $mapTest->run() );
} catch( PDOException $e )
{
    die( $e->getMessage() );
}

?>
