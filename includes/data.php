<?php
/**
 * @package Pods\Global\Functions\Data
 */
/**
 * Filter input and return sanitized output
 *
 * @param mixed $input The string, array, or object to sanitize
 * @param bool $nested
 *
 * @return array|mixed|object|string|void
 * @since 1.2.0
 */
function pods_sanitize ( $input, $nested = false ) {
    $output = array();

    if ( empty( $input ) )
        $output = $input;
    elseif ( is_object( $input ) ) {
        $input = get_object_vars( $input );

        foreach ( $input as $key => $val ) {
            $output[ pods_sanitize( $key ) ] = pods_sanitize( $val, true );
        }

        $output = (object) $output;
    }
    elseif ( is_array( $input ) ) {
        foreach ( $input as $key => $val ) {
            $output[ pods_sanitize( $key ) ] = pods_sanitize( $val, true );
        }
    }
    else
        $output = esc_sql( $input );

    if ( false === $nested )
        $output = apply_filters( 'pods_sanitize', $output, $input );

    return $output;
}

/**
 * Filter input and return unsanitized output
 *
 * @param mixed $input The string, array, or object to unsanitize
 * @param bool $nested
 *
 * @return array|mixed|object|string|void
 * @since 1.2.0
 */
function pods_unsanitize ( $input, $nested = false ) {
    $output = array();

    if ( empty( $input ) )
        $output = $input;
    elseif ( is_object( $input ) ) {
        $input = get_object_vars( $input );

        foreach ( $input as $key => $val ) {
            $output[ pods_unsanitize( $key ) ] = pods_unsanitize( $val, true );
        }

        $output = (object) $output;
    }
    elseif ( is_array( $input ) ) {
        foreach ( $input as $key => $val ) {
            $output[ pods_unsanitize( $key ) ] = pods_unsanitize( $val, true );
        }
    }
    else
        $output = stripslashes( $input );

    if ( false === $nested )
        $output = apply_filters( 'pods_unsanitize', $output, $input );

    return $output;
}

/**
 * Filter input and return sanitized output
 *
 * @param mixed $input The string, array, or object to sanitize
 * @param string $charlist (optional) List of characters to be stripped from the input.
 * @param string $lr Direction of the trim, can either be 'l' or 'r'.
 *
 * @return array|object|string
 * @since 1.2.0
 */
function pods_trim ( $input, $charlist = null, $lr = null ) {
    $output = array();

    if ( is_object( $input ) ) {
        $input = get_object_vars( $input );

        foreach ( $input as $key => $val ) {
            $output[ pods_sanitize( $key ) ] = pods_trim( $val, $charlist, $lr );
        }

        $output = (object) $output;
    }
    elseif ( is_array( $input ) ) {
        foreach ( $input as $key => $val ) {
            $output[ pods_sanitize( $key ) ] = pods_trim( $val, $charlist, $lr );
        }
    }
    else {
        if ( 'l' == $lr )
            $output = ltrim( $input, $charlist );
        elseif ( 'r' == $lr )
            $output = rtrim( $input, $charlist );
        else
            $output = trim( $input, $charlist );
    }

    return $output;
}

/**
 * Return a variable (if exists)
 *
 * @param mixed $var The variable name or URI segment position
 * @param string $type (optional) get|url|post|request|server|session|cookie|constant|globals|user|option|site-option|transient|site-transient|cache|date|pods
 * @param mixed $default (optional) The default value to set if variable doesn't exist
 * @param mixed $allowed (optional) The value(s) allowed
 * @param bool $strict (optional) Only allow values (must not be empty)
 * @param bool $casting (optional) Whether to cast the value returned like provided in $default
 * @param string $context (optional) All returned values are sanitized unless this is set to 'raw'
 *
 * @return mixed The variable (if exists), or default value
 * @since 1.10.6
 */
