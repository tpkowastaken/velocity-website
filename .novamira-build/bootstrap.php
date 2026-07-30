
$media = [];
$base = WP_CONTENT_DIR . '/uploads/2026/07/';
require_once ABSPATH . 'wp-admin/includes/image.php';
foreach (['portrait-1.jpg'=>'image/jpeg','portrait-2.jpg'=>'image/jpeg','portrait-3.jpg'=>'image/jpeg','portrait-4.jpg'=>'image/jpeg','velocity-voltage.svg'=>'image/svg+xml','velocity-white.svg'=>'image/svg+xml','chrome-v.png'=>'image/png'] as $name=>$mime) {
  $path = $base.$name;
  if (!file_exists($path)) { $media[$name]='missing'; continue; }
  $existing = get_posts(['post_type'=>'attachment','meta_key'=>'_wp_attached_file','meta_value'=>'2026/07/'.$name,'posts_per_page'=>1,'fields'=>'ids']);
  if ($existing) $id=(int)$existing[0];
  else {
    $id = wp_insert_attachment(['post_mime_type'=>$mime,'post_title'=>sanitize_file_name(pathinfo($name,PATHINFO_FILENAME)),'post_content'=>'','post_status'=>'inherit'], $path);
    $meta = wp_generate_attachment_metadata($id, $path);
    if (is_array($meta)) wp_update_attachment_metadata($id, $meta);
  }
  $media[$name]=['id'=>$id,'url'=>wp_get_attachment_url($id)];
}
if (get_option('permalink_structure')==='') { update_option('permalink_structure','/%postname%/'); flush_rewrite_rules(); }
$sample = get_page_by_path('zkusebni-stranka'); if ($sample) wp_trash_post($sample->ID);
$defs = [
  ['Domů','home'],['Služby','sluzby'],['Gen Z Audit','gen-z-audit'],['Obsahová strategie','obsahova-strategie'],
  ['Core Offer','core-offer'],['Reference','reference'],['O nás','o-nas'],['Kontakt','kontakt']
];
$pages=[];
foreach ($defs as [$title,$slug]) {
  $existing = get_page_by_path($slug);
  if ($existing) { $id=$existing->ID; wp_update_post(['ID'=>$id,'post_status'=>'publish','post_title'=>$title]); }
  else { $id = wp_insert_post(['post_type'=>'page','post_title'=>$title,'post_name'=>$slug,'post_status'=>'publish','post_content'=>'']); }
  $pages[$slug]=['id'=>$id,'url'=>get_permalink($id)];
}
update_option('show_on_front','page');
update_option('page_on_front',$pages['home']['id']);
$menu_name='Velocity Primary';
$menu = wp_get_nav_menu_object($menu_name);
if (!$menu) { $menu_id = wp_create_nav_menu($menu_name); } else { $menu_id = (int)$menu->term_id; }
// clear items
$items = wp_get_nav_menu_items($menu_id) ?: [];
foreach ($items as $it) wp_delete_post($it->ID, true);
$order=1;
foreach ([['home','Home'],['sluzby','Služby'],['gen-z-audit','Gen Z Audit'],['obsahova-strategie','Obsahová strategie'],['core-offer','Core Offer'],['reference','Reference'],['o-nas','O nás'],['kontakt','Kontakt']] as [$slug,$label]) {
  $parent=0;
  if (in_array($slug,['gen-z-audit','obsahova-strategie','core-offer'],true)) {
    // parent Služby item - find by object id
  }
  wp_update_nav_menu_item($menu_id, 0, [
    'menu-item-title'=>$label,
    'menu-item-object'=>'page',
    'menu-item-object-id'=>$pages[$slug]['id'],
    'menu-item-type'=>'post_type',
    'menu-item-status'=>'publish',
    'menu-item-position'=>$order++,
  ]);
}
$locations = get_theme_mod('nav_menu_locations') ?: [];
$locations['header'] = $menu_id; // Bricks may use different
set_theme_mod('nav_menu_locations', $locations);
return ['media'=>$media,'pages'=>$pages,'menu_id'=>$menu_id,'front'=>$pages['home']['id']];
