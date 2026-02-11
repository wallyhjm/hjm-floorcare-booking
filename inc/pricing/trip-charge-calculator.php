<?php

function hjm_floorcare_calculate_trip_charge($miles)
{
    $rates = hjm_floorcare_trip_charge_rates();

    foreach ($rates as $max_miles => $price) {
        if ($miles <= $max_miles) {
            return $price;
        }
    }

    return end($rates);
}