function pods_var ( $var = 'last', $type = 'get', $default = null, $allowed = null, $strict = false, $casting = false, $context = 'display' ) {
    if ( is_array( $type ) )
        $output = isset( $type[ $var ] ) ? $type[ $var ] : $default;
    elseif ( is_object( $type ) )
        $output = isset( $type->$var ) ? $type->$var : $default;
    else {
        $type = strtolower( (string) $type );

        if ( 'get' == $type && isset( $_GET[ $var ] ) )
            $output = stripslashes_deep( $_GET[ $var ] );
        elseif ( in_array( $type, array( 'url', 'uri' ) ) ) {
            $url = parse_url( get_current_url() );
            $uri = trim( $url[ 'path' ], '/' );
            $uri = array_filter( explode( '/', $uri ) );

            if ( 'first' == $var )
                $var = 0;
            elseif ( 'last' == $var )
                $var = -1;

            if ( is_numeric( $var ) )
                $output = ( $var < 0 ) ? pods_var_raw( count( $uri ) + $var, $uri ) : pods_var_raw( $var, $uri );
        }
        elseif ( 'url-relative' == $type ) {
            $url_raw = get_current_url();
            $prefix = get_bloginfo( 'wpurl' );

            if ( substr( $url_raw, 0, strlen( $prefix ) ) == $prefix )
                $url_raw = substr( $url_raw, strlen( $prefix ) + 1, strlen( $url_raw ) );

            $url = parse_url( $url_raw );
            $uri = trim( $url[ 'path' ], '/' );
            $uri = array_filter( explode( '/', $uri ) );

            if ( 'first' == $var )
                $var = 0;
            elseif ( 'last' == $var )
                $var = -1;

            if ( is_numeric( $var ) )
                $output = ( $var < 0 ) ? pods_var_raw( count( $uri ) + $var, $uri ) : pods_var_raw( $var, $uri );
        }
        elseif ( 'post' == $type && isset( $_POST[ $var ] ) )
            $output = stripslashes_deep( $_POST[ $var ] );
        elseif ( 'request' == $type && isset( $_REQUEST[ $var ] ) )
            $output = stripslashes_deep( $_REQUEST[ $var ] );
        elseif ( 'server' == $type ) {
            if ( isset( $_SERVER[ $var ] ) )
                $output = stripslashes_deep( $_SERVER[ $var ] );
            elseif ( isset( $_SERVER[ strtoupper( $var ) ] ) )
                $output = stripslashes_deep( $_SERVER[ strtoupper( $var ) ] );
        }
        elseif ( 'session' == $type && isset( $_SESSION[ $var ] ) )
            $output = $_SESSION[ $var ];
        elseif ( in_array( $type, array( 'global', 'globals' ) ) && isset( $GLOBALS[ $var ] ) )
            $output = $GLOBALS[ $var ];
        elseif ( 'cookie' == $type && isset( $_COOKIE[ $var ] ) )
            $output = stripslashes_deep( $_COOKIE[ $var ] );
        elseif ( 'constant' == $type && defined( $var ) )
            $output = constant( $var );
        elseif ( 'user' == $type && is_user_logged_in() ) {
            $user = get_userdata( get_current_user_id() );

            if ( isset( $user->{$var} ) )
                $value = $user->{$var};
            else
                $value = get_user_meta( $user->ID, $var );

            if ( is_array( $value ) && !empty( $value ) )
                $output = $value;
            elseif ( !is_array( $value ) && 0 < strlen( $value ) )
                $output = $value;
        }
        elseif ( 'option' == $type )
            $output = get_option( $var, $default );
        elseif ( 'site-option' == $type )
            $output = get_site_option( $var, $default );
        elseif ( 'transient' == $type )
            $output = get_transient( $var );
        elseif ( 'site-transient' == $type )
            $output = get_site_transient( $var );
        elseif ( 'cache' == $type && isset( $GLOBALS[ 'wp_object_cache' ] ) && is_object( $GLOBALS[ 'wp_object_cache' ] ) ) {
            $group = 'default';
            $force = false;

            if ( is_array( $var ) ) {
                if ( isset( $var[ 1 ] ) )
                    $group = $var[ 1 ];

                if ( isset( $var[ 2 ] ) )
                    $force = $var[ 2 ];

                if ( isset( $var[ 0 ] ) )
                    $var = $var[ 0 ];
            }

            $output = wp_cache_get( $var, $group, $force );
        }
        elseif ( 'date' == $type ) {
            $var = explode( '|', $var );

            if ( !empty( $var ) )
                $output = date_i18n( $var[ 0 ], ( isset( $var[ 1 ] ) ? strtotime( $var[ 1 ] ) : false ) );
        }
        elseif ( 'pods' == $type ) {
            if ( isset( $GLOBALS[ 'pods' ] ) && 'Pods' == get_class( $GLOBALS[ 'pods' ] ) ) {
                $output = $GLOBALS[ 'pods' ]->field( $var );

                if ( is_array( $output ) )
                    $output = pods_serial_comma( $output, $var, $GLOBALS[ 'pods' ]->fields );
            }
        }
        else
            $output = apply_filters( 'pods_var_' . $type, $default, $var, $allowed, $strict, $casting, $context );
    }

    if ( null !== $allowed ) {
        if ( is_array( $allowed ) ) {
            if ( !in_array( $output, $allowed ) )
                $output = $default;
        }
        elseif ( $allowed !== $output )
            $output = $default;
    }

    if ( true === $strict ) {
        if ( empty( $output ) )
            $output = $default;
        elseif ( true === $casting )
            $output = pods_cast( $output, $default );
    }

    if ( 'raw' != $context )
        $output = pods_sanitize( $output );

    return $output;
}

