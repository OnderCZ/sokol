<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$db=db();$current=active_season($db);$page=(string)($_GET['p']??'home');
$season = in_array($page,['season','match','summary'],true) ? find_season($db, clean_id((string)($_GET['id']??''))) : null;
$match = $page==='match' && $season ? find_match($season,clean_id((string)($_GET['item']??''))) : null;
$summary_part=($_GET['part']??'')==='autumn'?'autumn':'spring';
$season_summary=$page==='summary' && $season?($season['summaries'][$summary_part]??null):null;
$article = null;
if ($page==='article' && $current) foreach($db['seasons'] as $s) foreach($s['articles']??[] as $a) if(($a['id']??'')==($_GET['id']??'')){$article=$a;$season=$s;break 2;}
$other=null;
if($page==='page')foreach($db['extra_pages']??[] as $p) if(($p['id']??'')===($_GET['id']??'')){$other=$p;break;}
$names=['home'=>'Úvod','current'=>'Aktuální sezóna','archive'=>'Minulé sezóny','history'=>'Historie klubu','season'=>$season? 'Sezóna '.$season['label']:'Sezóna nenalezena','article'=>$article?$article['title']:'Článek nenalezen','match'=>$match?'Zápas '.$match['home'].' – '.$match['away']:'Utkání nenalezeno','page'=>$other?$other['title']:'Stránka nenalezena','summary'=>$season?'Souhrn '.$season['label']:'Souhrn sezóny','sponsors'=>'Sponzoři a partneři','contact'=>'Kontakt'];
$title=$names[$page]??'Stránka nenalezena';
function result_class(array $m): string {
    if(!played($m))return '';
    $local=$m['home']==='Sobíňov' ? (int)$m['home_score'] : (int)$m['away_score'];
    $rival=$m['home']==='Sobíňov' ? (int)$m['away_score'] : (int)$m['home_score'];
    return $local>$rival?'win':($local===$rival?'draw':'lose');
}
function game_row(array $m, array $s): void { ?>
  <a class="fixture" href="<?=h(route('match',['id'=>$s['id'],'item'=>$m['id']]))?>" aria-label="Detail zápasu <?=h($m['home'])?> – <?=h($m['away'])?>">
    <span class="fixture-date"><b><?=h(match_day($m))?></b><small><?=h(($m['round']??'').'. kolo')?><?=empty($m['time'])?'':' · '.h($m['time'])?></small></span>
    <span class="fixture-teams"><span class="<?=($m['home']==='Sobíňov'?'team-us':'')?>"><?=h($m['home'])?></span><span class="fixture-vs">—</span><span class="<?=($m['away']==='Sobíňov'?'team-us':'')?>"><?=h($m['away'])?></span></span>
    <span class="fixture-result <?=result_class($m)?>"><?= h(comparison($m)) ?></span>
    <span class="fixture-read"><?=!empty($m['report'])?'Číst report':'Detail zápasu'?></span>
    <span class="fixture-arrow" aria-hidden="true">↗</span>
  </a>
<?php }
function card_article(array $a, array $s): void { ?>
    <a class="news-card" href="<?=h(route('article',['id'=>$a['id']]))?>">
      <div class="news-card-icon"><span>↗</span></div>
      <div class="news-card-content"><span class="eyebrow muted"><?=h(stamp($a['date']??''))?> <span class="dot">·</span> <?=h($s['label'])?></span><h3><?=h($a['title'])?></h3><p><?=h($a['excerpt']??'')?></p><span class="inline-link">Číst článek <span>↗</span></span></div>
    </a>
<?php }
function site_footer(array $db): void { ?>
<footer class="site-footer"><div class="wrap footer-grid">
<div><a class="footer-logo" href="index.php"><img src="assets/logo.png" alt="Znak TJ Sokol Sobíňov"><span><?=h(site_copy($db,'brand_upper','TJ SOKOL'))?><br><?=h(site_copy($db,'brand_lower','SOBÍŇOV'))?></span></a><p><?=nl2br(h(site_copy($db,'footer_description','Klubová tradice od roku 1943.')))?></p></div>
<div><span class="eyebrow footer-label"><?=h(site_copy($db,'footer_club_heading','KLUB'))?></span><a href="<?=h(route('current'))?>"><?=h(site_copy($db,'nav_current','Aktuální sezóna'))?></a><a href="<?=h(route('archive'))?>"><?=h(site_copy($db,'nav_archive','Minulé sezóny'))?></a><a href="<?=h(route('history'))?>"><?=h(site_copy($db,'nav_history','Historie'))?></a><a href="<?=h(route('sponsors'))?>"><?=h(site_copy($db,'nav_sponsors','Sponzoři'))?></a><a href="<?=h(route('contact'))?>"><?=h(site_copy($db,'nav_contact','Kontakt'))?></a></div>
<div><span class="eyebrow footer-label"><?=h(site_copy($db,'footer_social_heading','SLEDUJTE NÁS'))?></span><?php if(external_link(site_copy($db,'facebook'))):?><a href="<?=h(external_link(site_copy($db,'facebook')))?>" target="_blank" rel="noopener noreferrer">Facebook ↗</a><?php endif; ?><?php if(external_link(site_copy($db,'instagram'))):?><a href="<?=h(external_link(site_copy($db,'instagram')))?>" target="_blank" rel="noopener noreferrer">Instagram (fan site) ↗</a><?php endif; ?><?php if(site_copy($db,'email')):?><a href="mailto:<?=h(site_copy($db,'email'))?>"><?=h(site_copy($db,'email'))?></a><?php endif; ?><a href="admin.php">Administrace ↗</a></div>
</div><div class="wrap footer-bottom"><span>© <?=date('Y')?> <?=h(site_copy($db,'name','TJ Sokol Sobíňov'))?></span><span><?=h(site_copy($db,'footer_note','Sobíňov · Vysočina'))?></span></div></footer>
<?php }
?><!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#153626"><meta name="description" content="TJ Sokol Sobíňov – výsledky, zápasy, sezóny a historie fotbalového klubu založeného v roce 1943."><title><?=h($title)?> | TJ Sokol Sobíňov</title><link rel="icon" href="assets/logo.png"><link rel="stylesheet" href="assets/style.css?v=9"><script defer src="assets/app.js"></script></head>
<body><header class="header"><div class="wrap nav-wrap"><a href="index.php" class="brand"><img src="assets/logo.png" alt="Znak TJ Sokol Sobíňov"><span class="brand-text"><?=h(site_copy($db,'brand_upper','TJ SOKOL'))?> <b><?=h(site_copy($db,'brand_lower','SOBÍŇOV'))?></b><small><?=h(site_copy($db,'brand_subtitle','FOTBALOVÝ KLUB · 1943'))?></small></span></a><button class="mobile-menu" type="button" aria-label="Otevřít navigaci" aria-expanded="false">☰</button><nav class="navigation" aria-label="Hlavní navigace"><a class="<?= $page==='home'?'selected':'' ?>" href="index.php"><?=h(site_copy($db,'nav_home','Úvod'))?></a><a class="<?= in_array($page,['current','season','article','match','chronicle','summary'])&&(!in_array($page,['season','match','summary'],true)||($season['status']??'')==='current')?'selected':'' ?>" href="<?=h(route('current'))?>"><?=h(site_copy($db,'nav_current','Aktuální sezóna'))?></a><a class="<?= $page==='archive'||(in_array($page,['season','match','chronicle'],true)&&($season['status']??'')==='past')?'selected':'' ?>" href="<?=h(route('archive'))?>"><?=h(site_copy($db,'nav_archive','Minulé sezóny'))?></a><a class="<?= $page==='history'?'selected':'' ?>" href="<?=h(route('history'))?>"><?=h(site_copy($db,'nav_history','Historie'))?></a><a class="<?= $page==='sponsors'?'selected':'' ?>" href="<?=h(route('sponsors'))?>"><?=h(site_copy($db,'nav_sponsors','Sponzoři'))?></a><a class="<?= $page==='contact'?'selected':'' ?>" href="<?=h(route('contact'))?>"><?=h(site_copy($db,'nav_contact','Kontakt'))?></a><?php foreach(array_slice(array_values(array_filter($db['extra_pages']??[],fn($ep)=>($ep['show_nav']??true))),0,3) as $ep): ?><a href="<?=h(route('page',['id'=>$ep['id']]))?>"><?=h($ep['title'])?></a><?php endforeach; ?></nav><a href="<?=h(route('current'))?>" class="nav-action">Sezóna <?=h($current['label']??'')?> <span>↗</span></a></div></header>
<main>
<?php if($page==='home'): ?>
<section class="hero"><div class="wrap">
  <div class="hero-card">
    <div class="hero-copy">
      <div class="hero-tag"><span class="pulse"></span><?=h(site_copy($db,'hero_kicker','TJ SOKOL SOBÍŇOV / ZALOŽENO 1943'))?></div>
      <h1><?=h(site_copy($db,'hero_title','Fotbal v Sobíňově.'))?></h1>
      <p><?=h(site_copy($db,'hero_description','Aktuální výsledky a historie klubu.'))?></p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?=h(route('current'))?>"><?=h(site_copy($db,'hero_button_current','Aktuální sezóna'))?><span aria-hidden="true">↗</span></a>
        <a class="btn btn-soft" href="<?=h(route('archive'))?>"><?=h(site_copy($db,'hero_button_archive','Archiv sezón'))?><span aria-hidden="true">→</span></a>
      </div>
    </div>
    <aside class="hero-aside" aria-label="O našem klubu">
      <span class="eyebrow green-text"><?=h(site_copy($db,'hero_panel_kicker','ZELENOBÍLÁ TRADICE'))?></span>
      <div class="hero-club">
        <img class="hero-crest" src="assets/logo.png" alt="Znak TJ Sokol Sobíňov" width="92" height="130">
        <div class="hero-club-text"><small><?=h(site_copy($db,'hero_panel_subtitle','FOTBALOVÝ ODDÍL'))?></small><strong><?=h(site_copy($db,'hero_panel_title','TJ Sokol Sobíňov'))?></strong><span><?=h(site_copy($db,'hero_panel_place','Vysočina'))?></span></div>
      </div>
      <div class="hero-aside-footer"><div class="hero-year"><b><?=h(site_copy($db,'hero_founded_year','1943'))?></b><small><?=h(site_copy($db,'hero_year_label','ROK ZALOŽENÍ'))?></small></div><a href="<?=h(route('history'))?>"><?=h(site_copy($db,'hero_history_link','Historie klubu'))?> <span aria-hidden="true">↗</span></a></div>
    </aside>
  </div>
