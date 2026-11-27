# Cykla & Vandra – Förstasida

WordPress-plugin som bygger förstasidan på cyklavandra.se från ett inställningsgränssnitt,
ett per språk (WPML). Ersätter den handbyggda prototypen "Ny förstasida" (page 36205).

## Funktioner

- **Shortcode `[cv_forstasida]`** renderar hela förstasidan för sidans WPML-språk:
  hero med AI-sökning, tre rutor, faktarad, aktuella guidade resor, populära resor på egen
  hand, nyheter, senaste blogginläggen, kartan över resorna (`[resekarta]`) och bandet "Följ oss".
- **Cykla&Vandra → Förstasida**, en flik per aktivt språk. Svenska standardvärden =
  prototypens texter och resor.
- **Språkregel:** guidade resor (rutan, sektionen) visas bara på svenska och norska.
  Styrs av "Visa guidade resor" per språk.
- **Resekort:** välj resor bland sidor taggade `guidad` / `självguidad`, dra för att sortera.
  Titel, utvald bild och länk hämtas från sidan (i andra språk via WPML). Kortrubrik,
  metarad, pris och bild kan skrivas över per kort. Saknas en översättning visas inte kortet
  på det språket.
- **Dagar och pris automatiskt** ur resesidans faktaruta ("Längd 5 dagar / 4 nätter",
  "Pris från 725 €", även norska "Lengde"/"fra"). Tom metarad byggs av taggarna och
  faktarutan, t.ex. "Vandring · 5 dagar · Nivå 1". Guidade resor saknar faktaruta – där
  skrivs metarad och pris in för hand. Det som skrivs in per kort går alltid före.
- **Nyheter:** manuellt valda resor eller automatiskt (tagg, t.ex. `2027`, + självguidad), nyast först eller i slumpmässig ordning (blandas i webbläsaren vid varje besök).
- **Blogg:** senaste inläggen i en kategori, renderade av pluginen (inte Avadas `fusion_blog`).
- **Svenska är huvudsidan.** Övriga språk *följer svenska* per sektion (standard): resor och
  deras ordning, bilder, länkar och av/på-val hämtas direkt från den svenska fliken. Länkar
  till sidor och kategorier byts automatiskt mot samma sida på språket (WPML).
- **"Spara och översätt ändringarna till alla språk"** på svenska fliken: Claude översätter de
  svenska texter som ändrats sedan förra gången. Förslagen visas per språk och sparas först när
  du godkänner dem. Texter du har justerat för hand på ett språk skrivs inte över (de visas som
  förslag utan kryss). Använder samma API-nyckel som AI-sök (`CVAI_API_KEY`), eller `CVFS_API_KEY`.
- **Uppdateringar** direkt från GitHub-releaser (som AI-sök).

Kräver pluginen **cyklavandra-ai-sok** för sökrutan i heron (utan den visas heron utan sökruta).

## Installation

1. Ladda ner senaste `cyklavandra-forstasida.zip` under **Releases** – *inte* "Source code (zip)".
2. WordPress → Tillägg → Lägg till nytt → Ladda upp tillägg → Aktivera
   (vid uppdatering: "Ersätt nuvarande").
3. Lägg `[cv_forstasida]` i en Avada-textmodul (eller kodblock) på en sida med mallen
   **"100% Width"** och dolt sidtitelfält, i en container med **Interior Content Width =
   100% Width** och 0 i padding/marginal. Enklast: klona prototypsidan, ta bort alla
   containrar utom den första och ersätt textmodulens innehåll med shortcoden.
   Med standardmall eller "Site Width" blir heron och banden inte kant i kant.
4. Ställ in texter, bilder och resor under **Cykla&Vandra → Förstasida**.

Shortcode-attribut: `lang="sv"` visar ett visst språks inställningar (för test).

## Filer

| Fil | Innehåll |
|---|---|
| `cyklavandra-forstasida.php` | Huvudfil, konstanter |
| `cyklavandra-meny.php` | Gemensam meny Cykla&Vandra – kopieras oförändrad från `bokningsformular` |
| `includes/lang.php` | WPML-hjälpare (språk, översatta sidor och taggar) |
| `includes/settings.php` | Standardvärden per språk, läsning, tvättning och sparning |
| `includes/trips.php` | Resekort, automatisk metarad, automatiska nyheter, sökning |
| `includes/events.php` | Guidade resor från The Events Calendar (avgångar → resesidor, sorterat på nästa avresa) |
| `includes/follow.php` | "Följ svenska": resor, bilder och länkar från standardspråket |
| `includes/translate.php` | Översättning av ändrade texter med Claude, förslagssidan |
| `includes/render.php` | Shortcoden, blogginlägg, rendering |
| `includes/admin.php` | Inställningssidan |
| `includes/updater.php` | Uppdateringar från GitHub-releaser |
| `templates/forstasida.php` | HTML-mallen (samma klasser `cvfs-*` som prototypen) |
| `assets/forstasida.css` / `forstasida.js` | Prototypens CSS och JS |
| `assets/admin.css` / `admin.js` | Resväljare, bildväljare |
| `assets/icon.svg`, `icon-*.png` | Ikon (översikten och uppdateringar) |

## Ny version

1. Höj `Version:` och `CVFS_VERSION` i `cyklavandra-forstasida.php` och lägg en rad under
   *Versioner* nedan (den blir releasetexten).
2. Committa och pusha till `main`.
3. På GitHub: **Actions → Bygg release → Run workflow** (gren `main`), eller pusha taggen
   `vX.Y.Z`. Zip-filen `cyklavandra-forstasida.zip` byggs och bifogas releasen.

