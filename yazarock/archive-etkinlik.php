<?php if(!defined('ABSPATH'))exit; get_header();
$cal=(($_GET['gorunum']??'')==='takvim');$past=isset($_GET['gecmis'])&&!$cal;$base=get_post_type_archive_link('etkinlik');
$cities=[];if(!$cal){foreach($wp_query->posts as $p){$c=get_post_meta($p->ID,'_yr_sehir',true);if($c)$cities[$c]=1;}ksort($cities);}?>
<div class="ph"><h1><?php echo $cal?'Etkinlik Takvimi':($past?'Geçmiş Etkinlikler':'Etkinlikler');?></h1><p>Konser ve festival takvimi.</p></div>
<div class="tabs vt"><a class="<?php echo $cal?'':'on';?>" href="<?php echo esc_url($base);?>">Liste</a><a class="<?php echo $cal?'on':'';?>" href="<?php echo esc_url(add_query_arg('gorunum','takvim',$base));?>">Takvim</a></div>
<?php if($cal):?>
<div class="box"><?php yr_cal(sanitize_text_field(wp_unslash($_GET['ay']??'')));?></div>
<?php else:?>
<div class="box" data-filter>
<div class="fb"><label style="flex:1;min-width:180px">Şehir<select data-k="c"><option value="all">Tüm şehirler</option><?php foreach(array_keys($cities) as $c)echo '<option>'.esc_html($c).'</option>';?></select></label></div>
<div class="tabs" data-k="y"><button data-v="all" aria-pressed="true">Tümü</button><button data-v="Konser" aria-pressed="false">Konser</button><button data-v="Festival" aria-pressed="false">Festival</button><button data-v="Kulüp Gecesi" aria-pressed="false">Kulüp Gecesi</button></div>
<div class="events"><?php while(have_posts()){the_post();yr_event_card();}?></div>
<p class="empty" <?php echo $wp_query->post_count?'hidden':'';?>>Etkinlik bulunamadı.</p>
<a class="more" href="<?php echo $past?esc_url($base):esc_url(add_query_arg('gecmis','1',$base));?>"><?php echo $past?'← Yaklaşan etkinlikler':'Geçmiş etkinlikler →';?></a></div>
<?php endif;get_footer();