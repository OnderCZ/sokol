<?php
declare(strict_types=1);

const SITE_ROOT = __DIR__;
const OLD_SITE = 'https://fotbalsobinov.unas.cz';
const STORE_FILE = __DIR__ . '/storage/content.php';
const AUTH_FILE = __DIR__ . '/storage/auth.php';

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function load_php_json(string $file): ?array {
    if (!is_file($file)) return null;
    $raw = file_get_contents($file);
    if ($raw === false) return null;
    $pos = strpos($raw, "\n");
    return json_decode(substr($raw, $pos === false ? 0 : $pos + 1), true, 512, JSON_THROW_ON_ERROR);
}
function save_php_json(string $file, array $data): void {
    $bytes = "<?php http_response_code(403); exit; ?>\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    $tmp = tempnam(dirname($file), 'new_');
    if ($tmp === false || file_put_contents($tmp, $bytes, LOCK_EX) === false || !rename($tmp, $file)) throw new RuntimeException('Soubor nelze uložit. Zkontrolujte oprávnění složky storage.');
    @chmod($file, 0640);
}
function db(): array { return load_php_json(STORE_FILE) ?? require SITE_ROOT . '/seed.php'; }
function save_db(array $db): void { $db['updated_at'] = date('c'); save_php_json(STORE_FILE, $db); }
function active_season(array $db): ?array { foreach ($db['seasons'] as $s) if (($s['status'] ?? '') === 'current') return $s; return null; }
function find_season(array $db, string $id): ?array { foreach ($db['seasons'] as $s) if ($s['id'] === $id) return $s; return null; }
function find_match(array $s, string $id): ?array { foreach ($s['matches'] ?? [] as $m) if ($m['id'] === $id) return $m; return null; }
function route(string $page, array $args=[]): string { return 'index.php?' . http_build_query(['p'=>$page] + $args); }
function admin_route(string $page, array $args=[]): string { return 'admin.php?' . http_build_query(['p'=>$page] + $args); }
function stamp(string $s): string { return $s ? date('j. n. Y', strtotime($s)) : '—'; }
function comparison(array $m): string { return (($m['home_score'] ?? '') !== '' && ($m['away_score'] ?? '') !== '') ? $m['home_score'] . ':' . $m['away_score'] : '– : –'; }
function played(array $m): bool {return ($m['home_score'] ?? '') !== '' && ($m['away_score'] ?? '') !== ''; }
function match_day(array $m): string {return !empty($m['date']) ? date('j. n. Y', strtotime($m['date'])) : ($m['date_label'] ?? 'Datum neurčeno');}
function sorted_matches(array $matches, bool $descending=false): array { usort($matches,fn($a,$b)=>strcmp($a['date']??'', $b['date']??'') * ($descending ? -1 : 1)); return $matches; }
function recent_matches(array $s, int $limit=3): array { return array_slice(array_values(array_filter(sorted_matches($s['matches'] ?? [],true),'played')),0,$limit); }
function upcoming_matches(array $s, int $limit=3): array { return array_slice(array_values(array_filter(sorted_matches($s['matches'] ?? []),fn($m)=>!played($m))),0,$limit); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function verify_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf']??''))) {http_response_code(403); exit('Neplatný bezpečnostní token. Obnovte stránku.');} }
function csrf_input(): string {return '<input type="hidden" name="csrf" value="'.h(csrf()).'">';}
function redirect(string $url): never {header('Location: '.$url, true, 303);exit;}
function clean_text(mixed $v, int $max=12000): string { $v=trim((string)$v); return function_exists('mb_substr') ? mb_substr($v,0,$max) : substr($v,0,$max*4); }
function cs_lower(string $v): string { return function_exists('mb_strtolower') ? mb_strtolower($v,'UTF-8') : strtr(strtolower($v),['Á'=>'á','Č'=>'č','Ď'=>'ď','É'=>'é','Ě'=>'ě','Í'=>'í','Ň'=>'ň','Ó'=>'ó','Ř'=>'ř','Š'=>'š','Ť'=>'ť','Ú'=>'ú','Ů'=>'ů','Ý'=>'ý','Ž'=>'ž']); }
function first_cap(string $v): string {preg_match('/^./us',$v,$m);$x=$m[0]??'?';return function_exists('mb_strtoupper') ? mb_strtoupper($x,'UTF-8') : strtoupper($x);}
function clean_id(string $v): string { return preg_replace('/[^a-zA-Z0-9_-]/','', $v) ?: ''; }
function new_id(): string {return bin2hex(random_bytes(6));}
function safe_url(string $url): string {
    $url=trim($url);
    if (preg_match('~^https?://~i',$url)) return $url;
    if (str_starts_with($url,'/')) return OLD_SITE.$url;
    return '';
}
function sanitize_html(string $html): string {
    // Only an explicit allowlist of structural HTML survives. Attributes are rebuilt.
    $html=preg_replace('~<(script|style|iframe|object|embed|svg|form|video|audio|noscript)\b[^>]*>.*?</\1\s*>~isu','',$html);
    $html=preg_replace('~<!--.*?-->~s','',$html);
    $tags='<div><p><br><strong><em><b><i><u><h2><h3><h4><ul><ol><li><table><thead><tbody><tr><th><td><blockquote><a><img><hr><span><figure><figcaption>';
    $html=strip_tags($html,$tags);
    return preg_replace_callback('~<\s*(/?)\s*([a-z][a-z0-9]*)\b([^>]*)>~iu',function($m) {
        $closing=$m[1]==='/';$tag=strtolower($m[2]);
        if($closing)return '</'.$tag.'>';
        $attrs=$m[3];
        if(in_array($tag,['th','td'],true)) {
            $out='<'.$tag;
            foreach(['colspan','rowspan'] as $attr) {
                if(preg_match('~\b'.$attr.'\s*=\s*[\"\']?([0-9]{1,2})~i',$attrs,$size) && (int)$size[1]>=1 && (int)$size[1]<=20) $out.=' '.$attr.'="'.$size[1].'"';
            }
            return $out.'>';
        }
        if(!in_array($tag,['a','img'],true))return '<'.$tag.'>';
        $name=$tag==='a'?'href':'src';
        if(!preg_match('~\b'.$name.'\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~i',$attrs,$match))return '<'.$tag.'>';
        $url=html_entity_decode($match[1]?:($match[2]?:($match[3]??'')),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $allowed=preg_match('~^https?://[^\s<>"]+$~i',$url) || (preg_match('~^(?:uploads|archive-files)/[a-zA-Z0-9_.-]+$~',$url)) || ($tag==='a'&&preg_match('~^index\.php\?[^<>"]+$~',$url));
        if(!$allowed)return '<'.$tag.'>';
        return '<'.$tag.' '.$name.'="'.h($url).'"'.($tag==='a' && !str_starts_with($url,'index.php?')?' rel="noopener noreferrer" target="_blank"':($tag==='a'?'':' loading="lazy" alt="Klubová fotografie"')).'>';
    },$html)??'';
}
/** Styling imported and newly edited tables consistently without changing saved content. */
function render_rich(string $html): string {
    $safe=sanitize_html($html);
    if (stripos($safe, '<table')===false) return $safe;
    // Wrap only in the view, never when saving, so archived HTML remains untouched.
    $safe=preg_replace('~<table(?:\s[^>]*)?>~i','<div class="rich-table-scroll" role="region" aria-label="Tabulka údajů – posuňte do strany pro další sloupce" tabindex="0"><table>',$safe);
    $safe=preg_replace('~</table\s*>~i','</table></div>',$safe);
    // A clearly identifiable club row makes large historical league tables easier to scan.
    $safe=preg_replace_callback('~<tr>(.*?)</tr>~is',static function($m) {
        return preg_match('~sobíňov~iu',strip_tags($m[1]))
          ? '<tr class="club-table-row">'.$m[1].'</tr>' : $m[0];
    },$safe);
    return $safe;
}
function session_start_safe(): void {
    if (session_status()!==PHP_SESSION_ACTIVE) {
        $secure=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off';
        session_name('sobinov_admin');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
        session_start();
    }
}
function is_logged(): bool {return ($_SESSION['admin']??false)===true;}
function require_login(): void {if(!is_logged()) redirect(admin_route('login'));}
function flash(string $msg): void {$_SESSION['flash']=$msg;}
function take_flash(): string {$f=$_SESSION['flash']??'';unset($_SESSION['flash']);return $f;}

/** Public-site copy is kept in settings; historic installations get safe defaults. */
function site_copy(array $db,string $key,string $fallback=''): string {return (string)($db['settings'][$key]??$fallback);}
function safe_media(string $url): string {return (bool)preg_match('~^(?:uploads|archive-files|assets)/[a-zA-Z0-9_.-]+$~',$url) || (bool)preg_match('~^https?://[^\s<>"\']+$~i',$url)?$url:'';}
function external_link(string $url): string {return preg_match('~^https?://[^\s<>"\']+$~i',$url)?$url:'';}

function public_text_fields(): array {
  return [
   'Úvodní klubová karta'=>[
    'hero_founded_year'=>'1943','hero_year_label'=>'ROK ZALOŽENÍ','hero_history_link'=>'Historie klubu',
   ],
   'Hlavička a navigace'=>[
    'nav_home'=>'Úvod','nav_current'=>'Aktuální sezóna','nav_archive'=>'Minulé sezóny',
    'nav_history'=>'Historie','nav_sponsors'=>'Sponzoři','nav_contact'=>'Kontakt',
   ],
   'Tlačítka a označení na úvodu'=>[
    'hero_button_current'=>'Aktuální sezóna','hero_button_archive'=>'Archiv sezón',
    'dashboard_button'=>'Celá sezóna','dashboard_next_heading'=>'PŘÍŠTÍ UTKÁNÍ',
    'dashboard_last_heading'=>'POSLEDNÍ VÝSLEDKY',
    'dashboard_all_matches'=>'Kompletní rozpis zápasů',
    'dashboard_all_results'=>'Všechny výsledky',
   ],
   'Podstránky a názvy rubrik'=>[
    'current_page_title'=>'Aktuální sezóna','current_page_description'=>'Výsledky, rozpis utkání a vše důležité o našem týmu.',
    'archive_page_title'=>'Minulé sezóny','archive_page_description'=>'Více než dvě desetiletí klubových výsledků, úspěchů a vzpomínek.',
    'history_page_title'=>'Naše historie','fixtures_kicker'=>'VÝSLEDKY A TERMÍNY',
    'fixtures_title'=>'Zápasy a reporty','fixtures_description'=>'Kliknutím na utkání otevřete celý zápasový komentář.',
    'summaries_kicker'=>'BILANCE SEZÓNY','summaries_title'=>'Hodnocení ročníku',
    'summaries_description'=>'Samostatné hodnocení podzimní a jarní části, včetně dobových fotografií.',
    'summary_autumn_label'=>'Po podzimu','summary_spring_label'=>'Po jarní části',
    'sponsors_page_title'=>'Naši sponzoři','contact_page_title'=>'Buďte s námi v kontaktu',
    'sponsors_support_title'=>'Podpora našeho klubu',
   ],
   'Patička'=>['footer_club_heading'=>'KLUB','footer_social_heading'=>'SLEDUJTE NÁS']
  ];
}
