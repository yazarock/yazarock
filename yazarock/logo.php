<?php
if(!defined('ABSPATH'))exit;
/* Yeni logo (enstrüman harfleri). Özelleştir › Yazarock Ayarları › "Eski logoyu kullan" ile geri alınabilir. */
function yr_logo_yeni(){return !get_theme_mod('yr_logo_eski',false);}
function yr_logo_svg(){return '<span class="screen-reader-text">Yazarock</span>'.'<svg viewBox="0 0 1120 220" aria-hidden="true" focusable="false"><defs><clipPath id="mikrofon-izgara"><circle cx="732" cy="86" r="34"></circle></clipPath></defs><polygon points="34,36 10,114 26,114 14,196 46,96 30,96 44,36" fill="#e5393f"></polygon><g fill="none" stroke="#e5393f" stroke-width="5" stroke-linecap="round"><path d="M72 50 Q63 66 72 82"></path><path d="M62 42 Q50 66 62 90"></path><path d="M164 50 Q173 66 164 82"></path><path d="M174 42 Q186 66 174 90"></path></g><g fill="none" stroke="#ece6e6" stroke-width="14" stroke-linecap="round" stroke-linejoin="round"><path d="M86 40 V98 Q86 124 118 124 Q150 124 150 98 V40"></path><path d="M118 124 V170"></path><path d="M230 84 L196 180 H292 L244 42 L238 60"></path><path d="M334 54 H410 L334 168 H410"></path><path d="M490 42 H518 L552 180 H456 Z"></path><path d="M589 47 H620 Q656 47 656 80 Q656 113 620 113 H589"></path><path d="M614 113 L656 180"></path><path d="M912 66 A62 62 0 1 0 912 154"></path><path d="M1052 44 L1012 108 L1054 180"></path></g><circle cx="118" cy="178" r="10" fill="#ece6e6"></circle><path d="M236 40 Q244 22 252 40" fill="none" stroke="#e5393f" stroke-width="4" stroke-linecap="round"></path><path d="M202 150 L298 131" stroke="#e5393f" stroke-width="7" stroke-linecap="round"></path><circle cx="202" cy="150" r="9" fill="#e5393f"></circle><rect x="306" y="46" width="22" height="16" rx="3" fill="#e5393f"></rect><rect x="296" y="51" width="11" height="6" rx="2" fill="#ece6e6"></rect><path d="M314 46 V62 M321 46 V62" stroke="#0d0809" stroke-width="2"></path><rect x="416" y="160" width="22" height="16" rx="3" fill="#e5393f"></rect><rect x="437" y="165" width="11" height="6" rx="2" fill="#ece6e6"></rect><path d="M423 160 V176 M430 160 V176" stroke="#0d0809" stroke-width="2"></path><g stroke="#e5393f" stroke-linecap="round"><path d="M504 158 L522 64" stroke-width="5"></path><path d="M484 92 H494 M480 108 H492 M476 124 H490" stroke-width="3"></path></g><rect x="511" y="78" width="14" height="10" rx="2" fill="#e5393f" transform="rotate(11 518 83)"></rect><circle cx="504" cy="160" r="7" fill="#0d0809" stroke="#e5393f" stroke-width="4"></circle><rect x="582" y="40" width="14" height="140" rx="7" fill="#ece6e6"></rect><path d="M582 126 H596 M582 140 H596 M582 154 H596 M582 168 H596" stroke="#0d0809" stroke-width="2"></path><g stroke="#e5393f" stroke-width="4" stroke-linecap="round"><path d="M664 60 H670 M666 80 H672 M664 100 H670"></path></g><circle cx="674" cy="60" r="5" fill="#e5393f"></circle><circle cx="676" cy="80" r="5" fill="#e5393f"></circle><circle cx="674" cy="100" r="5" fill="#e5393f"></circle><circle cx="732" cy="86" r="40" fill="#ece6e6"></circle><circle cx="732" cy="86" r="34" fill="#e5393f"></circle><g clip-path="url(#mikrofon-izgara)" stroke="#0d0809" stroke-width="2"><path d="M700 52 V120 M709 52 V120 M718 52 V120 M727 52 V120 M736 52 V120 M745 52 V120 M754 52 V120 M763 52 V120 M698 59 H766 M698 68 H766 M698 77 H766 M698 86 H766 M698 95 H766 M698 104 H766 M698 113 H766"></path></g><rect x="713" y="124" width="38" height="9" rx="2" fill="#ece6e6"></rect><path d="M710 136 H754 L745 182 H719 Z" fill="#ece6e6"></path><path d="M714 150 H750" stroke="#0d0809" stroke-width="3"></path><g stroke="#ece6e6" stroke-width="8" stroke-linecap="round"><path d="M868 30 V38 M868 182 V190 M788 110 H796 M816 58 L811 53 M816 162 L811 167"></path></g><circle cx="868" cy="110" r="40" fill="none" stroke="#e5393f" stroke-width="4"></circle><polygon points="874,86 858,112 867,112 861,134 878,106 869,106 878,86" fill="#e5393f"></polygon><ellipse cx="954" cy="44" rx="28" ry="6" fill="#e5393f" transform="rotate(-8 954 44)"></ellipse><ellipse cx="954" cy="44" rx="7" ry="2.5" fill="#0d0809" transform="rotate(-8 954 44)"></ellipse><g stroke="#ece6e6" stroke-width="5" stroke-linecap="round"><path d="M954 50 V98 M954 98 L942 114 M954 98 L966 114"></path></g><rect x="993" y="56" width="14" height="124" rx="7" fill="#ece6e6"></rect><path d="M993 138 H1007 M993 152 H1007 M993 166 H1007" stroke="#0d0809" stroke-width="2"></path><rect x="989" y="32" width="22" height="28" rx="4" fill="#e5393f"></rect><circle cx="995" cy="40" r="2.4" fill="#0d0809"></circle><circle cx="1005" cy="40" r="2.4" fill="#0d0809"></circle><circle cx="995" cy="51" r="2.4" fill="#0d0809"></circle><circle cx="1005" cy="51" r="2.4" fill="#0d0809"></circle><circle cx="1036" cy="152" r="5" fill="#e5393f"></circle><polygon points="1080,36 1104,114 1088,114 1100,196 1070,96 1086,96 1072,36" fill="#3b82f6"></polygon></svg>';}
add_action('customize_register',function($c){
	$c->add_setting('yr_logo_eski',['default'=>false,'sanitize_callback'=>'rest_sanitize_boolean']);
	$c->add_control('yr_logo_eski',['label'=>'Eski logoyu kullan (yeni enstrüman harfli logo yerine)','section'=>'yr','type'=>'checkbox','priority'=>1]);
	$c->add_setting('yr_oto_guncelle',['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
	$c->add_control('yr_oto_guncelle',['label'=>'Tema güncellemelerini GitHub\'dan otomatik kur','section'=>'yr','type'=>'checkbox','priority'=>2]);
},40);

/* ================= Otomatik tema güncellemesi ================= */
/* WordPress'in otomatik güncelleyicisi bu temayı da güncellesin */
add_filter('auto_update_theme',function($u,$item){
	$slug=is_object($item)&&isset($item->theme)?$item->theme:(is_array($item)&&isset($item['theme'])?$item['theme']:'');
	if($slug===get_template())return (bool)get_theme_mod('yr_oto_guncelle',true);
	return $u;
},20,2);
/* Saatlik kontrolde yeni sürüm görülürse güncellemeyi beklemeden başlat (sürüm başına bir kez dener) */
function yr_oto_guncelle_dene(){
	if(!get_theme_mod('yr_oto_guncelle',true)||wp_installing())return;
	if(!function_exists('yr_gh_remote_version'))return;
	$new=yr_gh_remote_version(true);$cur=wp_get_theme(get_template())->get('Version');
	if(!$new||!version_compare($new,$cur,'>'))return;
	if(get_option('yr_oto_denenen')===$new)return;
	update_option('yr_oto_denenen',$new,false);
	delete_site_transient('update_themes');wp_update_themes();
	if(!function_exists('wp_maybe_auto_update'))require_once ABSPATH.'wp-admin/includes/update.php';
	if(function_exists('wp_maybe_auto_update'))wp_maybe_auto_update();
}
add_action('yr_derleme_saatlik','yr_oto_guncelle_dene',20);
add_action('yr_oto_guncelle_simdi','yr_oto_guncelle_dene');
/* Ziyaretçi gelince en geç 20 dakikada bir arka planda kontrol (paneli ya da sayfayı bekletmez) */
add_action('init',function(){
	if(wp_doing_ajax()||wp_doing_cron()||get_transient('yr_oto_bakti'))return;
	set_transient('yr_oto_bakti',1,20*MINUTE_IN_SECONDS);
	if(!wp_next_scheduled('yr_oto_guncelle_simdi'))wp_schedule_single_event(time()+5,'yr_oto_guncelle_simdi');
},99);
