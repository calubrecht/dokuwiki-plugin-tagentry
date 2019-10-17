<?php

// must be run within Dokuwiki
if ( !defined( 'DOKU_INC' ) ) die();

if ( !defined( 'DOKU_PLUGIN' ) ) define( 'DOKU_PLUGIN', DOKU_INC . 'lib/plugins/' );

require_once( DOKU_PLUGIN . 'action.php' );

class action_plugin_tagentry extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers
     */
	public function register(Doku_Event_Handler $controller) {
        // hook
        $controller->register_hook(
            'HTML_EDITFORM_OUTPUT', 'BEFORE', $this,
            'handle_editform_output'
        );
    }

    /**
     * Create the additional fields for the edit form.
     */
    function handle_editform_output( &$event, $param ) {
        $pos = $event->data->findElementByAttribute( 'type', 'submit' );
        if ( !$pos ){ return; }
        $prefixHidden = empty( $event->data->_hidden['prefix'] );
        $suffixHidden = empty( $event->data->_hidden['suffix'] );
        if ( $prefixHidden || ! $suffixHidden ){
            return;
        }
        // get all tags
        $tagns = $this->getConf( 'namespace' );
        if ( $thlp =& plugin_load( 'helper', 'tag' ) ) {
            if ( $this->getConf( 'tagsrc' ) == 'Pagenames in tag NS' ) {
                $tagnst = $thlp->getConf( 'namespace' );
                if ( !empty( $tagnst ) )
                    $tagns = $tagnst;
            }
        }
        if ( $this->getConf( 'tagsrc' ) == 'All tags' && $thlp ) {
            $alltags = array_map( 'trim', idx_getIndex( 'subject', '_w' ) );
        } else {
            $alltags = $this->_getpages( $tagns );
        }

        // get already assigned tags for this page
        $assigned = false;
        if ( 1 ) { // parse wiki-text to pick up tags for draft/prevew
            $wikipage = '';
            $wt = $event->data->findElementByType( 'wikitext' );
            if ( $wt !== false ) {
                $wikipage = $event->data->_content[$wt]['_text'];
            }
            if ( !empty( $wikipage ) ){
                if ( preg_match( '@\{\{tag>(.*?)\}\}@', $wikipage, $m ) ) {
                    $assigned = explode( ' ', $m[1] );
                }
            }
        }
        if ( !is_array( $assigned ) ) {
            // those are from the prev. saved version.
            global $ID;
            $meta     = array();
            $meta     = p_get_metadata( $ID );
            $assigned = $meta['subject'];
        }
        $options = array(
            'blacklist' => explode( ' ', $this->getConf( 'blacklist' ) ),
            'assigned' => $assigned,
        );
        $out = '<div id="plugin__tagentry_wrapper">';
        $out .= $this->_format_tags( $alltags, $options );
        $out .= '</div>';
        $event->data->insertElement( $pos++, $out );
    }

    /**
     * callback function for dokuwiki search()
     *
     * Build a list of tags from the tag namespace
     * $opts['ns'] is the namespace to browse
     */
    function _tagentry_search_tagpages( &$data, $base, $file, $type, $lvl, $opts ) {
        $return = true;
        $item   = array();
        if ( $type == 'd' ) {
            // TODO: check if namespace mismatch -> break recursion early.
            return true;
        } elseif ( $type == 'f' && !preg_match( '#\.txt$#', $file ) ) {
            return false;
        }
        $id = pathID( $file );
        if ( getNS( $id ) != $opts['ns'] )
            return false;
        if ( isHiddenPage( $id ) ) {
            return false;
        }
        if ( $type == 'f' && auth_quickaclcheck( $id ) < AUTH_READ ) {
            return false;
        }
        $data[] = noNS( $id );
        return $return;
    }

    /**
     * list all tags from the topic index.
     * (requires newer version of the tag plugin)
     *
     * @param $thlp  pointer to tag plugin's helper
     * @return array list of tag names, sorted by frequency
     */
    function _gettags( &$thlp ) {
        $data = array();
        if ( !is_array( $thlp->topic_idx ) )
            return $data;
        foreach ( $thlp->topic_idx as $k => $v ) {
            if ( !is_array( $v ) || empty( $v ) || ( !trim( $v[0] ) ) )
                continue;
            $data[$k] = count( $v );
        }
        arsort( $data );
        return ( array_keys( $data ) );
    }

    /**
     * list all pages in the namespace.
     *
     * @param $tagns namespace to search.
     * @return array list of tag names.
     */
    function _getpages( $tagns = 'wiki:tags' ) {
        global $conf;
        require_once( DOKU_INC . 'inc/search.php' );
        $data = array();
        search( $data, $conf['datadir'], array(
             $this,
            '_tagentry_search_tagpages'
        ), array(
             'ns' => $tagns
        ) );
        return ( $data );
    }

    function clipstring( $s, $len = 22 ) {
        return substr( $s, 0, $len ) . ( ( strlen( $s ) > $len ) ? '..' : '' );
    }

    function escapeJSstring( $o ) {
        return ( // TODO: use JSON ?!
            str_replace( "\n", '\\n', str_replace( "\r", '', str_replace( '\'', '\\\'', str_replace( '\\', '\\\\', $o ) ) ) ) );
    }

    /** case insenstive in_array();.
     */
    function in_iarray( $needle, $haystack ) {
        if ( !is_array( $haystack ) )
            return false;
        foreach ( $haystack as $t ) {
            if ( strcasecmp( $needle, $t ) == 0 )
                return true;
        }
        return false;
    }

    /**
     * render and return the tag-select box.
     *
     * @param $alltags array of tags to display.
     * @param $options array
     * @return string XHTML form.
     */
    function _format_tags( $alltags, $options ) {
        $rv = '';
        if ( !is_array( $alltags ) ){ return $rv; }
        if ( count( $alltags ) < 1 ){ return $rv; }
        //$rv .= '<div>';
        //$rv .= ' <div><label>' . $this->getLang( 'assign' ) . '</label></div>';
        $rv .= '<div class="taglist"' . $dstyle . '>';
        //$rv .= '<div>';

        // Trie les tags
        natcasesort( $alltags );

        // Boucle sur les tags
        $i = 0;
        foreach ( $alltags as $tagname ) {

            // Blacklist
            $hasBlacklist = is_array( $options['blacklist'] );
            $inBlacklist = $this->in_iarray( $tagname, $options['blacklist'] );

            if ( $hasBlacklist && $inBlacklist ){ continue; }

            $i++;

            $rv .= '<label><input type="checkbox" id="plugin__tagentry_cb' . $tagname . '"';
            $rv .= ' value="1" name="' . $tagname . '"';
            if ( $this->in_iarray( $tagname, $options['assigned'] ) ){
                $rv .= ' checked="checked"';
            }

            $rv .= ' onclick="tagentry_clicktag(\'' . $this->escapeJSstring( $tagname ) . '\', this);"';

            // MODIF : 23/12/2013 14:43:26
            $rv .= ' /> ' . $this->_getTagTitle( $tagname )  ;
            $rv .= '</label>';
            //$rv.=' /> '.$this->clipstring($tagname).'</label>&nbsp;';
            // MODIF : 23/12/2013 14:43:26


            $rv .= "\n";
        }

        //$rv .= '</div>';
        //$rv .= '</div>';
        return ( $rv );
    }

    // MODIF : 23/12/2013 14:55:07
    /**
     * Return Header title or tag name
     * @param $tagname The name of tag without namespace
     * @return Title of the tag page or tag name formatted
     */
    function _getTagTitle( $tagname ) {
        global $conf;
        if ( $conf['useheading'] ) {
            $tagplugin = plugin_load( 'helper', 'tag' );
            if ( plugin_isdisabled( 'tag' ) || !$tagplugin ) {
                msg( 'The Tag Plugin must be installed to display tagentry.', -1 );
                return $this->clipstring( $tagname );
            }

            $id    = $tagname;
            $exist = false;
            resolve_pageID( $tagplugin->namespace, $id, $exist );
            if ( $exist ) {
                return p_get_first_heading( $id, false );
            }
        }
        return $this->clipstring( $tagname );
    }
    // MODIF : 23/12/2013 14:55:07
}
