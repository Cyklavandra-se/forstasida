# CLAUDE.md – Cykla & Vandra Förstasida (plugin)

WordPress-plugin som bygger förstasidan på cyklavandra.se från ett inställningsgränssnitt,
per språk. Ersätter en handbyggd prototyp (HTML + CSS + skript i en Avada-textmodul).

Ägare: Pär (Cykla & Vandra). Skriv till honom på **svenska**, kort och konkret.
Systerplugin: **cyklavandra-ai-sok** (AI-sökningen) – följ samma struktur, regler och
release-flöde som där.

## Miljö

- WordPress, tema **Avada** (Avada Builder), **WPML** (svenska = standard, norska `no`
  → `nb`, fler språk senare), The Events Calendar, All in One SEO, WP Fastest Cache.
- AI-sökningen finns som shortcode `[cv_ai_sok]` (attribut: `platshallare`, `knapp`,
  `exempel="nej"`, `exempelfragor="a|b"`). Exempelfrågor per språk ställs in i pluginen
  AI-sök – lägg inte till egna.
- Resor är WordPress-**sidor** taggade `guidad` / `självguidad` (+ språkvarianter),
  svårighet via taggar `hike1–4` / `bike1–4`, nyheter via taggen `2027`.
  Utvald bild (featured image) finns på de flesta resesidor.

## Mål

1. Shortcode **`[cv_forstasida]`** som renderar hela förstasidan för aktuellt WPML-språk.
2. Admin: **Cykla&Vandra → Förstasida**, en flik per aktivt WPML-språk.
3. Svenska versionen ska se ut **exakt som prototypen** (se nedan) innan vi går vidare.
4. Därefter norska (samma upplägg), sedan engelska m.fl.

## Språkregel (viktig)

- **Svenska och norska**: identiska upplägg, guidade resor visas.
- **Alla andra språk**: guidade resor visas **inte alls** – inte rutan "Guidade resor",
  inte sektionen "Aktuella guidade resor", inte i faktaraden. (Guidningen sker på svenska.)
  Styrs av en inställning per språk ("Visa guidade resor": på för sv/nb, av för övriga).

## Prototypen (förlaga för design)

- Sida **"Ny förstasida"**, page ID **36205**,
  https://cyklavandra.se/information/om-oss/ny-forstasida/ – publicerad, mall "100% Width",
  sidtitelfält dolt, ingen menypunkt.
- All CSS + två `<script>` ligger i **första** Avada-textmodulen. Hämta källan via REST:
  `GET /wp-json/wp/v2/pages/36205?context=edit` → `content.raw` (inloggad, nonce).
  Klasserna heter `cvfs-*`. Flytta CSS till `assets/forstasida.css` och JS till
  `assets/forstasida.js` i pluginen.
- Rör inte prototypen förrän den nya versionen är godkänd.

### Sektioner i ordning (svenska texter som de står nu)

