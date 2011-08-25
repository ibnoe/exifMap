<?php
// Convert degrees to decimal
function DegToDec($strRef,$intDeg,$intMin,$intSec)
{
        $arrLatLong = array();
        $arrLatLong["N"] = 1;
        $arrLatLong["E"] = 1;
        $arrLatLong["S"] = -1;
        $arrLatLong["W"] = -1;        
 
        return ($intDeg+((($intMin*60)+($intSec))/3600)) * $arrLatLong[$strRef];
}

// Prevent division by zero
function GpsDivide($strGps)
{
        $arrGps = explode("/",$strGps);
        
        if (!$arrGps[0] || !$arrGps[1]) {
                return 0;
        }
        else
        {
                return $arrGps[0]/$arrGps[1];
        }
}
?>