/**
 * Return a variable's raw value (if exists)
 *
 * @param mixed $var The variable name or URI segment position
 * @param string $type (optional) get|url|post|request|server|session|cookie|constant|user|option|site-option|transient|site-transient|cache
 * @param mixed $default (optional) The default value to set if variable doesn't exist
 * @param mixed $allowed (optional) The value(s) allowed
 * @param bool $strict (optional) Only allow values (must not be empty)
 * @param bool $casting (optional) Whether to cast the value returned like provided in $default
 *
 * @return mixed The variable (if exists), or default value
 * @since 2.0.0
 */
function pods_var_raw ( $var = 'last', $type = 'get', $default = null, $allowed = null, $strict = false, $casting = false ) {
    return pods_var( $var, $type, $default, $allowed, $strict, $casting, 'raw' );
}

/**
 * Cast a variable as a specific type
 *
 * @param $var
 * @param null $default
 *
 * @return bool
 */
function pods_cast ( $var, $default = null ) {
    if ( is_object( $var ) && is_array( $default ) )
        $var = get_object_vars( $var );
    else
        settype( $var, gettype( $default ) );

    return $var;
}

/**
 * Set a variable
 *
 * @param mixed $value The value to be set
 * @param mixed $key The variable name or URI segment position
 * @param string $type (optional) "url", "get", "post", "request", "server", "session", "cookie", "constant", or "user"
 *
 * @return mixed $value (if set), $type (if $type is array or object), or $url (if $type is 'url')
 * @since 1.10.6
 */
function pods_var_set ( $value, $key = 'last', $type = 'url' ) {
    $type = strtolower( $type );
    $ret = false;

    if ( is_array( $type ) ) {
        $type[ $key ] = $value;
        $ret = $type;
    }
    elseif ( is_object( $type ) ) {
        $type->$key = $value;
        $ret = $type;
    }
    elseif ( 'url' == $type ) {
        $url = parse_url( get_current_url() );
        $uri = trim( $url[ 'path' ], '/' );
        $uri = array_filter( explode( '/', $uri ) );

        if ( 'first' == $key )
            $key = 0;
        elseif ( 'last' == $key )
            $key = -1;

        if ( is_numeric( $key ) ) {
            if ( $key < 0 )
                $uri[ count( $uri ) + $key ] = $value;
            else
                $uri[ $key ] = $value;
        }

        $url[ 'path' ] = '/' . implode( '/', $uri ) . '/';
        $url[ 'path' ] = trim( $url[ 'path' ], '/' );

        $ret = http_build_url( $url );
    }
    elseif ( 'get' == $type )
        $ret = $_GET[ $key ] = $value;
    elseif ( 'post' == $type )
        $ret = $_POST[ $key ] = $value;
    elseif ( 'request' == $type )
        $ret = $_REQUEST[ $key ] = $value;
    elseif ( 'server' == $type )
        $ret = $_SERVER[ $key ] = $value;
    elseif ( 'session' == $type )
        $ret = $_SESSION[ $key ] = $value;
    elseif ( 'cookie' == $type )
        $ret = $_COOKIE[ $key ] = $value;
    elseif ( 'constant' == $type && !defined( $key ) ) {
        define( $key, $value );

        $ret = constant( $key );
    }
    elseif ( 'user' == $type && is_user_logged_in() ) {
        global $user_ID;

        get_currentuserinfo();

        update_user_meta( $user_ID, $key, $value );

        $ret = $value;
    }

    return apply_filters( 'pods_var_set', $ret, $value, $key, $type );
}

/**
 * Create a new URL off of the current one, with updated parameters
 *
 * @param array $array Parameters to be set (empty will remove it)
 * @param array $allowed Parameters to keep (if empty, all are kept)
 * @param array $excluded Parameters to always remove
 * @param string $url URL to base update off of
 *
 * @return mixed
 *
 * @since 2.0.0
 */
