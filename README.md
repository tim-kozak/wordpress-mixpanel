# Mixpanel tracker for wordpress and woocommerce analysis 
Wrapper around Mixpanel PHP and JS libs for advanced events tracking

Main point is to have all events in one place will it be frontend events or backend or ajax triggered.
You can get the thing when you look into MXTracker methods.

We inserting js library to each page automatically along with PHP library for each request.
Doing `identify` automatically on frontend and backend. 
And you can chose place where you want to track particular event or property.

There is:
 - api_track_event
 - js_track_event
 - api_set_super_properties
 - js_set_super_properties
 - etc.
 
## Setup steps
1. Clone this files to your theme folder
2. Copy your Mixpanel token for next step
3. Insert this code in your `functions.php`
<pre>
 //helpers
 include_once 'mixpanel/tools.php';
 
 //check if mixpanel need to start (skip on cron or 404 etc.)
 if (mx_is_ok()) {
     //Tracker class
     include_once 'mixpanel/MXTracker.php';
     //Starting up
     MXTracker::instance('{-----your token here-----}');
     //Tracking file with all events
     include_once 'mixpanel/tracking_file.php';
 }
 </pre>
4. Update `tools.php` - change HEAD_SCRIPT_HANDLER to any script you equeued with `wp_enqueue_script()`
5. Put all your call to  `mixpanel/tracking_file.php`

