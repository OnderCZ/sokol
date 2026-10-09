<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
session_start_safe();
$p=(string)($_GET['p']??'dashboard');
// Legacy import and raw archive editors were retired from the administration.
if (in_array($p,['import','legacy'],true)) $p='seasons';
$auth=load_php_json(AUTH_FILE);
$error='';
function save_uploaded_photo(string $field): string {
    $f=$_FILES[$field]??null;
    if(!$f || ($f['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return '';
    if($f['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Fotografii se nepodařilo nahrát.');
    if($f['size']>8_000_000)throw new RuntimeException('Fotografie může mít nejvýše 8 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if(!$ext || !getimagesize($f['tmp_name']))throw new RuntimeException('Vyberte platný JPG, PNG nebo WebP.');
    $file='uploads/'.bin2hex(random_bytes(18)).'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],__DIR__.'/'.$file))throw new RuntimeException('Fotografii nebylo možné uložit.');
    return $file;
}
if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=(string)($_POST['action']??'');
    if ($action==='setup' && !$auth) {
        $pw=(string)($_POST['password']??'');
        if(strlen($pw)<12)$error='Heslo musí obsahovat alespoň 12 znaků.';
        elseif($pw!==($_POST['password_confirm']??''))$error='Hesla se neshodují.';
        else {save_php_json(AUTH_FILE,['hash'=>password_hash($pw,PASSWORD_DEFAULT),'created'=>date('c')]);session_regenerate_id(true);$_SESSION['admin']=true;flash('Administrace byla úspěšně nastavena.');redirect(admin_route('dashboard'));}
    } elseif ($action==='login' && $auth) {
        $rate=(int)($_SESSION['attempts']??0);
        if($rate>=8 && time()-(int)($_SESSION['last_fail']??0)<900) $error='Příliš mnoho pokusů. Zkuste to prosím za 15 minut.';
        elseif (password_verify((string)($_POST['password']??''), $auth['hash'])) {session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['attempts']=0;redirect(admin_route('dashboard'));}
        else {$_SESSION['attempts']=$rate+1;$_SESSION['last_fail']=time();$error='Nesprávné heslo.';}
    } elseif($action==='logout'){$_SESSION=[];session_regenerate_id(true);redirect(admin_route('login'));}
    elseif(is_logged()) {
        $db=db();
        try {
            switch($action) {
            case 'season_save':
                $id=clean_id((string)($_POST['id']??''));
                $label=clean_text($_POST['label']??'',20);
                if(!preg_match('~^\d{4}/\d{4}$~',$label))throw new RuntimeException('Sezóna musí mít formát 2027/2028.');
                $league=clean_text($_POST['league']??'',170);
                if(!$league)throw new RuntimeException('Vyplňte název soutěže.');
                $found=false;
                foreach($db['seasons'] as &$s) if($s['id']===$id){$s['label']=$label;$s['league']=$league;$s['rank']=clean_text($_POST['rank']??'',40);$s['points']=clean_text($_POST['points']??'',20);$s['goals']=clean_text($_POST['goals']??'',40);$s['summary']=clean_text($_POST['summary']??'',4000);$found=true;break;}
                unset($s);
                if(!$found){$id=new_id();array_unshift($db['seasons'],['id'=>$id,'label'=>$label,'league'=>$league,'status'=>'past','rank'=>clean_text($_POST['rank']??'',40),'points'=>clean_text($_POST['points']??'',20),'goals'=>clean_text($_POST['goals']??'',40),'summary'=>clean_text($_POST['summary']??'',4000),'matches'=>[],'articles'=>[],'summaries'=>['autumn'=>[],'spring'=>[]],'legacy_html'=>'','original_url'=>'']);}
                save_db($db);flash('Sezóna byla uložena.');redirect(admin_route('season',['id'=>$id]));
            case 'season_archive':
                $current=active_season($db);if(!$current)throw new RuntimeException('Není nastavena aktuální sezóna.');
                if($current['id']!==clean_id((string)($_POST['id']??'')))throw new RuntimeException('Nelze archivovat jinou než aktuální sezónu.');
                foreach($db['seasons'] as &$s) if($s['id']===$current['id']){$s['status']='past';break;} unset($s);
                $years=explode('/',$current['label']);$newLabel=(isset($years[1]) && ctype_digit($years[1])) ? $years[1].'/'.((int)$years[1]+1) : 'Nová sezóna';
                array_unshift($db['seasons'],['id'=>new_id(),'label'=>$newLabel,'league'=>$current['league'],'status'=>'current','rank'=>'','points'=>'','goals'=>'','summary'=>'Nová soutěžní sezóna.','matches'=>[],'articles'=>[],'summaries'=>['autumn'=>[],'spring'=>[]],'legacy_html'=>'','original_url'=>'','auto_created'=>true]);
                save_db($db);flash('Sezóna '.$current['label'].' je v archivu. Nová sezóna '.$newLabel.' je aktuální.');redirect(admin_route('seasons'));
            case 'season_restore':
                $id=clean_id((string)($_POST['id']??''));
                $target=find_season($db,$id);
                if(!$target || ($target['status']??'')!=='past')throw new RuntimeException('Vybraná sezóna není v archivu.');
                if((string)($_POST['confirm_restore']??'')!=='yes')throw new RuntimeException('Potvrďte změnu aktuální sezóny.');
                $previous=active_season($db);
                foreach($db['seasons'] as $ix=>&$ss){
                    if($ss['id']===$id)$ss['status']='current';
                    elseif($ss['status']==='current')$ss['status']='past';
                }unset($ss);
                // An untouched automatically created placeholder can be safely removed.
                // Every populated or modified season is retained intact in the archive.
                if($previous && !empty($previous['auto_created']) && empty($previous['matches']) && empty($previous['articles']) && empty($previous['legacy_html']) && !array_filter($previous['summaries']??[],fn($rec)=>!empty($rec['body'])||!empty($rec['photo'])) && ($previous['summary']??'')==='Nová soutěžní sezóna.'){
                    $db['seasons']=array_values(array_filter($db['seasons'],fn($row)=>$row['id']!==$previous['id']));
                }
                save_db($db);flash('Sezóna '.$target['label'].' je nyní aktuální. Předchozí ročník byl zachován v archivu, pokud nešlo o prázdnou automaticky vytvořenou sezónu.');redirect(admin_route('season',['id'=>$id]));
            case 'match_save':
                $sid=clean_id((string)($_POST['season']??''));$id=clean_id((string)($_POST['id']??''));$foundSeason=false;
                foreach($db['seasons'] as &$s)if($s['id']===$sid){$foundSeason=true;$match=['id'=>$id?:new_id(),'date'=>clean_text($_POST['date']??'',10),'round'=>clean_text($_POST['round']??'',10),'home'=>clean_text($_POST['home']??'',100),'away'=>clean_text($_POST['away']??'',100),'home_score'=>clean_text($_POST['home_score']??'',3),'away_score'=>clean_text($_POST['away_score']??'',3),'halftime'=>clean_text($_POST['halftime']??'',20),'time'=>clean_text($_POST['time']??'',10),'report'=>sanitize_html((string)($_POST['report']??''))];
                    if(!$match['home']||!$match['away'])throw new RuntimeException('Vyplňte oba týmy.');
                    $found=false;foreach($s['matches'] as $i=>$m)if($m['id']===$id){$match+=array_intersect_key($m,array_flip(['date_label','historical_import','report_only']));$s['matches'][$i]=$match;$found=true;break;}if(!$found)$s['matches'][]=$match;break;}
                unset($s);if(!$foundSeason)throw new RuntimeException('Sezóna neexistuje.');save_db($db);flash('Zápas byl uložen.');redirect(admin_route('season',['id'=>$sid]));
            case 'match_delete':
                $sid=clean_id((string)($_POST['season']??''));$id=clean_id((string)($_POST['id']??''));foreach($db['seasons'] as &$s)if($s['id']===$sid){$s['matches']=array_values(array_filter($s['matches'],fn($m)=>$m['id']!==$id));break;}unset($s);save_db($db);flash('Zápas byl smazán.');redirect(admin_route('season',['id'=>$sid]));
            case 'summary_save':
                $sid=clean_id((string)($_POST['season']??''));$part=($_POST['part']??'')==='autumn'?'autumn':'spring';$exists=false;
                foreach($db['seasons'] as &$ss)if($ss['id']===$sid){
                    $exists=true;$previous=$ss['summaries'][$part]??[];
                    $photo=(string)($previous['photo']??'');
                    if(isset($_POST['photo_remove']))$photo='';
                    $uploaded=save_uploaded_photo('photo');if($uploaded)$photo=$uploaded;
                    $imageUrl=trim((string)($_POST['photo_url']??''));
                    if($imageUrl!==''){
                        if(!safe_media($imageUrl))throw new RuntimeException('Neplatná adresa fotografie.');
                        $photo=$imageUrl;
                    }
                    $ss['summaries'][$part]=[
                        'title'=>clean_text($_POST['title']??'',150),
                        'body'=>sanitize_html((string)($_POST['body']??'')),
                        'photo'=>$photo,
                        'caption'=>clean_text($_POST['caption']??'',1200)
                    ];break;
                }unset($ss);
                if(!$exists)throw new RuntimeException('Sezóna neexistuje.');
                save_db($db);flash('Souhrn sezóny byl uložen.');redirect(admin_route('season',['id'=>$sid]));
            case 'sponsor_save':
                $id=clean_id((string)($_POST['id']??''));$old=null;
                foreach($db['sponsors']??[] as $sp)if($sp['id']===$id){$old=$sp;break;}
                $name=clean_text($_POST['name']??'',160);if(!$name)throw new RuntimeException('Vyplňte název partnera.');
                $img=trim((string)($_POST['image']??($old['image']??'')));
                if($img!==''&&!safe_media($img))throw new RuntimeException('Neplatná adresa obrázku.');
                $url=trim((string)($_POST['url']??''));if($url!==''&&!external_link($url))throw new RuntimeException('Zadejte platný odkaz http nebo https.');
                $entry=['id'=>$id?:new_id(),'name'=>$name,'description'=>clean_text($_POST['description']??'',400),'url'=>$url,'image'=>$img];
                $found=false;foreach($db['sponsors'] as $i=>$sp)if($sp['id']===$id){$db['sponsors'][$i]=$entry;$found=true;break;}
                if(!$found)$db['sponsors'][]=$entry;
                save_db($db);flash('Partner byl uložen.');redirect(admin_route('sponsors'));
            case 'sponsor_delete':
                $id=clean_id((string)($_POST['id']??''));$db['sponsors']=array_values(array_filter($db['sponsors']??[],fn($sp)=>$sp['id']!==$id));
                save_db($db);flash('Partner byl odebrán.');redirect(admin_route('sponsors'));
            case 'club_links_save':
                $lines=preg_split('/\r?\n/',(string)($_POST['club_links']??''));$links=[];
                foreach($lines as $line){
                    $line=trim($line);if(!$line)continue;
                    $parts=explode('|',$line,2);
                    if(count($parts)<2)throw new RuntimeException('Každý odkaz musí mít název a URL oddělené znakem |.');
                    $title=clean_text($parts[0],100);$url=trim($parts[1]);
                    if(!$title||!external_link($url))throw new RuntimeException('Neplatný klubový odkaz: '.$line);
                    $links[]=['name'=>$title,'url'=>$url];
                }
                $db['club_links']=array_slice($links,0,40);save_db($db);flash('Odkazy byly aktualizovány.');redirect(admin_route('settings'));
            case 'team_photo_save':
                $new=save_uploaded_photo('photo');
                if($new){$db['settings']['home_team_photo']=$new;save_db($db);flash('Týmová fotografie byla změněna.');}
                redirect(admin_route('settings'));
            case 'article_save':
                $sid=clean_id((string)($_POST['season']??''));$id=clean_id((string)($_POST['id']??''));
                $a=['id'=>$id?:new_id(),'title'=>clean_text($_POST['title']??'',160),'date'=>clean_text($_POST['date']??'',10),'excerpt'=>clean_text($_POST['excerpt']??'',600),'body'=>sanitize_html((string)($_POST['body']??''))];
                if(!$a['title'])throw new RuntimeException('Vyplňte název článku.');
                foreach($db['seasons'] as &$s)if($s['id']===$sid){$found=false;foreach($s['articles'] as $i=>$old)if($old['id']===$id){$s['articles'][$i]=$a;$found=true;break;}if(!$found)array_unshift($s['articles'],$a);break;}unset($s);
                save_db($db);flash('Článek byl uložen.');redirect(admin_route('season',['id'=>$sid]));
            case 'article_delete':
                $sid=clean_id((string)($_POST['season']??''));$id=clean_id((string)($_POST['id']??''));foreach($db['seasons'] as &$s)if($s['id']===$sid){$s['articles']=array_values(array_filter($s['articles'],fn($a)=>$a['id']!==$id));break;}unset($s);save_db($db);flash('Článek byl smazán.');redirect(admin_route('season',['id'=>$sid]));
            case 'history_save':
                foreach(['chair','treasurer','culture'] as $key)$db['history'][$key]=clean_text($_POST[$key]??'',500);
                $db['history']['body']=sanitize_html((string)($_POST['body']??''));save_db($db);flash('Historie byla aktualizována.');redirect(admin_route('history'));
            case 'page_save':
                $id=clean_id((string)($_POST['id']??''));$prev=null;foreach($db['extra_pages'] as $old)if($old['id']===$id){$prev=$old;break;}
                $row=['id'=>$id?:new_id(),'title'=>clean_text($_POST['title']??'',100),'body'=>sanitize_html((string)($_POST['body']??'')),'show_nav'=>isset($_POST['show_nav']),'category'=>$prev['category']??'custom','legacy_source'=>$prev['legacy_source']??'','imported'=>$prev['imported']??false];
                if(!$row['title'])throw new RuntimeException('Vyplňte název stránky.');
                $found=false;foreach($db['extra_pages'] as $i=>$entry)if($entry['id']===$id){$db['extra_pages'][$i]=$row;$found=true;break;}if(!$found)$db['extra_pages'][]=$row;
                save_db($db);flash('Stránka byla uložena.');redirect(admin_route('pages'));
            case 'page_delete':
                $id=clean_id((string)($_POST['id']??''));$db['extra_pages']=array_values(array_filter($db['extra_pages'],fn($a)=>$a['id']!==$id));save_db($db);flash('Stránka byla smazána.');redirect(admin_route('pages'));
            case 'settings_save':
                foreach(array_merge(['name','tagline','email','contact','season_note','facebook','instagram','brand_upper','brand_lower','brand_subtitle','hero_kicker','hero_title','hero_description','hero_panel_kicker','hero_panel_subtitle','hero_panel_title','hero_panel_place','home_matches_kicker','home_matches_title','home_matches_description','home_team_kicker','home_team_title','home_team_description','home_team_photo','home_team_caption','home_team_players','footer_description','footer_note','history_intro','sponsors_intro','sponsors_funding','contact_intro','home_updates_label','club_links_title'],array_merge(...array_map('array_keys',array_values(public_text_fields())))) as $key)if(array_key_exists($key,$_POST))$db['settings'][$key]=clean_text($_POST[$key],5000);foreach(['facebook','instagram'] as $key)if($db['settings'][$key]!==''&&!external_link($db['settings'][$key]))throw new RuntimeException('Neplatná adresa sociální sítě.');if($db['settings']['home_team_photo']!==''&&!safe_media($db['settings']['home_team_photo']))throw new RuntimeException('Neplatná adresa fotografie.');save_db($db);flash('Nastavení byla uložena.');redirect(admin_route('settings'));
            case 'password_change':
                $old=(string)($_POST['old_password']??'');$pw=(string)($_POST['new_password']??'');
                if(!$auth || !password_verify($old,$auth['hash']))throw new RuntimeException('Původní heslo není správné.');
                if(strlen($pw)<12)throw new RuntimeException('Nové heslo musí mít alespoň 12 znaků.');
                save_php_json(AUTH_FILE,['hash'=>password_hash($pw,PASSWORD_DEFAULT),'created'=>$auth['created']??date('c'),'changed'=>date('c')]);flash('Heslo bylo změněno.');redirect(admin_route('settings'));
            case 'upload':
                $f=$_FILES['image']??null;
                if(!$f || $f['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Soubor se nepodařilo nahrát.');
                if($f['size']>5_000_000)throw new RuntimeException('Maximální velikost souboru je 5 MB.');
                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
                if(!$ext || !@getimagesize($f['tmp_name']))throw new RuntimeException('Povoleny jsou pouze obrázky JPG, PNG a WebP.');
                $dest=__DIR__.'/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
                if(!move_uploaded_file($f['tmp_name'],$dest))throw new RuntimeException('Soubor nelze uložit.');
                flash('Obrázek byl nahrán. Zkopírujte si jeho adresu do editoru.');redirect(admin_route('media'));
            default:throw new RuntimeException('Neznámá akce.');
            }
        } catch(Throwable $e){$error=$e->getMessage();}
    }
}
if(!$auth)$p='setup';elseif(!is_logged())$p='login';
$db=db();
$s=isset($_GET['id'])?find_season($db,clean_id((string)$_GET['id'])):null;
$sub_id=clean_id((string)($_GET['item']??''));
$match=$s&&$sub_id?find_match($s,$sub_id):null;
$article=null;if($s&&$sub_id)foreach($s['articles'] as $a)if($a['id']===$sub_id){$article=$a;break;}
$page_row=null;foreach($db['extra_pages'] as $pg)if($pg['id']===$sub_id){$page_row=$pg;break;}
function afield(string $label,string $name,mixed $value='',string $type='text',string $help=''):void{ ?>
<label class="field"><span><?=h($label)?></span><input type="<?=h($type)?>" name="<?=h($name)?>" value="<?=h($value)?>" <?= $type==='date'?'':'maxlength="500"' ?>><?php if($help):?><small><?=h($help)?></small><?php endif;?></label>
<?php }
function atarea(string $label,string $name,mixed $val='',int $rows=4):void{?><label class="field"><span><?=h($label)?></span><textarea name="<?=h($name)?>" rows="<?=$rows?>"><?=h($val)?></textarea></label><?php }
function rich_editor(string $label,string $name,mixed $value):void {static $n=0;$n++;$id='rich-'.$n; ?>
<div class="field rich-wrap"><label for="<?=$id?>"><?=$label?></label><div class="rich-toolbar"><button type="button" data-cmd="bold"><b>B</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="formatBlock" data-value="h2">H2</button><button type="button" data-cmd="formatBlock" data-value="p">¶</button><button type="button" data-cmd="insertUnorderedList">☷</button><button type="button" data-link="1">🔗</button></div><div class="rich-surface prose" id="<?=$id?>" contenteditable="true" role="textbox" aria-multiline="true"><?=sanitize_html((string)$value)?></div><textarea hidden name="<?=h($name)?>" data-rich-source="<?=$id?>"><?=h($value)?></textarea><small>Text upravíte přímo zde. Odkazy a obrázky můžete také vložit prostřednictvím HTML v pokročilém editoru.</small></div>
<?php }
function post_start(string $action):void {echo '<input type="hidden" name="action" value="'.h($action).'">'.csrf_input();}
function delete_button(string $action,array $params,string $label='Smazat'):void{?><form method="post" class="inline-form"><?php post_start($action);foreach($params as $k=>$v):?><input type="hidden" name="<?=h($k)?>" value="<?=h($v)?>"><?php endforeach;?><button class="link-danger" data-confirm="Opravdu smazat? Tato akce je nevratná." type="submit"><?=h($label)?></button></form><?php }
?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Administrace | TJ Sokol Sobíňov</title><link rel="icon" href="assets/logo.png"><link rel="stylesheet" href="assets/style.css?v=8"><link rel="stylesheet" href="assets/admin.css?v=4"><script defer src="assets/app.js"></script><script defer src="assets/admin.js"></script></head><body class="admin-body">
<?php if($p==='setup' || $p==='login'): ?>
<div class="auth-outer"><div class="auth-card"><a href="index.php" class="auth-brand"><img src="assets/logo.png" alt="Logo TJ Sokol Sobíňov"><b>TJ SOKOL SOBÍŇOV</b></a><span class="eyebrow green-text">KLUBOVÁ ADMINISTRACE</span><h1><?= $p==='setup'?'První nastavení.':'Vítejte zpět.'?></h1><p><?= $p==='setup'?'Vytvořte administrátorské heslo pro správu webu. Toto nastavení se provádí jen jednou.':'Přihlaste se pro správu zápasů, sezón a článků.'?></p><?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?><form method="post" class="stack-form"><?php post_start($p==='setup'?'setup':'login');afield('Heslo','password','','password');if($p==='setup')afield('Heslo znovu (min. 12 znaků)','password_confirm','','password');?><button type="submit" class="btn btn-primary"><?= $p==='setup'?'Vytvořit administraci':'Přihlásit se'?> <span>↗</span></button></form><a href="index.php" class="auth-back">← Zpět na web</a></div></div>
<?php else: ?>
<div class="admin-app"><aside class="admin-sidebar"><a class="admin-brand" href="admin.php"><img src="assets/logo.png" alt="Znak klubu"><span>TJ SOKOL<br><b>SOBÍŇOV</b><small>ADMINISTRACE</small></span></a><span class="side-label">OBSAH WEBU</span><nav aria-label="Administrace"><a class="<?= $p==='dashboard'?'here':''?>" href="<?=h(admin_route('dashboard'))?>">◫ <span>Přehled</span></a><a class="<?=in_array($p,['seasons','season','summary','match','article'])?'here':''?>" href="<?=h(admin_route('seasons'))?>">▤ <span>Sezóny a zápasy</span></a><a class="<?=$p==='history'?'here':''?>" href="<?=h(admin_route('history'))?>">◷ <span>Historie klubu</span></a><a class="<?=in_array($p,['pages','page'])?'here':''?>" href="<?=h(admin_route('pages'))?>">▣ <span>Další stránky</span></a><a class="<?=$p==='media'?'here':''?>" href="<?=h(admin_route('media'))?>">▧ <span>Fotografie</span></a><a class="<?=$p==='sponsors'?'here':''?>" href="<?=h(admin_route('sponsors'))?>">◈ <span>Sponzoři</span></a></nav><span class="side-label">NASTAVENÍ</span><nav><a class="<?=$p==='settings'?'here':''?>" href="<?=h(admin_route('settings'))?>">⚙ <span>Nastavení</span></a><a href="index.php" target="_blank">↗ <span>Zobrazit web</span></a></nav><div class="sidebar-bottom"><form method="post"><?php post_start('logout');?><button type="submit">⏻ &nbsp; Odhlásit se</button></form></div></aside>
<div class="admin-main"><header class="admin-topbar"><div><button type="button" class="admin-nav-toggle" aria-label="Otevřít administraci">☰</button><span>SPRÁVA KLUBOVÉHO WEBU</span></div><div class="admin-online"><i></i> Administrátor</div></header><div class="admin-content">
<?php if($msg=take_flash()):?><div class="alert success"><?=h($msg)?></div><?php endif;?><?php if($error):?><div class="alert error"><?=h($error)?></div><?php endif;?>
<?php if($p==='dashboard'): $active=active_season($db); ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">VÍTEJTE V ADMINISTRACI</span><h1>Přehled webu</h1><p>Všechno důležité pro správu vašeho klubu.</p></div><a class="admin-btn" href="<?=h(admin_route('match',['id'=>$active['id']??'']))?>">+ Přidat zápas</a></div>
<div class="admin-metrics"><div><span>AKTUÁLNÍ SEZÓNA</span><b><?=h($active['label']??'—')?></b><small><?=h($active['league']??'')?></small></div><div><span>ARCHIVOVANÉ SEZÓNY</span><b><?=count($db['seasons'])-($active?1:0)?></b><small>ročníků v archivu</small></div><div><span>ZÁPASY TÉTO SEZÓNY</span><b><?=count($active['matches']??[])?></b><small>v rozpisu</small></div><div><span>ČLÁNKY TÉTO SEZÓNY</span><b><?=count($active['articles']??[])?></b><small>zápasových reportů</small></div></div>
<div class="admin-two"><section class="admin-panel"><div class="panel-title"><h2>Rychlé akce</h2></div><div class="quick-links"><a href="<?=h(admin_route('season',['id'=>$active['id']??'']))?>">Upravit aktuální sezónu <span>↗</span></a><a href="<?=h(admin_route('article',['id'=>$active['id']??'']))?>">Napsat zápasový report <span>↗</span></a><a href="<?=h(admin_route('history'))?>">Upravit historii klubu <span>↗</span></a></div></section><section class="admin-panel"><div class="panel-title"><h2>Jak web funguje?</h2></div><p class="admin-help">Sezóna je samostatná sada zápasů a článků. Na konci ročníku ji archivujte tlačítkem <strong>Ukončit sezónu</strong> – web ji automaticky zařadí mezi minulé a připraví novou.</p><p class="admin-help">Údaje se ukládají přímo na vašem hostingu. Nepotřebujete externí redakční systém ani databázi.</p></section></div>
<?php elseif($p==='seasons'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">ROČNÍKY & ZÁPASY</span><h1>Správa sezón</h1><p>Spravujte jednotlivé ročníky, výsledky i přesun do archivu.</p></div><a class="admin-btn" href="<?=h(admin_route('season'))?>">+ Nová archivní sezóna</a></div>
<div class="admin-panel"><div class="admin-table-head columns-season"><span>SEZÓNA</span><span>STAV</span><span>ZÁPASY</span><span>AKCE</span></div><?php foreach($db['seasons'] as $i=>$season): ?><div class="admin-table-row columns-season"><div><strong><?=h($season['label'])?></strong><small><?=h($season['league'])?></small></div><div><span class="status <?= $season['status']==='current'?'status-current':''?>"><?= $season['status']==='current'?'Aktuální':'Archiv'?></span></div><div><?=count($season['matches'])?></div><div><a class="small-action" href="<?=h(admin_route('season',['id'=>$season['id']]))?>">Spravovat ↗</a></div></div><?php endforeach;?></div>
<?php elseif($p==='season'): $edit=$s??['id'=>'','label'=>'','league'=>'','rank'=>'','points'=>'','goals'=>'','summary'=>'','matches'=>[],'articles'=>[],'status'=>'past','legacy_html'=>'']; ?>
<div class="admin-page-head"><div><a href="<?=h(admin_route('seasons'))?>" class="back-link">← Všechny sezóny</a><h1><?=h($s?'Sezóna '.$s['label']:'Nová sezóna')?></h1><p><?= $s&&$s['status']==='current'?'Toto je aktuální ročník zobrazený na úvodní stránce.':'Archivovaná sezóna a její obsah.'?></p></div><div class="head-actions"><?php if($s):?><a class="small-action" href="<?=h(route($s['status']==='current'?'current':'season',$s['status']==='current'?[]:['id'=>$s['id']]))?>" target="_blank">Zobrazit ↗</a><?php if($s['status']==='past'):?><form method="post" class="inline-form"><?php post_start('season_restore');?><input type="hidden" name="id" value="<?=h($s['id'])?>"><input type="hidden" name="confirm_restore" value="yes"><button class="small-action" type="submit" data-confirm="Opravdu vrátit ročník <?=h($s['label'])?> mezi aktuální sezóny? Předchozí aktuální ročník se bezpečně archivuje.">Nastavit jako aktuální ↗</button></form><?php endif;endif;?></div></div>
<section class="admin-panel"><div class="panel-title"><h2>Základní údaje</h2><span class="status <?=$s&&$s['status']==='current'?'status-current':''?>"><?= $s&&$s['status']==='current'?'Aktuální':'Archiv'?></span></div><form method="post" class="stack-form"><?php post_start('season_save');?><input type="hidden" name="id" value="<?=h($edit['id'])?>"><div class="form-grid"><?php afield('Sezóna (např. 2027/2028)','label',$edit['label']);afield('Soutěž','league',$edit['league']);afield('Umístění','rank',$edit['rank']);afield('Body','points',$edit['points']);afield('Skóre','goals',$edit['goals']);?></div><?php atarea('Popis a shrnutí sezóny','summary',$edit['summary'],4);?><div><button type="submit" class="admin-btn">Uložit údaje ↗</button></div></form></section>
<?php if($s):?><section class="admin-panel"><div class="panel-title"><h2>Souhrny sezóny</h2></div><p class="admin-help">Hodnocení po podzimu a po jarní části má vlastní text a týmovou fotografii. Obě části lze upravit nezávisle na zápasech.</p><?php foreach(['autumn'=>'Po podzimu','spring'=>'Po sezóně'] as $part=>$lab):$summary=$s['summaries'][$part]??[];?><div class="admin-item"><div><strong><?=h($lab)?></strong><small><?=empty($summary['body'])?'Zatím bez textu':'✓ Hodnocení uloženo'?><?=empty($summary['photo'])?'':' · ✓ Fotografie'?></small></div><div class="admin-item-actions"><a href="<?=h(admin_route('summary',['id'=>$s['id'],'part'=>$part]))?>" class="small-action">Upravit hodnocení ↗</a><a href="<?=h(route('summary',['id'=>$s['id'],'part'=>$part]))?>" target="_blank">Zobrazit</a></div></div><?php endforeach;?></section><?php endif;?>
<?php if($s):?><section class="admin-panel"><div class="panel-title"><h2>Zápasy <span class="light-count"><?=count($s['matches'])?></span></h2><a class="admin-btn" href="<?=h(admin_route('match',['id'=>$s['id']]))?>">+ Přidat zápas</a></div><div class="admin-match-list"><?php foreach(sorted_matches($s['matches']) as $m): ?><div class="admin-item"><div><strong><?=h($m['home'])?> – <?=h($m['away'])?></strong><small><?=h(match_day($m))?> · <?=h($m['round'])?>. kolo · <?=h(comparison($m))?> · <?=empty($m['report'])?'Bez reportu':'✓ Report'?></small></div><div class="admin-item-actions"><a href="<?=h(route('match',['id'=>$s['id'],'item'=>$m['id']]))?>" target="_blank" class="small-action">Zobrazit</a><a href="<?=h(admin_route('match',['id'=>$s['id'],'item'=>$m['id']]))?>"><?=empty($m['report'])?'Napsat report':'Upravit report'?></a><?php delete_button('match_delete',['season'=>$s['id'],'id'=>$m['id']]);?></div></div><?php endforeach;?></div><?php if(!$s['matches']):?><p class="admin-help">Zatím žádné zápasy.</p><?php endif;?></section>
<section class="admin-panel"><div class="panel-title"><h2>Ostatní články <span class="light-count"><?=count($s['articles'])?></span></h2><a class="admin-btn" href="<?=h(admin_route('article',['id'=>$s['id']]))?>">+ Nový článek</a></div><?php foreach($s['articles'] as $a): ?><div class="admin-item"><div><strong><?=h($a['title'])?></strong><small><?=h(stamp($a['date']??''))?></small></div><div class="admin-item-actions"><a href="<?=h(admin_route('article',['id'=>$s['id'],'item'=>$a['id']]))?>">Upravit</a><?php delete_button('article_delete',['season'=>$s['id'],'id'=>$a['id']]);?></div></div><?php endforeach; ?><?php if(!$s['articles']):?><p class="admin-help">Zatím žádné články.</p><?php endif;?></section>
<?php if($s['status']==='current'):?><section class="admin-panel end-season"><h2>Ukončení aktuální sezóny</h2><p class="admin-help">Ročník <?=h($s['label'])?> se přesune do Minulých sezón. Automaticky vznikne nový ročník s prázdnými zápasy a články. Původní údaje zůstanou beze změny.</p><form method="post"><?php post_start('season_archive');?><input type="hidden" name="id" value="<?=h($s['id'])?>"><button class="admin-btn danger" data-confirm="Opravdu ukončit sezónu <?=h($s['label'])?> a založit následující?" type="submit">Ukončit sezónu a přesunout do archivu ↗</button></form></section><?php elseif($s['status']==='past'):?><section class="admin-panel restore-panel"><span class="eyebrow green-text">VRÁCENÍ Z ARCHIVU</span><h2>Nastavit tuto sezónu jako aktuální</h2><p>Pokud jste sezónu omylem archivovali, můžete ji vrátit. Stávající aktuální ročník se přesune do archivu; prázdný automaticky vytvořený ročník se odstraní. Žádné odehrané zápasy ani komentáře se nesmažou.</p><form method="post"><?php post_start('season_restore');?><input type="hidden" name="id" value="<?=h($s['id'])?>"><input type="hidden" name="confirm_restore" value="yes"><button type="submit" class="admin-btn" data-confirm="Opravdu nastavit sezónu <?=h($s['label'])?> jako aktuální? Dosavadní aktuální sezóna přejde do archivu.">Vrátit sezónu <?=h($s['label'])?> jako aktuální ↗</button></form></section><?php endif;endif; ?>
<?php elseif($p==='summary' && $s): $part=($_GET['part']??'')==='autumn'?'autumn':'spring';$record=$s['summaries'][$part]??[]; ?>
<div class="admin-page-head"><div><a class="back-link" href="<?=h(admin_route('season',['id'=>$s['id']]))?>">← Sezóna <?=h($s['label'])?></a><h1><?= $part==='autumn'?'Hodnocení po podzimu':'Hodnocení po sezóně'?></h1><p>Text, fotografie a popisek jsou oddělené od zápasových reportů.</p></div><a class="small-action" href="<?=h(route('summary',['id'=>$s['id'],'part'=>$part]))?>" target="_blank">Zobrazit ↗</a></div>
<section class="admin-panel"><form method="post" class="stack-form" enctype="multipart/form-data"><?php post_start('summary_save');?><input type="hidden" name="season" value="<?=h($s['id'])?>"><input type="hidden" name="part" value="<?=h($part)?>"><?php afield('Nadpis','title',$record['title']??($part==='autumn'?'Hodnocení po podzimu':'Hodnocení po sezóně'));rich_editor('Celý text souhrnu','body',$record['body']??'');?><div class="field"><span>Fotografie týmu</span><?php if(!empty($record['photo'])):?><img class="edit-summary-photo" src="<?=h(safe_media($record['photo']))?>" alt="Dosavadní fotografie"><?php endif;?><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, WebP do 8 MB. Nahráním nové fotografie se stávající nahradí.</small></div><?php afield('Adresa fotografie (pokud nechcete soubor nahrávat)','photo_url','');?><label class="check-field"><input type="checkbox" name="photo_remove" value="1"> Odebrat nynější fotografii</label><?php atarea('Popisek fotografie','caption',$record['caption']??'',3);?><button type="submit" class="admin-btn">Uložit hodnocení ↗</button></form></section>
<?php elseif($p==='match' && $s): $m=$match??['id'=>'','date'=>'','round'=>'','home'=>'Sobíňov','away'=>'','home_score'=>'','away_score'=>'','halftime'=>'','time'=>'','report'=>'']; ?>
<div class="admin-page-head"><div><a class="back-link" href="<?=h(admin_route('season',['id'=>$s['id']]))?>">← Sezóna <?=h($s['label'])?></a><h1><?= $match?'Upravit zápas':'Nový zápas'?></h1></div></div><section class="admin-panel"><form method="post" class="stack-form"><?php post_start('match_save');?><input type="hidden" name="season" value="<?=h($s['id'])?>"><input type="hidden" name="id" value="<?=h($m['id'])?>"><div class="form-grid"><?php afield('Datum zápasu','date',$m['date'],'date');afield('Kolo','round',$m['round']);afield('Domácí','home',$m['home']);afield('Hosté','away',$m['away']);afield('Góly domácích (nevyplněné = neodehráno)','home_score',$m['home_score'],'number');afield('Góly hostů','away_score',$m['away_score'],'number');afield('Poločas (např. 1:0)','halftime',$m['halftime']);afield('Čas výkopu (např. 15:00)','time',$m['time']);?></div><?php rich_editor('Detailní komentář k zápasu (nepovinné)','report',$m['report']);?><button class="admin-btn" type="submit">Uložit zápas ↗</button></form></section>
<?php elseif($p==='article' && $s): $a=$article??['id'=>'','title'=>'','date'=>date('Y-m-d'),'excerpt'=>'','body'=>'']; ?>
<div class="admin-page-head"><div><a class="back-link" href="<?=h(admin_route('season',['id'=>$s['id']]))?>">← Sezóna <?=h($s['label'])?></a><h1><?= $article?'Upravit článek':'Nový článek'?></h1></div></div><section class="admin-panel"><form method="post" class="stack-form"><?php post_start('article_save');?><input type="hidden" name="season" value="<?=h($s['id'])?>"><input type="hidden" name="id" value="<?=h($a['id'])?>"><div class="form-grid"><?php afield('Nadpis článku','title',$a['title']);afield('Datum','date',$a['date'],'date');?></div><?php atarea('Krátký úvod pro titulní stránku','excerpt',$a['excerpt'],3);rich_editor('Obsah článku','body',$a['body']);?><button type="submit" class="admin-btn">Uložit článek ↗</button></form></section>
<?php elseif($p==='sponsors'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">PODPOROVATELÉ KLUBU</span><h1>Sponzoři a partneři</h1><p>Spravujte všechny partnery z původní sponzorské stránky.</p></div><a class="admin-btn" href="<?=h(admin_route('sponsor'))?>">+ Přidat sponzora</a></div><section class="admin-panel"><?php foreach($db['sponsors']??[] as $sp):?><div class="admin-item"><div><strong><?=h($sp['name'])?></strong><small><?=h($sp['url']?:$sp['description'])?></small></div><div class="admin-item-actions"><a href="<?=h(admin_route('sponsor',['id'=>$sp['id']]))?>">Upravit</a><?php delete_button('sponsor_delete',['id'=>$sp['id']]);?></div></div><?php endforeach;?></section><a href="<?=h(route('sponsors'))?>" target="_blank" class="small-action">Otevřít veřejnou stránku ↗</a>
<?php elseif($p==='sponsor'): $sp=null;foreach($db['sponsors']??[] as $e)if($e['id']===clean_id((string)($_GET['id']??'')))$sp=$e;$sp=$sp??['id'=>'','name'=>'','description'=>'','image'=>'','url'=>'']; ?>
<div class="admin-page-head"><div><a class="back-link" href="<?=h(admin_route('sponsors'))?>">← Sponzoři</a><h1><?=h($sp['id']?'Upravit partnera':'Nový partner')?></h1></div></div><section class="admin-panel"><form method="post" class="stack-form"><?php post_start('sponsor_save');?><input type="hidden" name="id" value="<?=h($sp['id'])?>"><?php afield('Název sponzora','name',$sp['name']);atarea('Popisek','description',$sp['description'],3);afield('Webová adresa partnera','url',$sp['url']);afield('Adresa loga / fotografie (může být i z galerie)','image',$sp['image']);?><button type="submit" class="admin-btn">Uložit partnera ↗</button></form></section>
<?php elseif($p==='history'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">NAŠE KOŘENY</span><h1>Historie klubu</h1><p>Upravujte text historie a současné složení výboru.</p></div></div><section class="admin-panel"><form method="post" class="stack-form"><?php post_start('history_save');?><div class="form-grid"><?php afield('Předseda','chair',$db['history']['chair']);afield('Pokladník','treasurer',$db['history']['treasurer']);afield('Kulturní referenti','culture',$db['history']['culture']);?></div><?php rich_editor('Historie – hlavní text','body',$db['history']['body']);?><button class="admin-btn" type="submit">Uložit historii ↗</button></form></section>
<?php elseif($p==='pages'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">VLASTNÍ OBSAH</span><h1>Další stránky</h1><p>Např. kontakty, přípravka, fotogalerie nebo aktuality.</p></div><a class="admin-btn" href="<?=h(admin_route('page'))?>">+ Nová stránka</a></div><section class="admin-panel"><?php foreach($db['extra_pages'] as $pg): ?><div class="admin-item"><div><strong><?=h($pg['title'])?></strong><small><?=h(route('page',['id'=>$pg['id']]))?></small></div><div class="admin-item-actions"><a href="<?=h(admin_route('page',['item'=>$pg['id']]))?>">Upravit</a><?php delete_button('page_delete',['id'=>$pg['id']]);?></div></div><?php endforeach; ?><?php if(!$db['extra_pages']):?><p class="admin-help">Nemáte žádné vlastní stránky. Tři první se automaticky zobrazí v hlavní navigaci.</p><?php endif;?></section>
<?php elseif($p==='page'): $pg=$page_row??['id'=>'','title'=>'','body'=>'']; ?>
<div class="admin-page-head"><div><a class="back-link" href="<?=h(admin_route('pages'))?>">← Další stránky</a><h1><?= $page_row?'Upravit stránku':'Nová stránka'?></h1></div></div><section class="admin-panel"><form method="post" class="stack-form"><?php post_start('page_save');?><input type="hidden" name="id" value="<?=h($pg['id'])?>"><?php afield('Název stránky','title',$pg['title']);?><label class="check-field"><input type="checkbox" name="show_nav" value="1" <?=($pg['show_nav']??true)?'checked':''?>> Zobrazit v hlavním menu (max. 3 vlastní stránky)</label><?php rich_editor('Obsah stránky','body',$pg['body']);?><button class="admin-btn" type="submit">Uložit stránku ↗</button></form></section>
<?php elseif($p==='media'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">KLUBOVÁ GALERIE</span><h1>Fotografie</h1><p>Nahrané fotografie můžete používat ve článcích a na dalších stránkách.</p></div></div><section class="admin-panel"><h2>Nahrát fotografii</h2><form method="post" enctype="multipart/form-data" class="upload-form"><?php post_start('upload');?><input type="file" name="image" accept="image/png,image/jpeg,image/webp" required><button class="admin-btn" type="submit">Nahrát fotografii ↗</button></form><p class="admin-help">JPG, PNG nebo WebP do 5 MB. HTML obrázku do editoru vložte přes <code>&lt;img src="uploads/nazev.jpg"&gt;</code> v pokročilém editoru.</p></section><section class="admin-panel"><h2>Nahrané soubory</h2><div class="media-grid"><?php foreach(glob(__DIR__.'/uploads/*.{jpg,jpeg,png,webp}', GLOB_BRACE)?:[] as $file):$url='uploads/'.basename($file);?><div><img src="<?=h($url)?>" alt="Nahraná fotografie" loading="lazy"><button type="button" class="copy-url" data-url="<?=h($url)?>">Kopírovat adresu ↗</button></div><?php endforeach;?></div></section>
<?php elseif($p==='settings'): ?>
<div class="admin-page-head"><div><span class="eyebrow green-text">TEXTY A KONTAKTNÍ ÚDAJE</span><h1>Obsah celého webu</h1><p>Veřejné texty úvodní stránky, hlavičky, patičky, sponzorů a kontaktů lze upravit na jednom místě.</p></div></div>
<section class="admin-panel"><form method="post" class="stack-form"><?php post_start('settings_save');?>
<h2>Klub a kontakty</h2><div class="form-grid"><?php foreach(['name'=>'Název klubu','email'=>'Kontaktní e-mail','facebook'=>'Facebook','instagram'=>'Instagram (fan site)','brand_upper'=>'První řádek názvu u loga','brand_lower'=>'Druhý řádek názvu u loga','brand_subtitle'=>'Podtitulek pod logem','tagline'=>'Krátký podtitulek klubu','season_note'=>'Poznámka k soutěži'] as $k=>$lab)afield($lab,$k,$db['settings'][$k]??'');?></div><?php atarea('Další kontaktní informace','contact',$db['settings']['contact']??'',3);?>
<h2>Hlavní úvodní část</h2><div class="form-grid"><?php foreach(['hero_kicker'=>'Horní štítek','hero_title'=>'Hlavní nadpis','hero_panel_kicker'=>'Štítek u znaku','hero_panel_subtitle'=>'Popisek nad názvem vedle znaku','hero_panel_title'=>'Název klubu vedle znaku','hero_panel_place'=>'Popisek místa pod názvem'] as $k=>$lab)afield($lab,$k,$db['settings'][$k]??'');?></div><?php atarea('Text pod hlavním nadpisem','hero_description',$db['settings']['hero_description']??'',3);?>
<h2>Úvodní stránka a tým</h2><div class="form-grid"><?php foreach(['home_matches_kicker'=>'Štítek výsledků','home_matches_title'=>'Nadpis výsledků','home_matches_description'=>'Popis výsledků','home_team_kicker'=>'Štítek týmu','home_team_title'=>'Nadpis u týmové fotografie','home_team_description'=>'Úvod k týmu','home_team_photo'=>'Adresa fotografie mužstva','home_team_caption'=>'Popisek týmové fotografie','home_updates_label'=>'Poznámka k aktualizaci','club_links_title'=>'Nadpis klubových odkazů'] as $k=>$lab)afield($lab,$k,$db['settings'][$k]??'');?></div><?php atarea('Jména a sestava hráčů na fotografii','home_team_players',$db['settings']['home_team_players']??'',4);?>
<?php foreach(public_text_fields() as $group=>$fields):?><h2><?=h($group)?></h2><div class="form-grid"><?php foreach($fields as $field=>$fallback)afield($fallback.' (text na webu)',$field,$db['settings'][$field]??$fallback);?></div><?php endforeach;?>
<h2>Ostatní stránky a patička</h2><div class="form-grid"><?php foreach(['footer_note'=>'Krátký text v patičce','history_intro'=>'Podnadpis historie','sponsors_intro'=>'Úvod sponzorské stránky','contact_intro'=>'Úvod kontaktů'] as $k=>$lab)afield($lab,$k,$db['settings'][$k]??'');?></div><?php atarea('Popis klubu v patičce','footer_description',$db['settings']['footer_description']??'',3);atarea('Informace o grantové podpoře a dotacích','sponsors_funding',$db['settings']['sponsors_funding']??'',4);?><button type="submit" class="admin-btn">Uložit všechny texty ↗</button></form></section>
<section class="admin-panel"><h2>Nahrát novou týmovou fotografii</h2><form method="post" enctype="multipart/form-data" class="stack-form"><?php post_start('team_photo_save');?><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><p class="admin-help">Po nahrání se fotografie na hlavní stránce automaticky aktualizuje. Původní fotografii si před změnou můžete zálohovat.</p><button type="submit" class="admin-btn">Nahrát a nastavit fotografii ↗</button></form></section>
<section class="admin-panel"><h2>Odkazy na okolní kluby</h2><form method="post" class="stack-form"><?php post_start('club_links_save');?><?php $lines=[];foreach($db['club_links']??[] as $link)$lines[]=($link['name']??'').' | '.($link['url']??'');atarea('Každý řádek: Název | https://adresa', 'club_links',implode("\n",$lines),9);?><button type="submit" class="admin-btn">Uložit odkazy ↗</button></form></section>
<section class="admin-panel"><h2>Změna hesla</h2><form method="post" class="stack-form"><?php post_start('password_change');?><div class="form-grid"><?php afield('Staré heslo','old_password','','password');afield('Nové heslo (min. 12 znaků)','new_password','','password');?></div><button type="submit" class="admin-btn">Změnit heslo ↗</button></form></section>
<?php else:?><div class="admin-page-head"><h1>Stránka nenalezena</h1></div><a href="admin.php" class="admin-btn">Zpět na přehled</a><?php endif; ?>
</div></div></div>
<?php endif;?></body></html>