function pods_var_update ( $array = null, $allowed = null, $excluded = null, $url = null ) {
    $array = (array) $array;
    $allowed = (array) $allowed;
    $excluded = (array) $excluded;

    if ( empty( $url ) )
        $url = $_SERVER[ 'REQUEST_URI' ];

    if ( !isset( $_GET ) )
        $get = array();
    else
        $get = $_GET;

    $get = pods_unsanitize( $get );

    foreach ( $get as $key => $val ) {
        if ( is_array( $val ) && empty( $val ) )
            unset( $get[ $key ] );
        elseif ( !is_array( $val ) && strlen( $val ) < 1 )
            unset( $get[ $key ] );
        elseif ( !empty( $allowed ) ) {
            $allow_it = false;

            foreach ( $allowed as $allow ) {
                if ( $allow == $key )
                    $allow_it = true;
                elseif ( false !== strpos( $allow, '*' ) && 0 === strpos( $key, trim( $allow, '*' ) ) )
                    $allow_it = true;
            }

            if ( !$allow_it )
                unset( $get[ $key ] );
        }
    }

    if ( !empty( $excluded ) ) {
        foreach ( $excluded as $exclusion ) {
            if ( isset( $get[ $exclusion ] ) && !in_array( $exclusion, $allowed ) )
                unset( $get[ $exclusion ] );
        }
    }

    if ( !empty( $array ) ) {
        foreach ( $array as $key => $val ) {
            if ( null !== $val || false === strpos( $key, '*' ) ) {
                if ( is_array( $val ) && !empty( $val ) )
                    $get[ $key ] = $val;
                elseif ( !is_array( $val ) && 0 < strlen( $val ) )
                    $get[ $key ] = $val;
                elseif ( isset( $get[ $key ] ) )
                    unset( $get[ $key ] );
            }
            else {
                $key = str_replace( '*', '', $key );

                foreach ( $get as $k => $v ) {
                    if ( false !== strpos( $k, $key ) )
                        unset( $get[ $k ] );
                }
            }
        }
    }

    $url = current( explode( '#', current( explode( '?', $url ) ) ) );

    if ( !empty( $get ) )
        $url = $url . '?' . http_build_query( $get );

    return $url;
}

/**
 * Create a slug from an input string
 *
 * @param $orig
 *
 * @return mixed|void
 *
 * @since 1.8.9
 */
function pods_create_slug ( $orig, $strict = true ) {
    $str = preg_replace( "/([_ ])/", "-", trim( $orig ) );

    if ( $strict )
        $str = preg_replace( "/([^0-9a-z-])/", "", strtolower( $str ) );
    else
        $str = urldecode( sanitize_title( strtolower( $str ) ) );

    $str = preg_replace( "/(-){2,}/", "-", $str );
    $str = trim( $str, '-' );
    $str = apply_filters( 'pods_create_slug', $str, $orig );

    return $str;
}

/**
 * Return a lowercase alphanumeric name (with underscores)
 *
 * @param string $orig Input string to clean
 * @param boolean $lower Force lowercase
 * @param boolean $trim_underscores Whether to trim off underscores
 *
 * @return mixed|void
 * @since 1.2.0
 */
function pods_clean_name ( $orig, $lower = true, $trim_underscores = true ) {
    $str = preg_replace( "/([- ])/", "_", trim( $orig ) );

    if ( $lower )
        $str = strtolower( $str );

    $str = preg_replace( "/([^0-9a-zA-Z_])/", "", $str );
    $str = preg_replace( "/(_){2,}/", "_", $str );
    $str = trim( $str );

    if ( $trim_underscores )
        $str = trim( $str, '_' );

    $str = apply_filters( 'pods_clean_name', $str, $orig, $lower );

    return $str;
}

/**
 * Get the Absolute Integer of a value
 *
 * @param string $maybeint
 * @param bool $strict (optional) Check if $maybeint is a integer.
 * @param bool $allow_negative (optional)
 *
 * @return integer
 * @since 2.0.0
 */
function pods_absint ( $maybeint, $strict = true, $allow_negative = false ) {
    if ( true === $strict && !is_numeric( trim( $maybeint ) ) )
        return 0;

    if ( false !== $allow_negative )
        return intval( $maybeint );

    return absint( $maybeint );
}

