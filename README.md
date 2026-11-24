# Cykla & Vandra – Förstasida

WordPress-plugin som bygger förstasidan på cyklavandra.se från ett inställningsgränssnitt,
ett per språk (WPML). Ersätter den handbyggda prototypen "Ny förstasida" (page 36205).

## Funktioner

- **Shortcode `[cv_forstasida]`** renderar hela förstasidan för sidans WPML-språk:
  hero med AI-sökning, tre rutor, faktarad, aktuella guidade resor, populära resor på egen
  hand, nyheter, senaste blogginläggen och bandet "Följ oss".
- **Inställningar → Förstasida**, en flik per aktivt språk. Svenska standardvärden =
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
- **Nyheter:** manuellt valda resor eller automatiskt (tagg, t.ex. `2027`, + självguidad).
- **Blogg:** senaste inläggen i en kategori, renderade av pluginen (inte Avadas `fusion_blog`).
- **Uppdateringar** direkt från GitHub-releaser (som AI-sök).

Kräver pluginen **cyklavandra-ai-sok** för sökrutan i heron (utan den visas heron utan sökruta).

## Installation

1. Ladda ner senaste `cyklavandra-forstasida.zip` under **Releases** – *inte* "Source code (zip)".
2. WordPress → Tillägg → Lägg till nytt → Ladda upp tillägg → Aktivera
   (vid uppdatering: "Ersätt nuvarande").
3. Lägg `[cv_forstasida]` i en Avada-textmodul (eller kodblock) på en sida med mallen
   "100% Width", i en container utan inre marginaler (som prototypen).
4. Ställ in texter, bilder och resor under **Inställningar → Förstasida**.

Shortcode-attribut: `lang="sv"` visar ett visst språks inställningar (för test).

## Filer

| Fil | Innehåll |
|---|---|
| `cyklavandra-forstasida.php` | Huvudfil, konstanter |
| `includes/lang.php` | WPML-hjälpare (språk, översatta sidor och taggar) |
| `includes/settings.php` | Standardvärden per språk, läsning, tvättning och sparning |
| `includes/trips.php` | Resekort, automatisk metarad, automatiska nyheter, sökning |
| `includes/render.php` | Shortcoden, blogginlägg, rendering |
| `includes/admin.php` | Inställningssidan |
| `includes/updater.php` | Uppdateringar från GitHub-releaser |
| `templates/forstasida.php` | HTML-mallen (samma klasser `cvfs-*` som prototypen) |
| `assets/forstasida.css` / `forstasida.js` | Prototypens CSS och JS |
| `assets/admin.css` / `admin.js` | Resväljare, bildväljare |

## Ny version

1. Höj `Version:` och `CVFS_VERSION` i `cyklavandra-forstasida.php` och lägg en rad under
   *Versioner* nedan (den blir releasetexten).
2. Committa och pusha till `main`.
3. På GitHub: **Actions → Bygg release → Run workflow** (gren `main`), eller pusha taggen
   `vX.Y.Z`. Zip-filen `cyklavandra-forstasida.zip` byggs och bifogas releasen.

**Lägg aldrig API-nycklar eller lösenord i repot.**

## Versioner

- **0.2.0** – Antal dagar och pris läses automatiskt ur resesidans faktaruta (Längd/Pris, även på norska). Inställningen "Pris från resesidan" per sektion (av för nyheterna, som i prototypen). Samma resa kan inte väljas två gånger i en sektion.
- **0.1.0** – Första versionen: shortcoden `[cv_forstasida]` med prototypens utseende, inställningar per språk (Inställningar → Förstasida), resväljare, språkregeln för guidade resor, egen rendering av blogginläggen och uppdateringar från GitHub.
