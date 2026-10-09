<?php
$archive = [
 ['2025/2026','8. liga mužů – REALITY 11','6. místo','44','73:72',''],
 ['2024/2025','8. liga mužů – REALITY 11','9. místo','31','56:81',''],
 ['2023/2024','III. třída Havlíčkův Brod','2. místo','57','112:33','Postup do okresního přeboru.'],
 ['2022/2023','III. třída Havlíčkův Brod','5. místo','43','71:55',''],
 ['2021/2022','III. třída Havlíčkův Brod','4. místo','41','60:49',''],
 ['2020/2021','III. třída Havlíčkův Brod','4. místo','18','22:18','Soutěž předčasně ukončena kvůli COVID-19.'],
 ['2019/2020','III. třída Havlíčkův Brod','3. místo','27','40:23','Jarní část se kvůli pandemii nehrála.'],
 ['2018/2019','III. třída Havlíčkův Brod','3. místo','46','74:34',''],
 ['2017/2018','III. třída Havlíčkův Brod','6. místo','40','70:49',''],
 ['2016/2017','III. třída Havlíčkův Brod','8. místo','32','50:47',''],
 ['2015/2016','III. třída Havlíčkův Brod','5. místo','38','54:43',''],
 ['2014/2015','III. třída Havlíčkův Brod','11. místo','31','42:49',''],
 ['2013/2014','III. třída Havlíčkův Brod','6. místo','39','61:51',''],
 ['2012/2013','III. třída Havlíčkův Brod','4. místo','45','65:43',''],
 ['2011/2012','Okresní přebor Havlíčkův Brod','13. místo','24','46:65',''],
 ['2010/2011','Okresní přebor Havlíčkův Brod','12. místo','19','41:62',''],
 ['2009/2010','Okresní přebor Havlíčkův Brod','9. místo','33','48:47',''],
 ['2008/2009','Okresní přebor Havlíčkův Brod','8. místo','40','58:56',''],
 ['2007/2008','Okresní přebor Havlíčkův Brod','7. místo','34','71:47',''],
 ['2006/2007','III. třída Havlíčkův Brod','1. místo','62','86:26','Postup do okresního přeboru.'],
 ['2005/2006','IV. třída Havlíčkův Brod','1. místo','56','73:21','Postup do III. třídy.'],
 ['2004/2005','IV. třída Havlíčkův Brod','3. místo','45','65:32',''],
];
$seasons=[];
foreach($archive as [$label,$league,$place,$points,$score,$note]) {
    $year=explode('/',$label)[0];
    $ext = (int)$year <= 2015 ? 'htm' : 'html';
    $seasons[]=['id'=>str_replace('/','_',$label),'label'=>$label,'league'=>$league,'status'=>'past','rank'=>$place,'points'=>$points,'goals'=>$score,'summary'=>$note,'matches'=>[],'articles'=>[],'legacy_html'=>'','original_url'=>OLD_SITE.'/sezony/'.str_replace('/','_',$label).'.'.$ext];
}
$current = [
 ['2026-08-08','2','Česká Bělá','Sobíňov','3','4','2:2'],
 ['2026-08-15','3','Sobíňov','Šmolovy','1','0','0:0'],
 ['2026-08-23','4','Habry','Sobíňov','2','1','1:1'],
 ['2026-08-29','5','Sobíňov','Dlouhá Ves','1','3','0:1'],
 ['2026-09-05','6','Havlíčkův Brod C','Sobíňov','2','1','2:1'],
 ['2026-09-12','7','Sobíňov','Jeřišno','1','6','1:3'],
 ['2026-09-20','8','Tis B','Sobíňov','1','1','0:1'],
 ['2026-09-26','9','Sobíňov','Ledeč n. Sázavou B','6','0','2:0'],
 ['2026-10-03','10','Chotěboř C','Sobíňov','4','0','2:0'],
 ['2026-10-11','11','Sobíňov','Světlá n. Sázavou B','','','15:00'],
 ['2026-10-18','12','Leština','Sobíňov','','','14:30'],
 ['2026-10-24','13','Sobíňov','Mírovka B','','','14:30'],
 ['2026-10-31','1','Sobíňov','Keřkov','','','14:00'],
];
$matches=[];
foreach($current as $i=>$m) {[$date,$round,$home,$away,$hs,$as,$extra]=$m;
  $matches[]=['id'=>'m'.($i+1),'date'=>$date,'round'=>$round,'home'=>$home,'away'=>$away,'home_score'=>$hs,'away_score'=>$as,'halftime'=>($hs!==''?$extra:''),'time'=>($hs===''?$extra:''),'report'=>''];
}
$articles=[
 ['id'=>'a1','title'=>'Chotěboř C – Sobíňov 4:0','date'=>'2026-10-03','excerpt'=>'Na hřišti vedoucí Chotěboře jsme tentokrát nestačili. Domácí využili převahu a zaslouženě zvítězili.','body'=>'<p>Po vysoké domácí výhře nad rezervou Ledče jsme zajížděli na hřiště vedoucí Chotěboře „C“, kde nás čekala výrazně těžší prověrka. Domácí šli do vedení už v 9. minutě a do poločasu zvýšili na 2:0. Ani druhý poločas nám nevyšel, domácí přidali další dva góly a zvítězili 4:0.</p><p>Příště nás čeká domácí utkání se Světlou nad Sázavou „B“.</p>'],
 ['id'=>'a2','title'=>'Sobíňov – Ledeč n. Sázavou B 6:0','date'=>'2026-09-26','excerpt'=>'Po sérii utkání bez výhry přišlo přesvědčivé domácí vítězství. Šest branek a čisté konto.','body'=>'<p>Po čtyřech porážkách a následné remíze jsme se konečně dočkali vítězství. Rezervu Ledče jsme doma porazili 6:0. Od začátku jsme byli aktivnějším a nebezpečnějším týmem a vypracovali si řadu příležitostí.</p>'],
];
$history = <<<'HTML'
<p>Kořeny sobíňovského fotbalu sahají do roku 1943, kdy vznikl fotbalový oddíl a na hřišti „Na Kačinách“ se začaly hrát zápasy. O pozemek pro vybudování hřiště požádaly Dělnická tělovýchovná jednota a Sokol již v roce 1938.</p>
<h2>Od prvních zápasů k okresním soutěžím</h2>
<p>Po válce hrálo mužstvo přátelská i mistrovská utkání na Hlinecku. Na venkovní zápasy se jezdilo vlakem, někdy i na kole nebo na motocyklu. V padesátých letech hrál tým na Chotěbořsku. Mezi významné osobnosti klubu patřili Stanislav Vojíř a Stanislav Kafka.</p>
<p>V roce 1972 vznikla zděná kabina a místní trávník patřil mezi nejlépe udržované na okrese. V roce 1974 bylo založeno také družstvo žáků, později dorostu.</p>
<h2>Vzestupy, pády a nová generace</h2>
<p>Po krizi na přelomu sedmdesátých a osmdesátých let se klub znovu postavil na nohy díky obětavým funkcionářům. Od roku 1981 hrál okresní přebor. Roku 1988 se v Sobíňově odehrálo památné přátelské utkání se slavnou Amforou.</p>
<p>Další velký úspěch přišel v sezónách 2005/2006 a 2006/2007, kdy mužstvo vybojovalo dva postupy po sobě a pět let působilo v okresním přeboru. Po letech ve III. třídě se do okresního přeboru vrátilo v roce 2024.</p>
<h2>Osobnosti a klubová kronika</h2>
<p>Mezi nejznámější odchovance patří Bedřich Hamsa, který nastoupil v české a československé nejvyšší soutěži do 82 utkání. Historické fotografie a část údajů pochází z Kroniky sobíňovského fotbalu, o niž se dlouhá léta stará Zdeněk Chmelík starší.</p>
<p><a href="https://fotbalsobinov.unas.cz/historie.html">Původní úplná verze historie a historické fotografie ↗</a></p>
HTML;
return [
 'settings'=>['name'=>'TJ Sokol Sobíňov','tagline'=>'Fotbal se srdcem. Od roku 1943.','email'=>'','contact'=>'','season_note'=>'8. liga mužů – REALITY 11 • Okresní přebor Havlíčkův Brod'],
 'history'=>['body'=>$history,'chair'=>'Daniel Říčan','treasurer'=>'Michal Vopršal','culture'=>'David Vrána, Marek Ondráček, Pavel Kafka'],
 'extra_pages'=>[
 ['id'=>'pripravka-2018','title'=>'Přípravka 2018/2019','category'=>'youth','show_nav'=>false,'legacy_source'=>OLD_SITE.'/sezony/2018_2019_pripravka.html','imported'=>false,'body'=>'<p>Dobové zápisy přípravky ze sezóny 2018/2019.</p><p><a href="https://fotbalsobinov.unas.cz/sezony/2018_2019_pripravka.html">Původní stránka přípravky ↗</a></p>'],
 ['id'=>'zaci-2016','title'=>'Žáci 2016/2017','category'=>'youth','show_nav'=>false,'legacy_source'=>OLD_SITE.'/sezony/2016_2017_zaci.html','imported'=>false,'body'=>'<p>Hodnocení sezóny žáků 2016/2017.</p><p><a href="https://fotbalsobinov.unas.cz/sezony/2016_2017_zaci.html">Původní stránka žáků ↗</a></p>'],
 ['id'=>'zaci-2014','title'=>'Žáci 2014/2015','category'=>'youth','show_nav'=>false,'legacy_source'=>OLD_SITE.'/sezony/2014_2015_zaci.htm','imported'=>false,'body'=>'<p>Dobový archiv žáků ze sezóny 2014/2015.</p><p><a href="https://fotbalsobinov.unas.cz/sezony/2014_2015_zaci.htm">Původní stránka žáků ↗</a></p>'],
 ],
 'seasons'=>array_merge([['id'=>'2026_2027','label'=>'2026/2027','league'=>'8. liga mužů – REALITY 11','status'=>'current','rank'=>'','points'=>'','goals'=>'','summary'=>'Nový ročník okresního přeboru Havlíčkobrodska. Výsledky, rozpis zápasů a aktuální dění na jednom místě.','matches'=>$matches,'articles'=>$articles,'legacy_html'=>'','original_url'=>OLD_SITE.'/sezony/2026_2027.html']],$seasons),
 'updated_at'=>date('c'),
];