/**
 * Functions like str_replace except it will restrict $occurrences
 *
 * @param mixed $find
 * @param mixed $replace
 * @param string $string
 * @param int $occurrences (optional)
 *
 * @return mixed
 * @version 2.0.0
 */
function pods_str_replace ( $find, $replace, $string, $occurrences = -1 ) {
    if ( is_array( $string ) ) {
        foreach ( $string as $k => $v ) {
            $string[ $k ] = pods_str_replace( $find, $replace, $v, $occurrences );
        }

        return $string;
    }
    elseif ( is_object( $string ) ) {
        $string = get_object_vars( $string );

        foreach ( $string as $k => $v ) {
            $string[ $k ] = pods_str_replace( $find, $replace, $v, $occurrences );
        }

        return (object) $string;
    }

    if ( is_array( $find ) ) {
        foreach ( $find as &$f ) {
            $f = '/' . preg_quote( $f, '/' ) . '/';
        }
    }
    else
        $find = '/' . preg_quote( $find, '/' ) . '/';

    return preg_replace( $find, $replace, $string, $occurrences );
}

/**
 * Evaluate tags like magic tags but through pods_var
 *
 * @param string|array $tags String to be evaluated
 * @param bool $sanitize Whether to sanitize tags
 *
 * @return string
 * @version 2.1.0
 */
function pods_evaluate_tags ( $tags, $sanitize = false ) {
    if ( is_array( $tags ) ) {
        foreach ( $tags as $k => $tag ) {
            $tags[ $k ] = pods_evaluate_tags( $tag, $sanitize );
        }

        return $tags;
    }

    if ( $sanitize )
        return preg_replace_callback( '/({@(.*?)})/m', 'pods_evaluate_tag_sanitized', (string) $tags );
    else
        return preg_replace_callback( '/({@(.*?)})/m', 'pods_evaluate_tag', (string) $tags );
}

/**
 * Evaluate tag like magic tag but through pods_var_raw and sanitized
 *
 * @param string|array $tag
 *
 * @return string
 * @version 2.1.0
 * @see pods_evaluate_tag
 */
function pods_evaluate_tag_sanitized ( $tag ) {
    return pods_evaluate_tag( $tag, true );
}

/**
 * Evaluate tag like magic tag but through pods_var_raw
 *
 * @param string|array $tag
 * @param bool $sanitize Whether to sanitize tags
 *
 * @return string
 * @version 2.1.0
 */
function pods_evaluate_tag ( $tag, $sanitize = false ) {
    // Handle pods_evaluate_tags
    if ( is_array( $tag ) ) {
        if ( !isset( $tag[ 2 ] ) && strlen( trim( $tag[ 2 ] ) ) < 1 )
            return;

        $tag = $tag[ 2 ];
    }

    $tag = trim( $tag, ' {@}' );
    $tag = explode( '.', $tag );

    if ( empty( $tag ) || !isset( $tag[ 0 ] ) || strlen( trim( $tag[ 0 ] ) ) < 1 )
        return;

    // Fix formatting that may be after the first .
    if ( 2 < count( $tag ) ) {
        $first_tag = $tag[ 0 ];
        unset( $tag[ 0 ] );

        $tag = array(
            $first_tag,
            implode( '.', $tag )
        );
    }

    foreach ( $tag as $k => $v ) {
        $tag[ $k ] = trim( $v );
    }

    $value = '';

    if ( 1 == count( $tag ) )
        $value = pods_var_raw( $tag[ 0 ], 'get', '', null, true );
    elseif ( 2 == count( $tag ) )
        $value = pods_var_raw( $tag[ 1 ], $tag[ 0 ], '', null, true );

    $value = apply_filters( 'pods_evaluate_tag', $value, $tag );

    if ( $sanitize )
        $value = pods_sanitize( $value );

    return $value;
}

/**
 * Split an array into human readable text (Item, Item, and Item)
 *
 * @param array $value
 * @param string $field
 * @param array $fields
 * @param string $and
 * @param string $field_index
 *
 * @return string
 */