</div></section>
<section class="section wrap" id="prehlad"><div class="section-heading"><div><span class="eyebrow green-text"><?=h(site_copy($db,'home_matches_kicker','AKTUÁLNÍ SEZÓNA'))?></span><h2><?=h(site_copy($db,'home_matches_title','Zápasy a výsledky'))?></h2><p><?=h(site_copy($db,'home_matches_description','Přehled ročníku.'))?> <?=h($current['label']??'')?>.</p></div><a href="<?=h(route('current'))?>" class="text-link"><?=h(site_copy($db,'dashboard_button','Celá sezóna'))?> <span>↗</span></a></div>
<div class="season-dashboard"><div class="season-dashboard-main next-game-card">
  <div class="dashboard-top">
    <span class="eyebrow"><?=h(site_copy($db,'dashboard_next_heading','PŘÍŠTÍ UTKÁNÍ'))?></span>
    <span class="live-dot">NA PROGRAMU</span>
  </div>
  <?php $next=($current?upcoming_matches($current,1):[])[0]??null; if($next): ?>
  <div class="next-game-body">
    <div class="next-game-pairing" aria-label="<?=h($next['home'])?> proti <?=h($next['away'])?>">
      <div class="next-game-team <?=($next['home']==='Sobíňov')?'next-game-ours':''?>">
        <span class="next-game-role">DOMÁCÍ</span>
        <strong><?=h($next['home'])?></strong>
      </div>
      <span class="next-game-versus" aria-hidden="true">VS</span>
      <div class="next-game-team <?=($next['away']==='Sobíňov')?'next-game-ours':''?>">
        <span class="next-game-role">HOSTÉ</span>
        <strong><?=h($next['away'])?></strong>
      </div>
    </div>
    <div class="next-game-meta">
      <span class="next-game-date"><strong><?=h(match_day($next))?></strong><?=empty($next['time'])?'':' · '.h($next['time'])?></span>
      <span class="next-game-league"><?=h($next['round'])?>. kolo · <?=h($current['league'])?></span>
    </div>
  </div>
  <?php else: ?><p class="next-game-empty">Další utkání zatím nebylo vypsáno.</p><?php endif; ?>
  <a href="<?=h(route('current'))?>#rozpis" class="dashboard-link"><?=h(site_copy($db,'dashboard_all_matches','Kompletní rozpis zápasů'))?> <span aria-hidden="true">↗</span></a>
