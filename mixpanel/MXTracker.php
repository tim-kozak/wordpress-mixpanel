<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'MXTracker' ) ) {

    class MXTracker
    {
        private static $instance = null;

        public $token;
        public $current_user;

        private $mixpanel;
        private $is_tracking;
        private $identifier;
        private $alias_connection_holder_key;

        private function __construct() {}
        private function __clone() {}
        private function __sleep() {}
        private function __wakeup() {}

        public static function instance( $token = null ) {
            if ( is_null(self::$instance) ) {
                self::$instance = new MXTracker();
                self::$instance->setup($token);
                self::$instance->includes();
                self::$instance->init();
                self::$instance->set_user();
            }

            return self::$instance;
        }

        //region SETUP ----------------

        private function setup($token) {
            // API Key.
            $this->token = $token;
            $this->alias_connection_holder_key = 'mixpanel_distinct_id_key';
        }

        private function includes() {
            // Mixpanel PHP Library.
            include_once 'libs/mixpanel-php/lib/Mixpanel.php';

            // Mixpanel JS Library.
            $data = '<script type="text/javascript">(function(e,a){if(!a.__SV){var b=window;try{var c,l,i,j=b.location,g=j.hash;c=function(a,b){return(l=a.match(RegExp(b+"=([^&]*)")))?l[1]:null};g&&c(g,"state")&&(i=JSON.parse(decodeURIComponent(c(g,"state"))),"mpeditor"===i.action&&(b.sessionStorage.setItem("_mpcehash",g),history.replaceState(i.desiredHash||"",e.title,j.pathname+j.search)))}catch(m){}var k,h;window.mixpanel=a;a._i=[];a.init=function(b,c,f){function e(b,a){var c=a.split(".");2==c.length&&(b=b[c[0]],a=c[1]);b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments,0)))}}var d=a;"undefined"!==typeof f?d=a[f]=[]:f="mixpanel";d.people=d.people||[];d.toString=function(b){var a="mixpanel";"mixpanel"!==f&&(a+="."+f);b||(a+=" (stub)");return a};d.people.toString=function(){return d.toString(1)+".people (stub)"};k="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");for(h=0;h<k.length;h++)e(d,k[h]);a._i.push([b,c,f])};a.__SV=1.2;b=e.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";c=e.getElementsByTagName("script")[0];c.parentNode.insertBefore(b,c)}})(document,window.mixpanel||[]);mixpanel.init("'.$this->token.'");</script>';
            mx_add_inline_script_to_head( $data, 1 );
        }

        private function init() {

            // If we don't have a key, bail.
            if ( ! empty( $this->token ) ) {
                $this->is_tracking = true;
            } else {
                return;
            }

            // Setup the Mixpanel instance.
            $this->mixpanel = Mixpanel::getInstance( $this->token );
        }

        //endregion

        //region PUBLIC ----------------

        public function set_user($user = null) {

            // WP_User or user_id
            if ( $user ) {
                $this->setup_user($user);
                return;
            }

            // user is logged in
            if ( is_user_logged_in() ) {

                $user = get_user_by( 'id', get_current_user_id() );
                $this->setup_user($user);
                return;

            }

            $this->current_user = null;
            $this->identifier = null;
            $this->identify();
        }

        public function api_track_event( $event_name, $properties = array() ) {

            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // remove all blank properties
            if ( isset( $properties[''] ) ) {
                unset( $properties[''] );
            }

            // track the event
            $this->api()->track( $event_name, $properties );
        }

        public function js_track_event( $event_name, $properties = '' ) {

            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // json encode properties if they exist
            if ( is_array( $properties ) ) {

                // remove blank properties
                if ( isset( $properties[''] ) ) {
                    unset( $properties[''] );
                }

                $properties = ', ' . json_encode( $properties );
            }

            $event = 'mixpanel.track( "'.esc_html( $event_name ).'"'.$properties.' );';
            mx_add_inline_script_to_head( $event, 99 );
        }

        public function js_track_event_with_listener( $event_name, $properties = '', $js_event_name ) {

            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // json encode properties if they exist
            if ( is_array( $properties ) ) {

                // remove blank properties
                if ( isset( $properties[''] ) ) {
                    unset( $properties[''] );
                }

                $properties = ', ' . json_encode( $properties );
            }

            $event = '
            var html = document.getElementsByTagName("html")[0],
                mx_events = {};
            mx_events.'. $js_event_name .' = new CustomEvent("'. $js_event_name .'");
            html.addEventListener("'. $js_event_name .'", function() {
                mixpanel.track( "'.esc_html( $event_name ).'"'.$properties.' );
            });
            ';

            mx_add_inline_script_to_head( $event, 99 );
        }

        public function api_set_user_attributes ( $attributes ) {

            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // remove all blank properties
            if ( isset( $attributes[''] ) ) {
                unset( $attributes[''] );
            }
            $this->people()->set( $this->identifier, $attributes );
        }

        public function js_set_user_attributes ( $attributes ) {

            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // remove all blank properties
            if ( isset( $attributes[''] ) ) {
                unset( $attributes[''] );
            }
            $attributes = json_encode( $attributes );

            mx_add_inline_script_to_head('mixpanel.people.set('.$attributes.');',3);
        }

        public function api_set_super_properties ( $properties ) {
            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // remove all blank properties
            if ( isset( $properties[''] ) ) {
                unset( $properties[''] );
            }
            $this->events()->registerAll( $properties );
        }

        public function js_set_super_properties ( $properties ) {
            // Verify tracking status
            if ( !$this->is_tracking ) {
                return;
            }

            // remove all blank properties
            if ( isset( $properties[''] ) ) {
                unset( $properties[''] );
            }
            $properties = json_encode( $properties );

            mx_add_inline_script_to_head('mixpanel.register('.$properties.');',3);
        }

        //endregion

        //region PRIVATE --------------

        private function setup_user($user) {
            $this->alias_user($user);
            $this->current_user = $user;
            $this->identifier = $user->user_email;
            $this->identify();
        }

        private function identify() {
            $identity = $this->identifier ? $this->identifier : $this->get_distinct_id();
            if ($identity) {
                $this->api()->identify($identity);
                mx_add_inline_script_to_head('mixpanel.identify("'.$identity.'")',2);
            }
        }

        private function alias_user($user) {
            $status = get_user_meta($user->ID, $this->alias_connection_holder_key, true);
            if (!$status && $this->get_distinct_id()) {
                //not aliased yet
                $this->api()->createAlias( $this->get_distinct_id(), $user->user_email );
                update_user_meta($user->ID, $this->alias_connection_holder_key, $this->get_distinct_id());
            }
        }

        //endregion


        //region SHORCUTS --------------

        private function people() {
            return $this->mixpanel->people;
        }

        private function events() {
            return $this->mixpanel;
        }

        private function api() {
            return $this->mixpanel;
        }

        //endregion

        //region HELPERS ---------------

        private function get_distinct_id() {

            // Mixpanel randomizes (maybe?) the name of it's tracking cookie, so find the name of the cookie
            // by searching the keys of the cookie array and extracting the first value (there should be only one...highlander!)

            $cookies = preg_grep( '/mp[^*]+mixpanel/', array_keys( $_COOKIE ) );
            $cookie_name = array_shift( $cookies );

            if ( isset( $_COOKIE[ $cookie_name ] ) ) {

                $cookie = json_decode( str_replace( '\\', '', urldecode( $_COOKIE[ $cookie_name ] ) ) );
                return $cookie->distinct_id;

            } else {

                return null;
            }
        }

        //endregion

    }
}