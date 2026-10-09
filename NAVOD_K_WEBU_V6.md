# TJ Sokol Sobíňov – aktualizace na verzi 6

## Co je nové

Na úvodní stránce v sekci **Zápasy a výsledky** je přepracovaná karta **Příští utkání**. Místo písmen v rámečcích se zobrazují plné názvy domácího a hostujícího týmu. Datum, čas a soutěž jsou ve stejném přehledném bloku. Opravený kontrast všech textů zaručuje čitelnost na bílém pozadí. Přizpůsobení funguje i na telefonech.

## Aktualizace již fungujícího webu

1. Před změnou doporučujeme zálohovat současnou instalaci, zejména složky `storage/` a `uploads/`.
2. Z balíčku **rychlá aktualizace v6** nahrajte na hosting **pouze dva soubory**: `index.php` do kořene webu a `assets/style.css` do složky `assets/`.
3. **Nepřepisujte** `storage/content.php` (obsah, texty, zápasy, sezóny), ani `storage/auth.php` (heslo), `uploads/` či `archive-files/`.
4. Načtěte stránku znovu. Soubor stylů má novou verzi odkazu (`?v=9`), aby se nepoužila stará verze z mezipaměti.

Administrace se **nemění**. Názvy domácích a hostů stále čerpá karta z existujících zápasů a jsou upravitelné tamtéž.

## Nová instalace

Pro novou instalaci lze použít **kompletní balíček v6**, který obsahuje všechny soubory včetně historických dat a médií. Je určen pro hosting s PHP 8+ a právy k zápisu do `storage/` a `uploads/`.