</div><div class="season-dashboard-side"><div class="side-top"><span class="eyebrow"><?=h(site_copy($db,'dashboard_last_heading','POSLEDNÍ VÝSLEDKY'))?></span><span class="side-mark">↗</span></div><?php if($current)foreach(recent_matches($current,3) as $m): ?><a class="recent-match" href="<?=h(route('match',['id'=>$current['id'],'item'=>$m['id']]))?>"><span class="recent-date"><?=h(match_day($m))?></span><div><span><?=h($m['home'])?></span><span><?=h($m['away'])?></span></div><b class="<?=result_class($m)?>"><?=h($m['home_score'])?><br><?=h($m['away_score'])?></b></a><?php endforeach; ?><a href="<?=h(route('current'))?>#rozpis" class="side-link"><?=h(site_copy($db,'dashboard_all_results','Všechny výsledky'))?> <span>→</span></a></div></div></section>
<section class="section wrap team-feature"><div class="section-heading"><div><span class="eyebrow green-text"><?=h(site_copy($db,'home_team_kicker','NAŠE MUŽSTVO'))?></span><h2><?=h(site_copy($db,'home_team_title','Společně za Sobíňov'))?></h2><p><?=h(site_copy($db,'home_team_description','Fotbalové mužstvo TJ Sokol Sobíňov.'))?></p></div></div><figure class="team-photo-card"><?php $teamPhoto=safe_media(site_copy($db,'home_team_photo'));if($teamPhoto):?><img src="<?=h($teamPhoto)?>" alt="Tým TJ Sokol Sobíňov" loading="lazy" onerror="this.style.display='none'" /><?php endif; ?><figcaption><strong><?=h(site_copy($db,'home_team_caption'))?></strong><p><?=nl2br(h(site_copy($db,'home_team_players')))?></p></figcaption></figure><div class="club-links-card"><div><span class="eyebrow green-text"><?=h(site_copy($db,'club_links_title','Fotbal v našem okolí'))?></span><p><?=h(site_copy($db,'home_updates_label'))?></p></div><div class="club-links"><?php foreach($db['club_links']??[] as $link)if(external_link($link['url']??'')):?><a href="<?=h(external_link($link['url']))?>" target="_blank" rel="noopener noreferrer"><?=h($link['name'])?> ↗</a><?php endif;?></div></div></section>
<?php elseif($page==='current' && $current): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">FOTBALOVÁ SEZÓNA / <?=h($current['label'])?></span><h1><?=h(site_copy($db,'current_page_title','Aktuální sezóna'))?></h1><p><?=h(site_copy($db,'current_page_description','Výsledky a rozpis utkání.'))?></p></div></section>
<?php render_season($current,true); ?>
<?php elseif($page==='archive'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">NÁŠ FOTBALOVÝ ARCHIV</span><h1><?=h(site_copy($db,'archive_page_title','Minulé sezóny'))?></h1><p><?=h(site_copy($db,'archive_page_description','Historie našeho fotbalu.'))?></p></div></section>
<section class="wrap archive-section"><div class="archive-toolbar"><div><b><?=count(array_filter($db['seasons'],fn($s)=>$s['status']==='past'))?> sezón</b><span>v klubovém archivu</span></div><label class="search-box"><span>⌕</span><input id="season-search" type="search" placeholder="Hledat sezónu nebo soutěž…" aria-label="Filtrovat minulé sezóny"></label></div>
<div class="archive-table-head"><span>SEZÓNA / SOUTĚŽ</span><span>UMÍSTĚNÍ</span><span>BODY</span><span>SKÓRE</span><span></span></div><div class="archive-list" id="archive-list"><?php foreach($db['seasons'] as $s)if($s['status']==='past'): ?><a class="archive-item" data-search="<?=h(cs_lower($s['label'].' '.$s['league'].' '.$s['rank']))?>" href="<?=h(route('season',['id'=>$s['id']]))?>"><span class="archive-primary"><b><?=h($s['label'])?></b><small><?=h($s['league'])?></small></span><span class="archive-stat"><?=h($s['rank']?:'—')?></span><span class="archive-stat"><?=h($s['points']?:'—')?></span><span class="archive-stat"><?=h($s['goals']?:'—')?></span><span class="archive-go">↗</span></a><?php endif;?></div><div class="archive-no-results" hidden>Pro toto hledání nebyla nalezena žádná sezóna.</div><div class="youth-section"><span class="eyebrow green-text">DĚTSKÝ A MLÁDEŽNICKÝ FOTBAL</span><h2>Záznamy <em>mládeže.</em></h2><div class="youth-grid"><?php foreach($db['extra_pages']??[] as $ep)if(($ep['category']??'')==='youth'):?><a href="<?=h(route('page',['id'=>$ep['id']]))?>"><b><?=h($ep['title'])?></b><span>Otevřít archiv ↗</span></a><?php endif;?></div></div><p class="archive-footnote">Sezóny, výsledky, hodnocení ročníků a jednotlivé zápasové komentáře.</p></section>
<?php elseif($page==='season' && $season): ?>
<section class="page-hero"><div class="wrap"><div class="breadcrumbs"><a href="<?=h(route('archive'))?>">Minulé sezóny</a> / <?=h($season['label'])?></div><span class="eyebrow green-text">KLUBOVÝ ARCHIV / <?=h($season['league'])?></span><h1>Sezóna <em><?=h($season['label'])?>.</em></h1><p><?=h($season['summary']?:'Archiv výsledků a klubových zápasů.')?></p></div></section>
<?php render_season($season,false); ?>
<?php elseif($page==='history'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">OD ROKU 1943</span><h1><?=h(site_copy($db,'history_page_title','Naše historie'))?></h1><p><?=h(site_copy($db,'history_intro','Příběh sobíňovského fotbalu.'))?></p></div></section>
<section class="wrap history-layout"><aside class="history-aside"><div class="history-year">19<br>43<span>ROK ZALOŽENÍ</span></div><div class="history-info"><span class="eyebrow green-text">KLUBOVÝ VÝBOR</span><dl><dt>Předseda</dt><dd><?=h($db['history']['chair'])?></dd><dt>Pokladník</dt><dd><?=h($db['history']['treasurer'])?></dd><dt>Kulturní referenti</dt><dd><?=h($db['history']['culture'])?></dd></dl></div></aside><article class="prose history-prose"><span class="eyebrow green-text">TRADICE, NA KTEROU JSME HRDÍ</span><?=render_rich($db['history']['body'])?><?php if(empty($db['history']['imported'])): ?><div class="history-downloads"><span class="eyebrow green-text">HISTORICKÉ STATISTIKY / SRPEN 2026</span><a href="<?=OLD_SITE?>/dokumenty/2026.08-historicke-statistiky-starty.pdf" target="_blank" rel="noopener">Nejvíce startů hráčů ↗</a><a href="<?=OLD_SITE?>/dokumenty/2026.08-historicke-statistiky-goly.pdf" target="_blank" rel="noopener">Nejlepší střelci ↗</a><a href="<?=OLD_SITE?>/dokumenty/2026.08-historicke-statistiky-golmani.pdf" target="_blank" rel="noopener">Vychytaná čistá konta ↗</a></div><?php endif; ?></article></section>
<?php elseif($page==='match' && $match && $season): ?>
<?php $seasonPage=$season['status']==='current'?'current':'season';$seasonArgs=$season['status']==='current'?[]:['id'=>$season['id']]; ?>
<section class="page-hero match-detail-hero"><div class="wrap"><div class="breadcrumbs"><a href="<?=h(route($seasonPage,$seasonArgs))?>">← Sezóna <?=h($season['label'])?></a> / <?=h(($match['round']??'—').'. kolo')?></div><span class="eyebrow green-text">ZÁPASOVÝ DETAIL · <?=h($season['label'])?></span><h1>Průběh <em>utkání.</em></h1><p><?=h($season['league'])?> · <?=h(match_day($match))?><?=empty($match['time'])?'':' · '.h($match['time'])?></p></div></section>
<div class="wrap match-detail-layout">
  <div class="match-score-card"><div class="detail-team"><span class="detail-monogram"><?=first_cap($match['home'])?></span><h2><?=h($match['home'])?></h2><small>DOMÁCÍ</small></div><div class="detail-score"><strong class="<?=result_class($match)?>"><?=h(comparison($match))?></strong><span><?=played($match)?'KONEČNÝ VÝSLEDEK':'ZÁPAS NA PROGRAMU'?></span><?php if(!empty($match['halftime'])):?><small>Poločas <?=h($match['halftime'])?></small><?php endif;?></div><div class="detail-team"><span class="detail-monogram"><?=first_cap($match['away'])?></span><h2><?=h($match['away'])?></h2><small>HOSTÉ</small></div></div>
  <div class="match-report-paper"><div class="match-report-head"><div><span class="eyebrow green-text">KLUBOVÝ ZÁPIS</span><h2>Report ze zápasu</h2></div><a class="admin-edit-public" href="<?=h(admin_route('match',['id'=>$season['id'],'item'=>$match['id']]))?>">Upravit v administraci ↗</a></div><?php if(!empty($match['report'])):?><div class="prose match-report-content"><?=render_rich($match['report'])?></div><?php else:?><div class="report-empty"><b>Report zatím není k dispozici.</b><p>Výsledek a základní údaje jsou uvedeny výše. Komentář můžete kdykoliv doplnit v administraci.</p></div><?php endif;?></div>
  <div class="match-detail-footer"><a href="<?=h(route($seasonPage,$seasonArgs))?>#rozpis">← Zpět na všechna utkání</a></div>
</div>
<?php elseif($page==='summary' && $season): ?>
<section class="page-hero smaller"><div class="wrap"><div class="breadcrumbs"><a href="<?=h(route(($season['status']==='current'?'current':'season'),($season['status']==='current'?[]:['id'=>$season['id']])))?>">← Sezóna <?=h($season['label'])?></a></div><span class="eyebrow green-text">HODNOCENÍ ROČNÍKU / <?=h($season['label'])?></span><h1><?=h($season_summary['title']??($summary_part==='autumn'?'Hodnocení po podzimu':'Hodnocení po sezóně'))?></h1><p><?=h($season['league'])?></p></div></section>
<section class="wrap summary-detail"><div class="summary-paper"><?php if(!empty($season_summary['photo'])&&safe_media($season_summary['photo'])):?><figure class="summary-figure"><img src="<?=h(safe_media($season_summary['photo']))?>" alt="Fotografie týmu u hodnocení sezóny" loading="lazy"><figcaption><?=h($season_summary['caption']??'')?></figcaption></figure><?php endif; ?><?php if(!empty($season_summary['body'])):?><article class="prose summary-prose"><?=render_rich($season_summary['body'])?></article><?php else:?><p class="report-empty">Hodnocení této části sezóny zatím nebylo doplněno.</p><?php endif;?></div></section>
<?php elseif($page==='sponsors'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">PARTNEŘI KLUBU</span><h1><?=h(site_copy($db,'sponsors_page_title','Naši sponzoři'))?></h1><p><?=h(site_copy($db,'sponsors_intro'))?></p></div></section>
<section class="wrap sponsors-layout"><div class="sponsors-grid"><?php foreach($db['sponsors']??[] as $sp):?><article class="sponsor-card"><?php if(safe_media($sp['image']??'')):?><div class="sponsor-image"><img src="<?=h(safe_media($sp['image']))?>" alt="<?=h($sp['name'])?>" loading="lazy" onerror="this.style.display='none'"></div><?php endif;?><h2><?=h($sp['name'])?></h2><p><?=h($sp['description']??'')?></p><?php if(external_link($sp['url']??'')):?><a href="<?=h(external_link($sp['url']))?>" target="_blank" rel="noopener noreferrer">Web partnera ↗</a><?php endif;?></article><?php endforeach;?></div><div class="funding-card"><span class="eyebrow green-text">DOTAČNÍ PODPORA</span><h2><?=h(site_copy($db,'sponsors_support_title','Podpora našeho klubu'))?></h2><p><?=nl2br(h(site_copy($db,'sponsors_funding')))?></p></div></section>
<?php elseif($page==='contact'): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">KONTAKT A SOCIÁLNÍ SÍTĚ</span><h1><?=h(site_copy($db,'contact_page_title','Buďte s námi v kontaktu'))?></h1><p><?=h(site_copy($db,'contact_intro'))?></p></div></section><section class="wrap contact-layout"><div class="contact-card"><span class="eyebrow green-text">NAPIŠTE NÁM</span><h2>E-mail</h2><a href="mailto:<?=h(site_copy($db,'email'))?>"><?=h(site_copy($db,'email'))?></a><?php if(site_copy($db,'contact')):?><p><?=nl2br(h(site_copy($db,'contact')))?></p><?php endif;?></div><div class="contact-card"><span class="eyebrow green-text">SLEDUJTE KLUB</span><h2>Sociální sítě</h2><?php foreach(['facebook'=>'Facebookové stránky','instagram'=>'Instagram (fan site)'] as $key=>$label)if(external_link(site_copy($db,$key))):?><a href="<?=h(external_link(site_copy($db,$key)))?>" target="_blank" rel="noopener noreferrer"><?=h($label)?> ↗</a><?php endif;?></div></section>
<?php elseif($page==='article' && $article && $season): ?>
<section class="page-hero smaller"><div class="wrap"><div class="breadcrumbs"><a href="<?=h(route($season['status']==='current'?'current':'season', $season['status']==='current'?[]:['id'=>$season['id']]))?>">← Zpět na sezónu</a></div><span class="eyebrow green-text"><?=h(stamp($article['date']))?> / <?=h($season['label'])?></span><h1 class="article-title"><?=h($article['title'])?></h1><p><?=h($article['excerpt']??'')?></p></div></section><article class="wrap prose article-body"><?=render_rich($article['body']??'')?></article>
<?php elseif($page==='page' && $other): ?>
<section class="page-hero"><div class="wrap"><span class="eyebrow green-text">TJ SOKOL SOBÍŇOV</span><h1><?=h($other['title'])?></h1></div></section><article class="wrap prose article-body"><?=render_rich($other['body']??'')?></article>
<?php else: ?><section class="page-hero"><div class="wrap"><h1>Stránka <em>nenalezena.</em></h1><p>Požadovaný obsah neexistuje.</p><a class="btn btn-primary" href="index.php">Vrátit se na úvod ↗</a></div></section><?php http_response_code(404);endif; ?>
</main><?php site_footer($db); ?></body></html>
<?php
function render_season(array $s,bool $isCurrent): void { $db=db(); ?>
<section class="wrap season-section"><div class="season-headline"><div><span class="eyebrow green-text"><?= $isCurrent?'TENTO ROČNÍK':'KONEČNÁ BILANCE' ?></span><h2><?=h($s['league'])?></h2><p><?=h($s['summary'])?></p></div><div class="season-metrics"><div><span>UMÍSTĚNÍ</span><b><?=h($s['rank']?:'—')?></b></div><div><span>BODY</span><b><?=h($s['points']?:'—')?></b></div><div><span>SKÓRE</span><b><?=h($s['goals']?:'—')?></b></div></div></div>
<?php $summaries=$s['summaries']??[]; $hasSpring=count(array_filter($s['matches']??[],fn($m)=>(int)substr($m['date']??'',5,2)>0 && (int)substr($m['date']??'',5,2)<7))>0;$showSpringFirst=!$isCurrent&&$hasSpring; ?>
<?php if(!empty($s['matches'])): ?><div class="content-panel" id="rozpis"><div class="content-panel-head"><div><span class="eyebrow green-text"><?=h(site_copy($db,'fixtures_kicker','VÝSLEDKY A TERMÍNY'))?></span><h2><?=h(site_copy($db,'fixtures_title','Zápasy a reporty'))?></h2><p class="fixture-instruction"><?=h(site_copy($db,'fixtures_description','Klikněte na utkání pro komentář.'))?></p></div><span class="panel-counter"><?=count($s['matches'])?> UTKÁNÍ</span></div>
<?php $groups=['podzim'=>[],'jaro'=>[],'neurceno'=>[]];foreach(sorted_matches($s['matches']) as $m){$month=(int)substr($m['date']??'',5,2);$part=$month>=7?'podzim':($month>0?'jaro':'neurceno');$groups[$part][]=$m;} if($showSpringFirst)$groups=['jaro'=>$groups['jaro'],'podzim'=>$groups['podzim'],'neurceno'=>$groups['neurceno']]; ?>
<div class="fixture-rows"><?php foreach($groups as $part=>$items):if(!$items)continue;?><div class="fixture-group-title"><span><?=['podzim'=>'Podzimní část','jaro'=>'Jarní část','neurceno'=>'Další zápasy'][$part]?></span><small><?=count($items)?> utkání</small></div><?php foreach($items as $m)game_row($m,$s);endforeach;?></div></div><?php endif; ?>
<div class="season-summary-section"><div class="section-heading"><div><span class="eyebrow green-text"><?=h(site_copy($db,'summaries_kicker','BILANCE SEZÓNY'))?></span><h2><?=h(site_copy($db,'summaries_title','Hodnocení ročníku'))?></h2><p><?=h(site_copy($db,'summaries_description','Hodnocení podzimu a jara.'))?></p></div></div><div class="summary-card-grid"><?php $summaryLabels=['spring'=>site_copy($db,'summary_spring_label','Po jarní části'),'autumn'=>site_copy($db,'summary_autumn_label','Po podzimu')];if($isCurrent)$summaryLabels=array_reverse($summaryLabels,true);foreach($summaryLabels as $part=>$name):$summary=$summaries[$part]??[];?><a class="summary-card" href="<?=h(route('summary',['id'=>$s['id'],'part'=>$part]))?>"><?php if(!empty($summary['photo'])&&safe_media($summary['photo'])):?><img src="<?=h(safe_media($summary['photo']))?>" alt="Fotografie týmu" loading="lazy"><?php endif;?><div><span class="eyebrow green-text"><?=h($name)?> · <?=h($s['label'])?></span><h3><?=h($summary['title']??($part==='autumn'?'Hodnocení po podzimu':'Hodnocení po sezóně'))?></h3><span class="summary-more"><?=empty($summary['body'])?'Zatím bez textu':'Přečíst hodnocení'?> ↗</span></div></a><?php endforeach;?></div></div>
</section>
<?php }