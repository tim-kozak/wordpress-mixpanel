<?php

/**
 * Mixpanel tracker
 */

//helpers
include_once 'mixpanel/tools.php';

//check if mixpanel need to start
if (mx_is_ok()) {
    //Tracker class
    include_once 'mixpanel/MXTracker.php';
    //Starting up
    MXTracker::instance('{-----your token here-----}');
    //Tracking file with all events
    include_once 'mixpanel/tracking_file.php';
}