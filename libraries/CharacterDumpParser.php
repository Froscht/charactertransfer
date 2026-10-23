<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CharacterDumpParser
 *
 * Kümmert sich um:
 *  - Lua-Dump -> JSON dekodieren
 *  - Normalisierung in ein sauberes $chardata-Array
 *    (main, Inventory, Equippment, mounts/pets, money, professions, talents, specmask, etc.)
 *
 * Erwartete $params beim Konstruktor:
 *  - spelldata    => Array aus config('constants')['spelldata']
 *  - talentspells => Array aus config('constants')['talentspells']
 *  - factions     => Array aus config('constants')['factions'] (optional, aktuell hier nicht genutzt)
 *  - skilllines   => Array aus config('constants')['skilllines'] (by_spell / by_name / by_class_name)
 *  - createinfo_skills => Array aus config('constants')['createinfo_skills'] (Start-Skill-Lines nach Rasse/Klasse)
 */
class CharacterDumpParser
{
    /** @var array */
    private $spelldata = [];

    /** @var array */
    private $talentspells = [];

    /** @var array */
    private $factions = [];

    /** @var array */
    private $skilllines = [];

    /** @var array */
    private $createinfoSkills = [];

    /**
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        if (isset($params['spelldata']) && is_array($params['spelldata'])) {
            $this->spelldata = $params['spelldata'];
        }

        if (isset($params['talentspells']) && is_array($params['talentspells'])) {
            $this->talentspells = $params['talentspells'];
        }

        if (isset($params['factions']) && is_array($params['factions'])) {
            $this->factions = $params['factions'];
        }

        if (isset($params['skilllines']) && is_array($params['skilllines'])) {
            $this->skilllines = $params['skilllines'];
        }

        if (isset($params['createinfo_skills']) && is_array($params['createinfo_skills'])) {
            $this->createinfoSkills = $params['createinfo_skills'];
        }
    }

    // ------------------------------------------------------------------------
    //  Rohdump -> JSON
    // ------------------------------------------------------------------------

    /**
     * Dekodiert den kombinierten CHDMP_DATA/CHDMP_KEY-String.
     *
     * @param string $data
     * @return array|null
     */
    public function decodecharacterdump($data)
    {
        // Original-Logik aus deinem Code
        $data = str_replace(
            ["'", "\\"],
            "",
            strrev(
                base64_decode(
                    base64_decode(
                        strrev($data)
                    )
                )
            )
        );

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Extrahiert CHDMP_DATA / CHDMP_KEY aus der Lua-Datei
     * und gibt den dekodierten Dump als JSON-String zurück.
     *
     * @param string $luaFileContents
     * @return string|null JSON-String oder null bei Fehler
     */
    public function ReturnCharDumpAsJson($luaText)
    {
        // Extract Base64 fields
        $extract = function($name) use ($luaText) {
            $pattern = '/\b' . preg_quote($name, '/') . '\s*=\s*"([A-Za-z0-9+\/=@\r\n\t\f\v\s]+)"/';
            if (!preg_match($pattern, $luaText, $m)) {
                return null;
            }
            return preg_replace('/\s+/', '', $m[1]); // remove whitespace inside ""
        };

        $data = $extract('CHDMP_DATA');
        $key  = $extract('CHDMP_KEY');
        if ($data === null || $key === null) return null;

        $full = $data . $key;

        // Helpers
        $b64 = function($str, $strict=true) {
            return base64_decode($str, $strict);
        };

        $json_try = function($s) {
            $j = json_decode($s, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $j : null;
        };

        // Try decode variants
        $variants = [
            ['desc' => 'normal',   'pre' => $full],
            ['desc' => 'reversed', 'pre' => strrev($full)],
        ];

        foreach ($variants as $v) {
            // Outer decode
            $outer = $b64($v['pre'], true);
            if ($outer === false) $outer = $b64($v['pre'], false);
            if ($outer === false) continue;

            // Clean newlines
            $outerClean = preg_replace('/\s+/', '', $outer);

            // Inner decode
            $json = $b64($outerClean, true);
            if ($json === false) $json = $b64($outerClean, false);

            // Maybe already JSON
            if ($json === false) {
                $trim = ltrim($outer);
                if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                    $json = $outer;
                }
            }

            // Try parsing
            if ($json !== false && ($parsed = $json_try($json)) !== null) {
                return json_encode($parsed);  // <- WICHTIG: Als String zurückgeben
            }

            // Try reversed JSON
            if ($json !== false) {
                $rev = strrev($json);
                if (($parsed = $json_try($rev)) !== null) {
                    return json_encode($parsed);  // <- WICHTIG: Als String zurückgeben
                }
            }
        }

        return null;
    }

    /**
     * Sekunden in "X days, Y hours, Z minutes" umwandeln
     *
     * @param int $seconds
     * @return string
     */
    public function convertSeconds($seconds)
    {
        $seconds = (int)$seconds;

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "$days days, $hours hours, $minutes minutes";
    }

    /**
     * Anzeigename einer Fraktion aus der factions-Konfiguration.
     *
     * Die Konfiguration enthaelt kein enUS - die englischen Namen liegen in itIT.
     *
     * @param int    $factionId
     * @param string $locale z.B. enUS / deDE
     * @return string leerer String wenn unbekannt
     */
    public function getFactionName($factionId, $locale = '')
    {
        $factionId = (int)$factionId;

        if (!isset($this->factions[$factionId]) || !is_array($this->factions[$factionId])) {
            return '';
        }

        $locales = $this->factions[$factionId];

        if ($locale !== '' && isset($locales[$locale]['Name'])) {
            return (string)$locales[$locale]['Name'];
        }

        foreach (['itIT', 'deDE'] as $fallback) {
            if (isset($locales[$fallback]['Name'])) {
                return (string)$locales[$fallback]['Name'];
            }
        }

        foreach ($locales as $entry) {
            if (isset($entry['Name'])) {
                return (string)$entry['Name'];
            }
        }

        return '';
    }

    /**
     * Rangesname aus dem Rohe-Reputation-Wert (WotLK-Schwellen).
     *
     * @param int $value
     * @return string
     */
    public function getReputationStanding($value)
    {
        $value = (int)$value;

        if ($value < -42000) {
            return 'Hated';
        }
        if ($value < -21000) {
            return 'Hostile';
        }
        if ($value < 0) {
            return 'Unfriendly';
        }
        if ($value < 36000) {
            return 'Neutral';
        }
        if ($value < 63000) {
            return 'Friendly';
        }
        if ($value < 84000) {
            return 'Honored';
        }
        if ($value < 99000) {
            return 'Revered';
        }

        return 'Exalted';
    }

    /**
     * Item-Datensatz aus dem Dump in die interne Form bringen.
     *
     * @param array $item
     * @return array
     */
    private function normalizeItem(array $item)
    {
        return [
            'ID'    => (int)($item['I'] ?? 0),
            'Count' => (int)($item['C'] ?? 1),
            'G1'    => (int)($item['G1'] ?? 0),
            'G2'    => (int)($item['G2'] ?? 0),
            'G3'    => (int)($item['G3'] ?? 0),
            'Q'     => (int)($item['Q'] ?? 0),
            'E'     => (int)($item['E'] ?? 0),
        ];
    }


    /**
     * Encodiert Glyphen in Wowhead-Glyphen-Hash
     *
     */

    function encodeWowheadGlyphHashFromType(array $glyphList): string
    {
        $alphabet = '0123456789abcdefghjkmnpqrstvwxyz';

        // 1 = Major, 2 = Minor
        $majors = [];
        $minors = [];

        foreach ($glyphList as $g) {
            $type = (int)($g['Type'] ?? 0);
            $spell = (int)($g['spell'] ?? 0);
            if ($spell <= 0) continue;

            if ($type === 1) $majors[] = $spell;
            if ($type === 2) $minors[] = $spell;
        }

        // Slot-Mapping nach Reihenfolge:
        // Major Slots 0,1,2
        // Minor Slots 3,4,5
        $slotSpells = [];
        for ($i = 0; $i < 3; $i++) {
            if (isset($majors[$i])) $slotSpells[$i] = $majors[$i];
        }
        for ($i = 0; $i < 3; $i++) {
            if (isset($minors[$i])) $slotSpells[3 + $i] = $minors[$i];
        }

        ksort($slotSpells);

        $hash = '0';
        foreach ($slotSpells as $slot => $spellId) {
            $hash .= $alphabet[$slot];
            $hash .= $alphabet[($spellId >> 15) & 31];
            $hash .= $alphabet[($spellId >> 10) & 31];
            $hash .= $alphabet[($spellId >> 5)  & 31];
            $hash .= $alphabet[$spellId & 31];
        }

        return $hash;
    }


    // ------------------------------------------------------------------------
    //  Hauptparser: normalisiert den Dump
    // ------------------------------------------------------------------------

    /**
     * Normalisiert den dekodierten Dump in ein sauberes $chardata-Array,
     * das im Admin-Controller und im SqlGenerator weiterverwendet wird.
     *
     * @param array $data Dekodierter Dump (json_decode(..., true))
     * @return array
     */
    public function ReadCharacterDump(array $data)
    {
        $chardata = [];
        $newInventory = [];
        $characterequippment = [];

        $chardata["main"] = [];

        // --------------------------------------------------------------------
        //  Main / Grunddaten
        // --------------------------------------------------------------------
        $chardata["main"]["locale"]      = $data["globalinfo"]["locale"] ?? '';
        $chardata["main"]["name"]        = $data["unitinfo"]["name"] ?? '';
        $chardata["main"]["specs"]       = $data["unitinfo"]["specs"] ?? [];
        $chardata["main"]["honor"]       = $data["unitinfo"]["honor"] ?? 0;
        $chardata["main"]["stats"]       = $data["unitstats"] ?? [];
        $chardata["main"]["playtime"]    = isset($data["unitinfo"]["playtime"]) ? $this->convertSeconds($data["unitinfo"]["playtime"]) : $this->convertSeconds(0);
        $chardata["main"]["playtimeOnLevel"]    = isset($data["unitinfo"]["playtimeonlevel"]) ? $this->convertSeconds($data["unitinfo"]["playtimeonlevel"]) : $this->convertSeconds(0);
        // Rohe Sekunden - die Spieldauer-Spalte im Core erwartet Int, nicht den lesbaren String
        $chardata["main"]["playtimeSeconds"]       = (int)($data["unitinfo"]["playtime"] ?? 0);
        $chardata["main"]["playtimeOnLevelSeconds"] = (int)($data["unitinfo"]["playtimeonlevel"] ?? 0);
        $chardata["main"]["kills"]       = $data["unitinfo"]["kills"] ?? 0;
        $chardata["main"]["arenapoints"] = $data["unitinfo"]["arenapoints"] ?? 0;
        $chardata["main"]["level"]       = $data["unitinfo"]["level"] ?? 1;

        // --------------------------------------------------------------------
        //  Klasse -> interne ID + "newspells"
        // --------------------------------------------------------------------
        $class  = 0;
        $spells = [];

        $className = $data["unitinfo"]["class"] ?? null;

        if ($className) {
            switch ($className) {
                case "WARRIOR":
                    $class  = 1;
                    $spells = [
                        "PLATE_MAIL", "MAIL", "BerserkerStance", "DefStance", "THROW_WAR",
                        "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "STAVES", "POLEARMS",
                        "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES", "GUNS", "FIST_WEAPONS",
                        "DAGGERS", "CROSSBOWS", "BOWS", "BLOCK"
                    ];
                    break;

                case "PALADIN":
                    $class  = 2;
                    $spells = [
                        "PLATE_MAIL", "MAIL", "Redemption",
                        "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "POLEARMS",
                        "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES", "BLOCK"
                    ];
                    break;

                case "HUNTER":
                    $class  = 3;
                    $spells = [
                        "MAIL", "TameBeast", "FeedPet", "DismissPet", "CallPet", "RevivePet",
                        "THROW_WAR", "TWO_H_SWORDS", "TWO_H_AXES", "STAVES", "POLEARMS",
                        "ONE_H_SWORDS", "ONE_H_AXES", "GUNS", "FIST_WEAPONS",
                        "DAGGERS", "CROSSBOWS", "BOWS"
                    ];
                    break;

                case "ROGUE":
                    $class  = 4;
                    $spells = [
                        "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES",
                        "GUNS", "FIST_WEAPONS", "DAGGERS", "CROSSBOWS", "BOWS"
                    ];
                    break;

                case "PRIEST":
                    $class  = 5;
                    $spells = [
                        "WANDS", "STAVES", "SHOOT", "ONE_H_MACES", "DAGGERS"
                    ];
                    break;

                case "DEATH_KNIGHT":
                    $class  = 6;
                    $spells = [
                        "PLATE_MAIL", "MAIL", "DeathGate", "Runeforging",
                        "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "POLEARMS",
                        "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES"
                    ];
                    break;

                case "SHAMAN":
                    $class  = 7;
                    $spells = [
                        "MAIL", "SearingTotem", "HealingStreamTotem", "StoneskinTotem",
                        "TWO_H_MACES", "TWO_H_AXES", "STAVES",
                        "ONE_H_MACES", "ONE_H_AXES", "FIST_WEAPONS", "DAGGERS", "BLOCK"
                    ];
                    break;

                case "MAGE":
                    $class  = 8;
                    $spells = [
                        "PolyMorphPig", "TelePortDalaran",
                        "WANDS", "STAVES", "SHOOT", "ONE_H_SWORDS", "DAGGERS"
                    ];
                    break;

                case "WARLOCK":
                    $class  = 9;
                    $spells = [
                        "Imp", "Voidwalker", "Succubus", "Felhunter",
                        "WANDS", "STAVES", "SHOOT", "ONE_H_SWORDS", "DAGGERS"
                    ];
                    break;

                case "DRUID":
                    $class  = 11;
                    $spells = [
                        "BearForm", "AquaticForm", "SwiftFlightForm",
                        "TWO_H_MACES", "STAVES", "POLEARMS",
                        "ONE_H_MACES", "FIST_WEAPONS", "DAGGERS"
                    ];
                    break;

                default:
                    $class  = 0;
                    $spells = [];
                    break;
            }
        }

        $newspells = [];
        foreach ($spells as $spellName) {
            if (isset($this->spelldata[$spellName])) {
                $newspells[] = $this->spelldata[$spellName];
            }
        }

        $chardata["newspells"]  = $newspells;
        $chardata["main"]["class"] = $class;
        $chardata["main"]["classname"] = $className;

        // --------------------------------------------------------------------
        //  Rasse -> interne ID
        // --------------------------------------------------------------------
        $raceName = $data["unitinfo"]["race"] ?? null;
        $race     = 0;

        switch ($raceName) {
            case "Human":     $race = 1;  break;
            case "Orc":       $race = 2;  break;
            case "Dwarf":     $race = 3;  break;
            case "Night Elf": $race = 4;  break;
            case "Undead":    $race = 5;  break;
            case "Tauren":    $race = 6;  break;
            case "Gnome":     $race = 7;  break;
            case "Troll":     $race = 8;  break;
            case "Blood Elf": $race = 10; break;
            case "Draenei":   $race = 11; break;
            default:          $race = 0;  break;
        }

        $chardata["main"]["race"] = $race;
        $chardata["main"]["racename"] = $raceName;

        // --------------------------------------------------------------------
        //  Geschlecht
        //  UnitSex() aus der Lua liefert 1 = maennlich, 2 = weiblich,
        //  der Core (characters.gender) speichert 0 = maennlich, 1 = weiblich.
        // --------------------------------------------------------------------
        $genderRaw = (int)($data["unitinfo"]["gender"] ?? 1);
        switch ($genderRaw) {
            case 1:
                $gender = 0;
                break;
            case 2:
                $gender = 1;
                break;
            default:
                $gender = 0;
                break;
        }

        $chardata["main"]["gender"]      = $gender;
        $chardata["main"]["ServerIP"]    = $data["globalinfo"]["realmlist"] ?? '';
        $chardata["main"]["ServerRealm"] = $data["globalinfo"]["realm"] ?? '';
        $chardata["main"]["locale"] = $data["globalinfo"]["locale"] ?? '';

        // --------------------------------------------------------------------
        //  Skills, Glyphen (Rohdaten, werden weiter unten noch verarbeitet)
        // --------------------------------------------------------------------
        $chardata["skills"]  = $data["skills"] ?? [];
        $chardata["glyphs"]  = $data["glyphs"] ?? [];
        $chardata["achievements"] = $data["achiev"] ?? [];
        $points = 0;
        foreach ($chardata["achievements"] as $achievement) {
            if($achievement["completed"] == 1) {
                $points = $points + $achievement["points"];
            }
        }
        $chardata["achievementspoints"] = $points;





        // --------------------------------------------------------------------
        //  Inventory / Equipment
        // --------------------------------------------------------------------
        if (isset($data["inventory"]) && is_array($data["inventory"])) {
            foreach ($data["inventory"] as $key => $item) {
                // key ist z. B. "0000:01"
                if (!is_array($item)) {
                    continue;
                }

                // Extract the bag ID and slot from the key
                $parts = explode(':', $key);
                if (count($parts) !== 2) {
                    continue;
                }

                list($bagId, $slot) = $parts;

                // Equipment: BagId von 0000 bis 0135
                if ($bagId >= '0000' && $bagId <= '0135') {
                    $characterequippment[$slot] = $this->normalizeItem($item);
                } else {
                    // Taschen (1000 = Rucksack, 1001..1004 = Beutel)
                    if (!isset($newInventory[$bagId]) || !is_array($newInventory[$bagId])) {
                        $newInventory[$bagId] = [];
                    }

                    $newInventory[$bagId][$slot] = $this->normalizeItem($item);
                }
            }
        }

        // --------------------------------------------------------------------
        //  Mounts / Pets
        // --------------------------------------------------------------------
        $mounts = [];
        $pets   = [];

        if (isset($data["creature"]) && is_array($data["creature"])) {
            foreach ($data["creature"] as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }

                if (substr($key, 0, 1) === 'M') {
                    $mounts[] = $value;
                } elseif (substr($key, 0, 1) === 'C') {
                    $pets[] = $value;
                }
            }
        }

        ksort($characterequippment);

        $chardata["Inventory"]   = $newInventory;
        $chardata["Equippment"]  = $characterequippment;
        $chardata["mounts"]      = $mounts;
        $chardata["pets"]        = $pets;

        // --------------------------------------------------------------------
        //  Bank - eigener Tab, bleibt getrennt vom Inventar
        //  main:     inv:39..inv:66   -> Bank-Slots (Core-Nummerierung)
        //  bankbags: inv:67..inv:73   -> die Bankbeutel-Items selbst
        //  bags:     5:slot..11:slot  -> Inhalt der Bankbeutel (Container 5..11)
        // --------------------------------------------------------------------
        $bank = [
            'slots'    => [],
            'bags'     => [],
            'contents' => [],
        ];

        if (isset($data["bank"]) && is_array($data["bank"])) {
            if (isset($data["bank"]["main"]) && is_array($data["bank"]["main"])) {
                foreach ($data["bank"]["main"] as $key => $item) {
                    $slot = (int)str_replace('inv:', '', (string)$key);

                    if (!is_array($item) || $slot < 39 || $slot > 66) {
                        continue;
                    }

                    $normalized = $this->normalizeItem($item);
                    if ($normalized['ID'] <= 0) {
                        continue;
                    }

                    $bank['slots'][$slot] = $normalized;
                }
            }

            if (isset($data["bank"]["bankbags"]) && is_array($data["bank"]["bankbags"])) {
                foreach ($data["bank"]["bankbags"] as $key => $item) {
                    $slot = (int)str_replace('inv:', '', (string)$key);

                    if (!is_array($item) || $slot < 67 || $slot > 73) {
                        continue;
                    }

                    $normalized = $this->normalizeItem($item);
                    if ($normalized['ID'] <= 0) {
                        continue;
                    }

                    $bank['bags'][$slot] = $normalized;
                }
            }

            if (isset($data["bank"]["bags"]) && is_array($data["bank"]["bags"])) {
                foreach ($data["bank"]["bags"] as $key => $item) {
                    $parts = explode(':', (string)$key);

                    if (count($parts) !== 2 || !is_array($item)) {
                        continue;
                    }

                    $container = (int)$parts[0];
                    $slot      = (int)$parts[1];

                    if ($container < 5 || $container > 11 || $slot < 1) {
                        continue;
                    }

                    $normalized = $this->normalizeItem($item);
                    if ($normalized['ID'] <= 0) {
                        continue;
                    }

                    if (!isset($bank['contents'][$container])) {
                        $bank['contents'][$container] = [];
                    }

                    $bank['contents'][$container][$slot] = $normalized;
                }
            }
        }

        ksort($bank['slots']);
        ksort($bank['bags']);

        foreach ($bank['contents'] as $container => $bagContents) {
            ksort($bank['contents'][$container]);
        }

        $chardata["bank"] = $bank;

        // --------------------------------------------------------------------
        //  EquipmentCache (Anzeigecache)
        // --------------------------------------------------------------------
        $equipment = [
            'Head'      => isset($characterequippment[1]["ID"])  ? $characterequippment[1]["ID"]  : '0',
            'Neck'      => isset($characterequippment[2]["ID"])  ? $characterequippment[2]["ID"]  : '0',
            'Shoulder'  => isset($characterequippment[3]["ID"])  ? $characterequippment[3]["ID"]  : '0',
            'Shirt'     => isset($characterequippment[4]["ID"])  ? $characterequippment[4]["ID"]  : '0',
            'Chest'     => isset($characterequippment[5]["ID"])  ? $characterequippment[5]["ID"]  : '0',
            'Waist'     => isset($characterequippment[6]["ID"])  ? $characterequippment[6]["ID"]  : '0',
            'Legs'      => isset($characterequippment[7]["ID"])  ? $characterequippment[7]["ID"]  : '0',
            'Feet'      => isset($characterequippment[8]["ID"])  ? $characterequippment[8]["ID"]  : '0',
            'Wrist'     => isset($characterequippment[9]["ID"])  ? $characterequippment[9]["ID"]  : '0',
            'Hands'     => isset($characterequippment[10]["ID"]) ? $characterequippment[10]["ID"] : '0',
            'Finger1'   => isset($characterequippment[11]["ID"]) ? $characterequippment[11]["ID"] : '0',
            'Finger2'   => isset($characterequippment[12]["ID"]) ? $characterequippment[12]["ID"] : '0',
            'Trinket1'  => isset($characterequippment[13]["ID"]) ? $characterequippment[13]["ID"] : '0',
            'Trinket2'  => isset($characterequippment[14]["ID"]) ? $characterequippment[14]["ID"] : '0',
            'Back'      => isset($characterequippment[15]["ID"]) ? $characterequippment[15]["ID"] : '0',
            'Main hand' => isset($characterequippment[16]["ID"]) ? $characterequippment[16]["ID"] : '0',
            'Off hand'  => isset($characterequippment[17]["ID"]) ? $characterequippment[17]["ID"] : '0',
            'Ranged'    => isset($characterequippment[18]["ID"]) ? $characterequippment[18]["ID"] : '0',
            'Tabard'    => isset($characterequippment[19]["ID"]) ? $characterequippment[19]["ID"] : '0',
            'Bag1'      => isset($characterequippment[20]["ID"]) ? $characterequippment[20]["ID"] : '0',
            'Bag2'      => isset($characterequippment[21]["ID"]) ? $characterequippment[21]["ID"] : '0',
            'Bag3'      => isset($characterequippment[22]["ID"]) ? $characterequippment[22]["ID"] : '0',
            'Bag4'      => isset($characterequippment[23]["ID"]) ? $characterequippment[23]["ID"] : '0'

        ];

        $equipmentCache = "";

        foreach ($equipment as $item) {
            // Adds Item ID and appends '0' for Appearance Mod ID and Item Enchant Aura ID.
            $equipmentCache .= $item . ' 0 ';
        }

        $chardata["equipmentCache"] = trim($equipmentCache);

        // --------------------------------------------------------------------
        //  Currency
        // --------------------------------------------------------------------
        $currency = [];

        if (isset($data["currency"]) && is_array($data["currency"])) {
            foreach ($data["currency"] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id  = $item['I'] ?? 0;
                $cnt = $item['C'] ?? 0;

                if ($id != 0 && $cnt != 0) {
                    $currency[] = [
                        'I' => $id,
                        'C' => $cnt,
                    ];
                }
            }
        }

        $chardata["currency"] = $currency;

        // --------------------------------------------------------------------
        //  Reputation
        // --------------------------------------------------------------------

        $reputation = [];

        if (isset($data["reputation"]) && is_array($data["reputation"])) {
            foreach ($data["reputation"] as $rep) {
                if (!is_array($rep)) {
                    continue;
                }

                $factionId = (int)($rep["ID"] ?? 0);
                $value     = (int)($rep["Value"] ?? $rep["V"] ?? 0);

                if ($factionId <= 0) {
                    continue;
                }

                $reputation[] = [
                    'ID'       => $factionId,
                    'Value'    => $value,
                    'Flags'    => (int)($rep["Flags"] ?? 1),
                    'Name'     => $this->getFactionName($factionId, $chardata["main"]["locale"] ?? ''),
                    'Standing' => $this->getReputationStanding($value),
                ];
            }
        }

        $chardata["reputation"] = $reputation;

        // --------------------------------------------------------------------
        //  Professionen / Skills
        // --------------------------------------------------------------------
        $skills      = [];
        $professions = [
            "main"      => [],
            "secondary" => [],
            "other"     => [],
        ];

        $profmain = [
            2259  => "Alchemy",
            2018  => "Blacksmithing",
            7411  => "Enchanting",
            4036  => "Engineering",
            2366  => "Herb Gathering",
            45357 => "Inscription",
            25229 => "Jewelcrafting",
            2108  => "Leatherworking",
            8613  => "Skinning",
            3908  => "Tailoring",
        ];

        $profsec = [
            45542 => "First Aid",
            65293 => "Fishing",
            51296 => "Cooking",
        ];

        $other = [
            // English
            "Fishing"     => 51294,
            "First Aid"   => 7924,
            "Riding"      => 34090,
            // German
            "Angeln"      => 51294,
            "Erste Hilfe" => 7924,
            "Reiten"      => 34090,
            // French
            "Pêche"       => 51294,
            "Secourisme"  => 7924,
            "Monte"       => 34090,
            // Spanish
            "Pesca"           => 51294,
            "Primeros auxilios" => 7924,
            "Equitación"      => 34090,
            // Russian
            "Рыбная ловля" => 51294,
            "Первая помощь" => 7924,
            "Верховая езда" => 34090,
        ];

        if (isset($data["skills"]) && is_array($data["skills"])) {
            foreach ($data["skills"] as $skill) {
                if (!is_array($skill)) {
                    continue;
                }

                // Skillline-ID aufloesen: ohne sie fehlt der Skill beim Transfer
                $resolved = $this->resolveSkillId($skill, $class);

                if ($resolved['id'] > 0) {
                    $current = (int)($skill['C'] ?? 0);
                    $max     = (int)($skill['M'] ?? 0);

                    // Klassen-Skill-Lines meldet der Client mit 1/1, die Core fuehrt
                    // sie aber als level*5 (Level 80 = 400/400) - siehe character_skills.
                    if ($resolved['classline']) {
                        $cap     = min(400, ((int)($data["unitinfo"]["level"] ?? 1)) * 5);
                        $current = $cap;
                        $max     = $cap;
                    }

                    $skills[] = [
                        'Skill' => $resolved['id'],
                        'Name'  => isset($skill['N']) ? (string)$skill['N'] : '',
                        'C'     => $current,
                        'M'     => $max,
                    ];
                }

                if (isset($skill["S"])) {
                    // Check if the skill is in profmain or profsec and add them to professions
                    if (isset($profmain[$skill['S']])) {
                        $professions['main'][] = [
                            'Current' => $skill['C'] ?? 0,
                            'Max'     => $skill['M'] ?? 0,
                            'Link'    => '<a  data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $skill['S'] . '"></a>',
                        ];
                    } elseif (isset($profsec[$skill['S']])) {
                        $professions['secondary'][] = [
                            'Current' => $skill['C'] ?? 0,
                            'Max'     => $skill['M'] ?? 0,
                            'Link'    => '<a data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $skill['S'] . '"></a>',
                        ];
                    }
                } else {
                    if (isset($skill['N']) && isset($other[$skill['N']])) {
                        $professions['other'][] = [
                            'Name'    => $skill['N'],
                            'Current' => $skill['C'] ?? 0,
                            'Max'     => $skill['M'] ?? 0,
                            'Link'    => '<a data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $other[$skill['N']] . '"></a>',
                        ];
                    }
                }
            }
        }

        // Skill-Lines, die das Addon nicht meldet (Header/versteckt), die auf dem
        // Realm aber jeder Charakter hat: 183 GENERIC (DND), 777 Mounts,
        // 778 Companion Pets. max entspricht level*5 (Level 80 = 400), 183 zusaetzlich value = max.
        $levelCap = min(400, ((int)($data["unitinfo"]["level"] ?? 1)) * 5);
        $known    = [];

        foreach ($skills as $entry) {
            $known[$entry['Skill']] = true;
        }

        foreach ([183 => $levelCap, 777 => 1, 778 => 1] as $hiddenId => $hiddenValue) {
            if (isset($known[$hiddenId])) {
                continue;
            }

            $known[$hiddenId] = true;

            $skills[] = [
                'Skill' => $hiddenId,
                'Name'  => '',
                'C'     => $hiddenValue,
                'M'     => $levelCap,
            ];
        }

        // Start-Skill-Lines der Rasse/Klasse (playercreateinfo_skills): rassenspezifische
        // Linien und Dual Wield/Wands tauchen im Dump-UI nicht auf, die Core fuehrt sie
        // aber fuer jeden Charakter. Was der Dump bereits gemeldet hat, bleibt unangetastet.
        $raceMask  = ($race  > 0) ? (1 << ($race - 1))  : 0;
        $classMask = ($class > 0) ? (1 << ($class - 1)) : 0;

        foreach ($this->createinfoSkills as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $skillId = (int)($entry['skill'] ?? 0);

            if ($skillId <= 0 || isset($known[$skillId])) {
                continue;
            }

            $entryRace  = (int)($entry['raceMask'] ?? 0);
            $entryClass = (int)($entry['classMask'] ?? 0);

            if ($entryRace != 0 && ($entryRace & $raceMask) === 0) {
                continue;
            }

            if ($entryClass != 0 && ($entryClass & $classMask) === 0) {
                continue;
            }

            $known[$skillId] = true;

            $skills[] = [
                'Skill' => $skillId,
                'Name'  => '',
                'C'     => $levelCap,
                'M'     => $levelCap,
            ];
        }

        $chardata["skills"]      = $skills;
        $chardata["professions"] = $professions;

        // --------------------------------------------------------------------
        //  Talente & Spezialisierung
        // --------------------------------------------------------------------
        $talentPoints = [
            "highesttab"      => "",
            "highestTabName"  => "",
            1 => 0,
            2 => 0,
            3 => 0,
        ];

        $highestTab    = 0;
        $highestPoints = 0;

        if (isset($data["talents"][0]) && is_array($data["talents"][0])) {
            foreach ($data["talents"][0] as $talentTab) {
                if (!isset($talentTab['talents']) || !is_array($talentTab['talents'])) {
                    continue;
                }

                foreach ($talentTab['talents'] as $talent) {
                    if (!is_array($talent) || !isset($talent['tab'], $talent['currentRank'])) {
                        continue;
                    }

                    $tabIdx = $talent['tab'];
                    $talentPoints[$tabIdx] += $talent['currentRank'];

                    if ($talentPoints[$tabIdx] > $highestPoints) {
                        $highestPoints             = $talentPoints[$tabIdx];
                        $highestTab                = $tabIdx;
                        $talentPoints['highesttab']     = $talentTab['icon'] ?? '';
                        $talentPoints['highestTabName'] = $talentTab['name'] ?? '';
                    }
                }
            }
        }

        $talentPointsTwo = [
            "highestTabTwo"   => "",
            "highestTabName"  => "",
            1 => 0,
            2 => 0,
            3 => 0,
        ];

        $highestTabTwo    = 0;
        $highestPointsTwo = 0;

        if (isset($data["talents"][1]) && is_array($data["talents"][1])) {
            foreach ($data["talents"][1] as $talentTab) {
                if (!isset($talentTab['talents']) || !is_array($talentTab['talents'])) {
                    continue;
                }

                foreach ($talentTab['talents'] as $talent) {
                    if (!is_array($talent) || !isset($talent['tab'], $talent['currentRank'])) {
                        continue;
                    }

                    $tabIdx = $talent['tab'];
                    $talentPointsTwo[$tabIdx] += $talent['currentRank'];

                    if ($talentPointsTwo[$tabIdx] > $highestPointsTwo) {
                        $highestPointsTwo             = $talentPointsTwo[$tabIdx];
                        $highestTabTwo                = $tabIdx;
                        $talentPointsTwo['highestTabTwo']   = $talentTab['icon'] ?? '';
                        $talentPointsTwo['highestTabName']  = $talentTab['name'] ?? '';
                    }
                }
            }
        }

        // Geld
        $moneyRaw = $data["unitinfo"]["money"] ?? 0;
        $moneyRaw = (int)$moneyRaw;

        $gold      = floor($moneyRaw / 10000);
        $remainder = $moneyRaw % 10000;

        $silver = floor($remainder / 100);
        $copper = $remainder % 100;

        $newmoney = [
            'gold'   => $gold,
            'silver' => $silver,
            'copper' => $copper,
        ];

        $chardata["main"]["money"] = $newmoney;

        $chardata["talenttree"][1] = $talentPoints;
        $chardata["talenttree"][2] = $talentPointsTwo;

        // --------------------------------------------------------------------
        //  Talentspec-Masken (für character_talent SQL)
        // --------------------------------------------------------------------
        $ranks   = '';
        $specmask = [];

        if (isset($data['talents'][0]) && is_array($data['talents'][0])) {
            foreach ($data['talents'][0] as $index => $talent) {
                if (!is_numeric($index) || !is_array($talent)) {
                    continue;
                }

                foreach ($talent as $talentfurther) {
                    if (!is_array($talentfurther)) {
                        continue;
                    }

                    foreach ($talentfurther as $talentspell) {
                        if (!is_array($talentspell) || !isset($talentspell['currentRank'])) {
                            continue;
                        }

                        $ranks .= $talentspell['currentRank'];

                        if ($talentspell['currentRank'] >= 1) {
                            $talendID = $talentspell['talentID'] ?? null;

                            if ($talendID !== null &&
                                isset($this->talentspells[$talendID]["ranks"][$talentspell['currentRank']])) {
                                $actualspell = $this->talentspells[$talendID]["ranks"][$talentspell['currentRank']];
                                $specmask[0][] = $actualspell;
                            }
                        }
                    }
                }

                $ranks .= "-";
            }

            $ranks = rtrim($ranks, '-');
        }

        $ranks2 = '';

        if (isset($data['talents'][1]) && is_array($data['talents'][1])) {
            foreach ($data['talents'][1] as $index => $talent) {
                if (!is_numeric($index) || !is_array($talent)) {
                    continue;
                }

                foreach ($talent as $talentfurther) {
                    if (!is_array($talentfurther)) {
                        continue;
                    }

                    foreach ($talentfurther as $talentspell) {
                        if (!is_array($talentspell) || !isset($talentspell['currentRank'])) {
                            continue;
                        }

                        $ranks2 .= $talentspell['currentRank'];

                        if ($talentspell['currentRank'] >= 1) {
                            $talendID = $talentspell['talentID'] ?? null;

                            if ($talendID !== null &&
                                isset($this->talentspells[$talendID]["ranks"][$talentspell['currentRank']])) {
                                $actualspell = $this->talentspells[$talendID]["ranks"][$talentspell['currentRank']];
                                $specmask[1][] = $actualspell;
                            }
                        }
                    }
                }

                $ranks2 .= "-";
            }

            $ranks2 = rtrim($ranks2, '-');
        }
        $glyphcodeOne =  $this->encodeWowheadGlyphHashFromType($chardata["glyphs"][0]);
        $glyphcodeTwo =  $this->encodeWowheadGlyphHashFromType($chardata["glyphs"][1]);
        $chardata["specmask"] = $specmask ?? [];

        $chardata["talenttree"][1]["link"] = $ranks ."_" . $glyphcodeOne ?? '';
        $chardata["talenttree"][2]["link"] = $ranks2 ."_" . $glyphcodeTwo ?? '';
        //



        return $chardata;
    }

