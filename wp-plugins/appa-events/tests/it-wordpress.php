<?php
/**
 * Integrationstest mit WordPress + ACF:  wp eval-file tests/it-wordpress.php
 *
 * Prüft: ACF-JSON -> Beitragstyp/Feldgruppe, abgeleitete Felder, Loop-Abfrage (Query-ID appa_events).
 * Legt Test-Veranstaltungen an und löscht ALLE Veranstaltungen – nur auf einer Testinstanz ausführen!
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$GLOBALS['t_ok'] = true; $GLOBALS['t_n'] = 0;
function t($name, $cond, $info = '') { $GLOBALS['t_n']++; if (!$cond) { $GLOBALS['t_ok'] = false; echo "FAIL $name $info\n"; } else { echo "ok   $name\n"; } }

t('Beitragstyp registriert', post_type_exists('veranstaltung'));
$pt = get_post_type_object('veranstaltung');
t('show_in_rest', $pt && $pt->show_in_rest);
t('nicht publicly_queryable', $pt && !$pt->publicly_queryable);
t('Menü-Icon', $pt && $pt->menu_icon === 'dashicons-calendar-alt', var_export($pt->menu_icon ?? null, true));
$groups = acf_get_field_groups(['post_type' => 'veranstaltung']);
t('Feldgruppe geladen', count($groups) === 1, count($groups));
$fields = $groups ? acf_get_fields($groups[0]) : [];
t('8 Felder', count($fields) === 8, count($fields));
t('Feldnamen', array_column($fields, 'name') === ['datum_von','datum_bis','uhrzeit','datum_hinweis','ort','beschreibung','button_text','button_link'], implode(',', array_column($fields,'name')));

// Alte Testdaten entfernen
foreach (get_posts(['post_type'=>'veranstaltung','post_status'=>'any','numberposts'=>-1,'fields'=>'ids']) as $id) wp_delete_post($id, true);

$mk = function($title, $meta, $status='publish') {
  $id = wp_insert_post(['post_type'=>'veranstaltung','post_title'=>$title,'post_status'=>$status]);
  foreach ($meta as $k=>$v) update_post_meta($id, $k, $v);   // wie Bridge/REST: nach dem Anlegen
  return $id;
};
$today = wp_date('Ymd');
$d = fn($off) => wp_date('Ymd', strtotime("$off day"));
$a = $mk('Vorbei',               ['datum_von'=>$d(-30), 'datum_bis'=>$d(-29)]);
$b = $mk('Gestern beendet',      ['datum_von'=>$d(-3),  'datum_bis'=>$d(-1)]);
$c = $mk('Läuft gerade',         ['datum_von'=>$d(-1),  'datum_bis'=>$d(+1)]);
$e = $mk('Heute',                ['datum_von'=>$today]);
$f = $mk('Nächstes Jahr',        ['datum_von'=>$d(+365),'datum_bis'=>$d(+366)]);
$g = $mk('In Vorbereitung',      ['datum_hinweis'=>'In Vorbereitung']);
$h = $mk('Entwurf kommt',        ['datum_von'=>$d(+10)], 'draft');
$i = $mk('Bald',                 ['datum_von'=>$d(+10)]);

t('abgeleitetes Ende gesetzt', get_post_meta($f, '_appa_ende', true) === $d(+366), get_post_meta($f,'_appa_ende',true));
t('ohne Datum: NO_END', get_post_meta($g, '_appa_ende', true) === '99991231');
t('ohne Datum: NO_START', get_post_meta($g, '_appa_sort', true) === '00000000');
// Datum nachträglich ändern -> Hook zieht nach
update_post_meta($i, 'datum_von', $d(-40)); update_post_meta($i, 'datum_bis', $d(-39));
t('Änderung zieht nach', get_post_meta($i, '_appa_ende', true) === $d(-39));
update_post_meta($i, 'datum_bis', $d(+20));
t('Änderung zieht nach (2)', get_post_meta($i, '_appa_ende', true) === $d(+20));

$q = new WP_Query(['post_type'=>'post']);   // beliebige Ausgangsabfrage, wie sie der Loop übergibt
do_action('elementor/query/appa_events', $q);
$q->query($q->query_vars);
$titles = wp_list_pluck($q->posts, 'post_title');
echo "Ergebnis: " . implode(' | ', $titles) . "\n";
// Sortierung nach Beginn: ohne Datum zuerst, dann "Bald" (läuft seit 40 Tagen), Läuft gerade, Heute, Nächstes Jahr
t('Reihenfolge', $titles === ['In Vorbereitung','Bald','Läuft gerade','Heute','Nächstes Jahr'], implode('|',$titles));
t('Vergangenes fehlt', !in_array('Vorbei',$titles) && !in_array('Gestern beendet',$titles));
t('Entwurf fehlt', !in_array('Entwurf kommt',$titles));

$GLOBALS['post'] = get_post($e);
t('Shortcode', do_shortcode('[appa_termin id="'.$g.'"]') === 'In Vorbereitung', do_shortcode('[appa_termin id="'.$g.'"]'));
$str = do_shortcode('[appa_termin id="'.$f.'"]');
echo "Terminzeile Nächstes Jahr: $str\n";
t('Terminzeile enthält Jahr', (bool) preg_match('/\d{4}$/', $str));

echo ($GLOBALS['t_ok'] ? "\nALLE {$GLOBALS['t_n']} OK\n" : "\nFEHLER\n");
if (!$GLOBALS['t_ok']) { exit(1); }
