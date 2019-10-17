<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the tagentry plugin
 *
 * @author    Robin Gareus <robin@gareus.org>
 */
$meta['tagsrc']       = array('multichoice', '_choices' => array('All tags','Pagenames in tag NS', 'Pages in given namespace'));
$meta['namespace']    = array('string');
$meta['blacklist']    = array('string');

//Setup VIM: ex: et ts=2 enc=utf-8 :
