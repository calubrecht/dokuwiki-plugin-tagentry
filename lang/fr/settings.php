<?php
/**
 * English language file for config of the
 * tagentry plugin
 *
 * @author    Robin Gareus <robin@gareus.org
 */
$lang['tagsrc']        = 'Selection des étiquettes disponibles.';
$lang['namespace']     = 'Utiliser ce namespace pour lister cette étiquette.';
$lang['blacklist']     = 'Liste des étiquettes à cacher (Séparé par des espaces).';
$lang['table']         = 'Arranger les étiquettes sous forme d\'une table xHTML';
$lang['limit']         = 'Limite le nombre d\'étiquettes, &lt;1: non limité';
$lang['height']        = <<<END
Hauteur des cases à coché de tagentry,
<em>float(em|px|pt)</em><br/>&lt;0:
Agrandir en fonction du nombre d'étiquettes,
&gt;0 fixe, 0 ou vide: utiliser les feuilles de styles CSS
END;
$lang['tablerowcnt']   = 'Nombre d\'étiquettes par rangé lorsque l\'utilisation des table xHTML est enclenché (default:5)';

//Setup VIM: ex: et ts=2 enc=utf-8 :