function pods_serial_comma ( $value, $field = null, $fields = null, $and = null, $field_index = null ) {
    if ( is_object( $value ) )
        $value = get_object_vars( $value );

    $simple = false;

    if ( !empty( $fields ) && is_array( $fields ) && isset( $fields[ $field ] ) ) {
        $field = $fields[ $field ];

        $tableless_field_types = apply_filters( 'pods_tableless_field_types', array( 'pick', 'file', 'avatar', 'taxonomy' ) );
        $simple_tableless_objects = PodsForm::field_method( 'pick', 'simple_objects' );

        if ( !empty( $field ) && is_array( $field ) && in_array( $field[ 'type' ], $tableless_field_types ) ) {
            if ( in_array( $field[ 'type' ], apply_filters( 'pods_file_field_types', array( 'file', 'avatar' ) ) ) ) {
                if ( null === $field_index )
                    $field_index = 'guid';
            }
            elseif ( in_array( $field[ 'pick_object' ], $simple_tableless_objects ) )
                $simple = true;
            else {
                $table = pods_api()->get_table_info( $field[ 'pick_object' ], $field[ 'pick_val' ] );

                if ( !empty( $table ) ) {
                    if ( null === $field_index )
                        $field_index = $table[ 'field_index' ];
                }
            }
        }
    }

    if ( $simple && is_array( $field ) && !is_array( $value ) && !empty( $value ) )
        $value = PodsForm::field_method( 'pick', 'simple_value', $value, $field );

    if ( !is_array( $value ) )
        return $value;

    if ( null === $and )
        $and = ' ' . __( 'and', 'pods' ) . ' ';

    $last = '';

		$original_value = $value;
    if ( !empty( $value ) )
        $last = array_pop( $value );

    if ( $simple && is_array( $field ) && !is_array( $last ) && !empty( $last ) )
        $last = PodsForm::field_method( 'pick', 'simple_value', $last, $field );

    if ( is_array( $last ) ) {
        if ( null !== $field_index && isset( $last[ $field_index ] ) )
            $last = $last[ $field_index ];
        elseif ( isset( $last[ 0 ] ) )
            $last = $last[ 0 ];
        elseif ( $simple )
            $last = current( $last );
        else
            $last = '';
    }

    if ( !empty( $value ) ) {
        if ( null !== $field_index && isset( $original_value[ $field_index ] ) )
            return $original_value[ $field_index ];

        if ( 1 == count( $value ) ) {
            if ( isset( $value[ 0 ] ) )
                $value = $value[ 0 ];

            if ( $simple && is_array( $field ) && !is_array( $value ) && !empty( $value ) )
                $value = PodsForm::field_method( 'pick', 'simple_value', $value, $field );

            if ( is_array( $value ) ) {
                if ( null !== $field_index && isset( $value[ $field_index ] ) )
                    $value = $value[ $field_index ];
                elseif ( $simple )
                    $value = implode( ', ', $value );
                else
                    $value = '';
            }

            $value = trim( $value, ', ' ) . ', ';
        }
        else {
            if ( null !== $field_index && isset( $value[ $field_index ] ) )
                return $value[ $field_index ];
            elseif ( !isset( $value[ 0 ] ) )
                $value = array( $value );

            foreach ( $value as $k => &$v ) {
                if ( $simple && is_array( $field ) && !is_array( $v ) && !empty( $v ) )
                    $v = PodsForm::field_method( 'pick', 'simple_value', $v, $field );

                if ( is_array( $v ) ) {
                    if ( null !== $field_index && isset( $v[ $field_index ] ) )
                        $v = $v[ $field_index ];
                    elseif ( $simple )
                        $v = trim( implode( ', ', $v ), ', ' );
                    else
                        unset( $value[ $k ] );
                }
            }

            $value = trim( implode( ', ', $value ), ', ' ) . ', ';
        }

        $value = trim( $value );
        $last = trim( $last );

        if ( 0 < strlen( $value ) && 0 < strlen( $last ) )
            $value = $value . $and . $last;
        elseif ( 0 < strlen( $last ) )
            $value = $last;
        else
            $value = '';
    }
    else
        $value = $last;

    $value = trim( $value, ', ' );

    return (string) $value;
}

/**
 * Return a variable if a user is logged in or anonymous, or a specific capability
 *
 * @param mixed $anon Variable to return if user is anonymous (not logged in)
 * @param mixed $user Variable to return if user is logged in
 * @param string|array $capability Capability or array of Capabilities to check to return $user on
 *
 * @since 2.0.5
 */
function pods_var_user ( $anon = false, $user = true, $capability = null ) {
    $value = $anon;

    if ( is_user_logged_in() ) {
        if ( empty( $capability ) )
            $value = $user;
        else {
            $capabilities = (array) $capability;

            foreach ( $capabilities as $capability ) {
                if ( current_user_can( $capability ) ) {
                    $value = $user;

                    break;
                }
            }
        }
    }

    return $value;
}