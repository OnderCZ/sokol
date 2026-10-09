# TJ Sokol Sobíňov — moderní web, verze 5

Responzivní web pro PHP 8+ bez databáze, se správou zápasů, reportů, hodnocení sezón, fotografií, sponzorů, historie a textů celého webu.

## Co je nového oproti verzi 4

- **Jednodušší úvod:** místo vysoké tmavozelené obdélníkové karty je kompaktní jednotná světlá plocha s nadpisem a odkazy vlevo, klubovým znakem a identitou vpravo. Bez velkých dekorací. Na telefonu se přirozeně skládá pod sebe.
- **Tabulky ve všech veřejných textech:** společné odsazení, zelené záhlaví, jemně střídané řádky, zvýraznění Sobíňova, přehledné číselné sloupce a horizontální posouvání na mobilu. Funguje u historických mládežnických tabulek, reportů, sezónních hodnocení, historie i vlastních stránek. Změna se aplikuje při zobrazení — nezasahuje do uložených historických textů.
- **Administrace jednodušší:** odstraněna položka „Import archivu“, importní akce a editor původního dobového HTML v jednotlivých sezónách. Staré adresy editoru/importu otevřou seznam sezón. Dobový archiv nemá veřejnou stránku; nejedná se o smazání dat.
- **Upravitelný úvod:** v administraci v sekci Nastavení lze upravit nadpis, podnadpis, název a texty u znaku, rok založení i název odkazu „Historie klubu“.

## Zachovaný obsah

- 23 sezón, 548 strukturovaných zápasů a jejich dříve přiřazené reporty.
- Obě samostatná hodnocení sezóny (podzim/jaro), možnost nahrávat jejich fotografie.
- Sponzoři, týmová fotografie, historie, kontakty, sociální odkazy a mládežnické stránky.
- Úplné historické podklady a přílohy ve `storage/` a `archive-files/`, včetně původního HTML jako interní zálohy. Nejsou veřejně nabízeny jako další duplicitní stránky.

**Poznámka k obsahu:** Automaticky přiřazené historické reporty, souhrny a fotografie je pořád vhodné jednotlivě zkontrolovat. Tato verze nemění historická data ze čtvrté verze.

## Instalace a aktualizace

1. Hosting potřebuje PHP 8+ a zápis do složek `storage/` a `uploads/`. Rozbalte ZIP do kořene webu; doporučujeme HTTPS.
2. Při první instalaci otevřete `admin.php` a nastavte heslo alespoň 12 znaků.
3. **Při aktualizaci existující instalace nejprve zálohujte `storage/`, `uploads/` a `archive-files/`!** Nepřepisujte vlastní upravený `storage/content.php`, protože byste přišli o mezitím uložené úpravy. Pokud už v4 běží na serveru a obsah jste tam editovali, nahrajte z v5 pouze `index.php`, `admin.php`, `bootstrap.php`, `assets/style.css` a `assets/admin.js`; stávající obsah ponechte.
4. Pokud instalaci aktualizujete z v4, po záloze můžete ze serveru odstranit starý `lib_import.php` a adresář `tools/`, protože už nejsou potřeba. Souborů `storage/source_*.php` a obsahu složky `archive-files/` se toto netýká — **ty nemažte**.
5. V administraci nastavíte texty a kontakty přes **Nastavení**, sezónní hodnocení a zápasy přes **Sezóny a zápasy**.

Podrobnější český postup je v přiloženém dokumentu `NAVOD_K_WEBU_V5.md` (nebo samostatně ke stažení).

## Lokální spuštění

```sh
php -S localhost:8000
```

Web: `http://localhost:8000/` • Administrace: `http://localhost:8000/admin.php`

Data používají soubory PHP s neaktivním JSON za bezpečnostní hlavičkou a přístup do složky `storage/` je navíc chráněn `.htaccess`. Zachovejte ochranné soubory `.htaccess`.

## Verze 6
Aktualizovanou kartu pristiho utkani a instrukce najdete v NAVOD_K_WEBU_V6.md.