1. **Hero** (full bredd)
   - Bakgrundsbild slumpas per besök bland 4 bilder (ingen mörkning; textskugga på
     rubrik, svag mörk toning bakom underrubriken):
     - `2020/12/12924658334_3daa5efc52_k.jpg` (vandrare, vulkanlandskap)
     - `2020/12/44334843835_2e38fdab86_k.jpg` (vandrare, klippkust)
     - `2024/09/PXL_20240826_093340787.jpg` (cyklist på bro)
     - `2025/10/eurobike_radreisen_donauradweg_weingarten_radgruppe-21.jpg` (Donau)
     (alla under `https://cyklavandra.se/wp-content/uploads/`). Pär byter till bilder med
     bara landskap eller motivet vid sidan – gör bildlistan redigerbar per språk
     (mediaväljare).
   - Rubrik "Var vill du cykla eller vandra?"
   - Underrubrik "Beskriv din drömresa med egna ord – vi hittar resorna som passar."
   - `[cv_ai_sok platshallare="T.ex. lätt cykling med vinprovning i september, två par" knapp="Hitta resor"]`
   - Bildhöjd fast 600 px (desktop). **När sökresultat visas** krymper bilden mjukt så den
     slutar 24 px under sökformuläret; resultaten hamnar på ljus bakgrund (#F4F6F3) i en
     vit ruta; exempelfrågorna döljs. (MutationObserver på `.cvai-out`, klass
     `cvfs-has-res`, CSS-variabel `--cvfs-hero-h`.)
   - Exempelfrågor: vita, 92 % täckning (8 % genomskinliga), mörk text.
   - **Mobil (≤700 px)**: bilden täcker hela hero när ingen sökning gjorts; exempelfrågorna
     ligger på **en rad som sveps i sidled** (en rad per knapp, ellips vid lång text);
     mindre padding.
2. **Tre rutor** (ingen liten överrubrik)
   - Guidade resor – "Svensk guide, trevligt sällskap och fasta avresedatum. Vi tar hand om
     allt." – "Se alla guidade resor →" → `/vandra/guidade/` (grön #EEF3EF)
   - Cykla på egen hand – "Längs floder, vingårdar och kuster i din egen takt. Vi bokar
     hotell och flyttar bagaget." – "Se alla cykelresor →" → `/cykla/cykla-pa-egen-hand/` (persika #FBEFE4)
   - Vandra på egen hand – "Kustleder och bergsstigar med navigeringsapp, bagagetransport
     och support under resan." – "Se alla vandringsresor →" → `/vandra/pa-egen-hand/` (ljusblå #EAF1F6)
   - Hela rutan klickbar.
3. **Faktarad** (4 kolumner, sista kant i kant till höger; **dold på mobil**)
   - Cykel- och vandringsresor sedan 2009 / Erfarenhet av cykel- och vandringsresor
     (Pär kan vilja byta underraden – upprepar rubriken)
   - Tusentals nöjda resenärer / Från ett tjugotal länder
   - Support under resan / På alla resor på egen hand
   - Bagagetransport och app / Ingår i alla resor på egen hand
4. **Aktuella guidade resor** (ljusgrå bakgrund #F4F6F3, full bredd) – länk "Alla guidade resor →"
   - Andorra, La Gomera, Rioja & San Sebastián, Piemonte & Ligurien, Korfu/Paxos/Albanien
     (nuvarande SvD-kampanjresor). Kort: bild, "Guidad vandring · X dagar", namn, "från X kr".
5. **Populära resor på egen hand** (vit) – länk "Alla resor på egen hand →" `/sjalvguidade/`
   - Costa Brava, Canal du Midi, Mosel, Baskien (San Sebastián), Wachau. Kort: bild,
     "Typ · X dagar · Nivå N", namn, "från X €".
6. **Nyheter för säsongen 2027** (ljusgrå) – länk "Se alla nyheter →"
   `/information/hitta-resa/destinationer/nyheter/`. 4 kort. Endast självguidade.
7. **Nyhetsbrev** – Avadas `[fusion_blog]`, kategori `nyheter`, 3 senaste, kort stylade som
   resekorten, länk "Alla inlägg →" `/category/nyheter/`. (Avadas datumformat för svenska
   är `j F Y`.) Gör gärna egen rendering i pluginen i stället för fusion_blog.
8. **Band "Följ oss och få inspiration!"** (sandfärg #F6E6D6) – "Bilder, tips och nyheter
   från våra resor." – knappar **Nyhetsbrev** (`/information/nyhetsbrev/`, samma flik),
   **Instagram** (`instagram.com/cyklavandra.se`), **Facebook** (`facebook.com/cyklavandra/`)
   (ny flik) + 4 små kvadratiska bilder till höger.

Alla sektioner har samma innehållsbredd (1200 px wrap med 24 px inre marginal → 1152 px),
även bloggen och bandet.

## Admin-gränssnitt (förslag)

Per språkflik:
- Hero: rubrik, underrubrik, bilder (mediaväljare, flera), platshållare + knapptext till AI-sök.
- Tre rutor: rubrik, text, länktext, URL (guidade-rutan döljs om "Visa guidade resor" är av).
- Faktarad: 4 × (rubrik, underrad).
- Resesektioner: rubrik, länktext, URL, och **resväljare** (sök bland sidor taggade
  guidad/självguidad, dra för att sortera). Kortet hämtar titel, utvald bild och permalänk
  automatiskt; i andra språk via WPML (`wpml_object_id`).
- Nyheter: automatiskt från taggen (inställbar, nu `2027`) + typ självguidad, antal (4),
  eller manuellt val.
- Blogg: kategori + antal.
- Band: rubrik, text, knappar (text + URL), 4 bilder.
- "Visa guidade resor" (på/av).

## Öppna frågor till Pär

- ~~Pris och antal dagar~~ – **besvarat (0.2.0):** läses ur resesidans faktaruta
  (`<div>Längd</div><div>5 dagar / 4 nätter</div>`, `<div>Pris</div><div>från 725 €</div>`;
  norska `Lengde`/`fra`), `cvfs_trip_details()`. 109 av 136 självguidade svenska sidor har
  rutan (nov 2026); guidade sidor har den inte – där fylls metarad/pris i för hand.
  Fälten `_cvkp_days`/`_cvkp_nights` (kanalpubliceringen) finns men är ofta 0 – används inte.
- ~~Norska sidan~~ – **besvarat:** det finns en fungerande norsk startsida som ska ersättas.
  Det finns också en norsk kopia av prototypen: `/no/informasjon-om-programmet/om-oss/ny-forside/`
  (page 37430) – bra källa till norska texter.
- ~~Repo-namn~~ – repot heter `Cyklavandra-se/forstasida`; pluginmappen och zip-filen heter
  `cyklavandra-forstasida`.

## Regler (samma som AI-sök)

- Höj `Version:` och konstanten `CVFS_VERSION` vid varje ändring, och lägg en rad under
  "Versioner" i README.md.
- Nya inställningar ska ha standardvärden.
- Inga API-nycklar eller hemligheter i repot.
- Ingen inline-HTML som WordPress `wpautop` kan förstöra – rendera från PHP-mallar.
- All text ut via `esc_html` / `esc_url` / `esc_attr`.

## Testa lokalt

WordPress + SQLite Database Integration + `php` (ingen MySQL). Mocka WPML med filtren
`wpml_current_language`, `wpml_active_languages`, `wpml_object_id` och actionen
`wpml_switch_language`. Kör `php -l` och `node --check` på JS.

## Release

Som AI-sök: pusha till `main` → GitHub Release med tagg `vX.Y.Z` → workflow bygger
`cyklavandra-forstasida.zip` → Pär installerar via Tillägg → Ladda upp → "Ersätt nuvarande".
Påminn om att ladda ner pluginens zip, inte "Source code (zip)".

## Läge (0.6.1)

Första uppgiften är gjord. Arkitektur:

- `includes/settings.php` – option `cvfs_settings` = `array( lang => inställningar )`.
  `cvfs_get($lang)` slår ihop sparat med `cvfs_defaults($lang)` (assoc rekursivt, listor ersätts).
  Svenska standardvärden = prototypen, inklusive resornas sid-id och kortens texter/priser.
  Andra språk: samma, men kortens titel/meta/pris tomma (hämtas automatiskt) och
  `show_guided` bara på för sv/nb.
- `includes/trips.php` – kort = sid-id + valfria `title`, `meta`, `price`, `image`. Tomt →
  sidans titel/utvalda bild ("large"), metarad från taggar (`guidad`, `hike1–4`/`bike1–4`)
  + dagar ur faktarutan, pris ur faktarutan om sektionens `auto_price` är på.
  Wachau (33232) har kvar manuell metarad: taggen är bike2 men prototypen säger Nivå 1.
  Kort utan översättning i språket hoppas över (`cvfs_object_id`).
- Heron har två bildlistor: `hero.images` (dator/surfplatta) och `hero.images_mobile`
  (≤ 700 px, samma gräns som CSS:en). Inline-skriptet väljer lista med `matchMedia`; tom
  lista faller tillbaka på den andra.
- Pärs testsida "Cykla & Vandra v2.0" (page 37967): heron gick inte kant i kant eftersom
  sidan hade standardmall, synligt sidtitelfält och containern på "Site Width" – inget fel i
  pluginen. Se README → Installation.
- `templates/forstasida.php` – samma HTML/klasser som prototypen. `cvfs_render()` tar bort
  radbrytningar mellan taggar (skydd mot wpautop). Slumpningen av herobild är ett inline-skript
  direkt efter heron (som i prototypen, så att bilden väljs innan sidan ritas).
- `assets/forstasida.css` – del 1 = prototypens CSS oförändrad (en regel per rad, ordningen
  spelar roll), del 2 = pluginens tillägg (20 px under heron som prototypens `<p>` gav, och
  bloggkorten).
- `cyklavandra-meny.php` – gemensam adminmeny Cykla&Vandra (klass `Cyklavandra_Meny`), kopierad
  **oförändrad** från `Cyklavandra-se/bokningsformular`. Ändra den aldrig här. Kort på
  översikten via filtret `cyklavandra_verktyg`, ikon `assets/icon.svg`.
- Admin: Cykla&Vandra → Förstasida (`admin.php?page=cvfs&lang=xx`), sparas via
  `admin-post.php?action=cvfs_save`, resväljaren söker via AJAX `cvfs_search_trips`.

Verifierat lokalt: min utdata inlagd i live-sidans Avada-HTML ger exakt samma positioner och
storlekar som prototypen på 1440, 900 och 390 px. Enda avsiktliga skillnaden: bloggsektionen
har samma 24 px sidmarginal som övriga sektioner även under 1000 px (i prototypen gick den
kant i kant där).

Svenska är godkänd av Pär (testsida page 37967, nu kant i kant).

Norska (0.5.0): `cvfs_defaults_nb()` i settings.php – Pär ville utgå från den **svenska sidan**,
inte den norska prototypkopian (page 37430), så texterna är översättningar av de svenska.
Norska adresser hämtade från hreflang-länkarna. Bloggkategorin på norska heter
`nyheter-no` (namn "Nyheter"; hette `uncategorized-no` före 0.5.1). Guidade priser står i "kr"
även på de norska resesidorna. Baskien saknar "Lengde" i den norska faktarutan → manuell metarad.

Svenska som huvudsida (0.6.0), beslutat med Pär:
- `includes/follow.php` – `cvfs_effective($lang)` = språkets inställningar + standardspråkets
  struktur i sektioner där `follow[sektion]` är på (standard för alla andra språk). Struktur =
  bilder, länkar (`cvfs_translate_url` via `url_to_postid`/kategori → `wpml_object_id`, cache i
  transient med `cvfs_cache_ver`), av/på-val, resornas urval/ordning (`cvfs_follow_trips`:
  rubrik/metarad alltid språkets egna per resa-id, pris/bild språkets eller svenskans).
  Frontend och admin använder `cvfs_effective`. Texter och `date_format`, `labels`,
  `show_guided` är alltid språkets egna.
- `includes/translate.php` – knapp (Pär valde knapp, inte vid sparande). `cvfs_text_fields`
  listar texter (guidade hoppas över där `show_guided` är av; kortrubriker översätts från
  svenskan – Pärs val). Option `cvfs_translations[lang][sökväg] = {src: md5(svenska), out}`.
  Status: new/changed (förvalda), adjusted/own (handjusterade – ej förvalda), clear.
  AJAX `cvfs_translate_lang` per språk → Claude (`claude-opus-5-5`, effort low,
  `output_config.format` json_schema, `fallbacks: "default"` + beta
  `server-side-fallback-2026-07-01`, retry utan fallbacks vid 400) → förslag; sparas med
  `admin-post.php?action=cvfs_apply_translations`. Nyckel: `CVFS_API_KEY` eller AI-söks.
  Ej testat mot riktiga API:et (ingen nyckel i utvecklingsmiljön) – lokalt med mock.

0.6.1 efter Pärs norska testsida (`/no/informasjon-om-programmet/om-oss/cykla-vandra-v2-0/`):
- `cvfs_trip_facts()` läser taggar från originalsidan (`cvfs_original_id`) – norska sidor saknar
  ofta hike/bike-taggar (har `vandre`/`syklus`). URL-reserven tittar bara på sökvägen.
- `cvfs_translate_url()` slår upp sidor med `get_page_by_path` först; misslyckade uppslag cachas inte.
- `cvfs_master_trips()`: nyheter i auto-läge följer svenskans automatiska urval.
- `cvfs_plain_text()` för utdrag: strip_shortcodes tog bort hela Avada-innehållet.
- Översättningen med Claude fungerar live (norska texterna på testsidan kom därifrån).

Nästa steg: engelska m.fl. (utan guidade resor).

## Första uppgiften i Claude Code

1. Skapa pluginskelett (huvudfil, `includes/`, `assets/`, `templates/`, README, workflow).
2. Hämta prototypens HTML/CSS/JS (page 36205) och flytta in den i mallar och assets.
3. Bygg inställningssidan med svenska standardvärden = texterna ovan.
4. Testa lokalt att `[cv_forstasida]` ger samma resultat som prototypen.
5. Fråga Pär om de öppna frågorna innan resekorten görs automatiska.