    // ------------------------------------------------------------------------
    //  Skillline-IDs
    // ------------------------------------------------------------------------

    /**
     * Skillline-ID zu einem Skill-Eintrag aus dem Dump.
     *
     * Reihenfolge:
     *  1. "Skill"                 - liefert das Addon selbst (Waffenfertigkeiten ueber die
     *                               Achievement-Kriterien, Asset-ID == Skillline-ID)
     *  2. "S"  ueber by_spell     - Professionen, locale-unabhaengig
     *  3. "N"  ueber by_name      - uebersetzte Skill-Line-Namen
     *  4. "N"  ueber by_class_name - Klassen-Skill-Lines (Holy/Protection/...)
     *
     * @param  array $skill
     * @param  int   $classId
     * @return array array('id' => Skillline-ID (0 = nicht aufloesbar),
     *                      'classline' => true bei Aufloesung ueber by_class_name)
     */
    private function resolveSkillId(array $skill, $classId = 0)
    {
        if (isset($skill['Skill']) && (int)$skill['Skill'] > 0) {
            return ['id' => (int)$skill['Skill'], 'classline' => false];
        }

        if (isset($skill['S']) && isset($this->skilllines['by_spell'][(int)$skill['S']])) {
            return ['id' => (int)$this->skilllines['by_spell'][(int)$skill['S']], 'classline' => false];
        }

        $name = isset($skill['N']) ? trim((string)$skill['N']) : '';
        if ($name === '') {
            return ['id' => 0, 'classline' => false];
        }

        if (isset($this->skilllines['by_name'][$name])) {
            return ['id' => (int)$this->skilllines['by_name'][$name], 'classline' => false];
        }

        if ($classId > 0 && isset($this->skilllines['by_class_name'][$classId][$name])) {
            return ['id' => (int)$this->skilllines['by_class_name'][$classId][$name], 'classline' => true];
        }

        return ['id' => 0, 'classline' => false];
    }
}