**Lägg aldrig API-nycklar eller lösenord i repot.**

## Versioner

- **0.9.2** – Hovereffekt på rese- och bloggkorten: kortet lyfts 4 px med tydligare skugga och bilden zoomar in lite (5 %). "Se alla"-korten får bara skuggan. Ingen effekt på mobil eller pekskärm.
- **0.9.1** – Blogginläggen kan också visas i rad i sidled (av/på för dator och mobil; upp till 12 inlägg, inställbart). De tre rutorna (Guidade resor, Cykla, Vandra) växer lite när muspekaren är över dem. På mobil och surfplatta med pekskärm händer inget.
- **0.9.0** – Rad i sidled för guidade, populära och nyheter, med av/på per sektion för dator (pilar ‹ ›) och mobil (svep). Raden har ett "Se alla"-kort sist. När raden är av visas högst 5 kort (för nyheter det angivna antalet). Guidade resor fylls automatiskt från The Events Calendar: alla resor med kommande avgång, sorterade efter nästa avresa, med "Nästa avresa …" i metaraden och pris från avgångens "Kostnad". Nyheternas tagg kan vara "nästa säsong" automatiskt (byts 1 september), och `{år}` i rubrik och etikett byts mot säsongens år.
- **0.8.0** – Nyheter kan visas i slumpmässig ordning: "Automatiskt: … slumpmässig ordning". Webbläsaren väljer vid varje besök ut antalet kort (standard 4) bland alla resor med taggen, så det fungerar även med sidcache. Övriga språk följer. Samma resa kan aldrig visas två gånger i en sektion.
- **0.7.4** – Kartan ligger på ett ljusgrått band i full bredd (#F4F6F3), som Aktuella guidade resor och Nyheter.
- **0.7.3** – Kartan ligger nu ovanför bandet "Följ oss", som åter är sist på sidan.
- **0.7.2** – Förberett för engelska, danska, finska och isländska. Inbyggda ord för metaraderna finns på finska och isländska ("Vaellus", "Gönguferð" …). Datumformat per språk (danska och isländska `j. F Y`, finska `j.n.Y`). Faktarutan läses också på isländska och finska (Lengd, Tímalengd, Verð, frá, alk.).
- **0.7.1** – Kartan på förstasidan döljs inte längre när kunden gör en AI-sökning. På andra sidor döljs den som förut.
- **0.7.0** – Kartan över resorna (`[resekarta]`) längst ner, under bandet "Följ oss", i samma bredd som övriga sektioner. Kryssrutan "Karta" under Band "Följ oss" och karta stänger av och på den. Den är på från början och följer svenskan på andra språk.
- **0.6.3** – Antal dagar läses också när faktarutan säger "Varighet" (norska resesidor) i stället för "Lengde", liksom danska "Varighed" och engelska "Duration".
- **0.6.2** – Mindre avstånd mellan metaraden (typ · dagar · nivå) och resans namn på resekorten: 8 px i stället för 34 px. Avada gav rubriken 28 px marginal ovanför.
- **0.6.1** – Rättat: kortens typ och nivå läses från den svenska originalsidans taggar, eftersom översatta sidor ofta saknar hike/bike-taggarna. Tidigare blev resor utan nivåtagg "Cykling" eftersom domänen cyklavandra.se innehåller "/cykla". Länkar som följer svenskan hittas nu via sidans sökväg (till exempel /vandra/guidade/ på norska). Nyheter i automatiskt läge visar samma resor på alla språk som på svenska. Blogginlägg byggda med Avada får utdrag igen.
- **0.6.0** – Svenska är huvudsidan: övriga språk följer svenskans resor, bilder och länkar (kryssrutan "Följ svenska" per sektion, länkar översätts via WPML). Ny knapp "Spara och översätt ändringarna till alla språk": Claude översätter ändrade texter, förslagen granskas innan de sparas, och handjusterade texter skrivs inte över.
- **0.5.1** – Norska standardvärdet för bloggkategorin är nu `nyheter-no` (kategorin har bytt namn till Nyheter), länken "Alle innlegg →" går till `/no/category/nyheter-no/`.
- **0.5.0** – Norska standardvärden: samma upplägg, resor och bilder som den svenska sidan med norska texter, norska adresser, norska kortrubriker och bloggkategorin `uncategorized-no`. Ord i automatiska metarader på norska: Vandring/Sykling, Guidet vandring, dager. Har den norska fliken sparats tidigare: tryck "Återställ fliken till standardvärden" för att få de nya texterna.
- **0.4.0** – Egna bakgrundsbilder i heron för mobil (skärmar upp till 700 px, gärna stående bilder). Tom lista = samma bilder som för dator och surfplatta.
- **0.3.0** – Inställningarna ligger under menyn Cykla&Vandra → Förstasida (samma meny som Bokningsformulär, AI-sök och Kanalpublicering), med ett kort och en ikon på översiktssidan.
- **0.2.0** – Antal dagar och pris läses automatiskt ur resesidans faktaruta (Längd/Pris, även på norska). Inställningen "Pris från resesidan" per sektion (av för nyheterna, som i prototypen). Samma resa kan inte väljas två gånger i en sektion.
- **0.1.0** – Första versionen: shortcoden `[cv_forstasida]` med prototypens utseende, inställningar per språk (Cykla&Vandra → Förstasida), resväljare, språkregeln för guidade resor, egen rendering av blogginläggen och uppdateringar från GitHub.
