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
 */
class CharacterDumpParser
{
    /** @var array */
    private $spelldata = [];

    /** @var array */
    private $talentspells = [];

    /** @var array */
    private $factions = [];

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
        //  Geschlecht (Lua 1/2 -> 0/1)
        // --------------------------------------------------------------------
        $genderRaw = $data["unitinfo"]["gender"] ?? 1;
        switch ($genderRaw) {
            case 1:
                $gender = 1;
                break;
            case 2:
                $gender = 0;
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
                    $characterequippment[$slot] = [
                        'ID'  => $item['I'] ?? 0,
                        'Count' => $item['C'] ?? 1,
                        'G1' => $item['G1'] ?? 0,
                        'G2' => $item['G2'] ?? 0,
                        'G3' => $item['G3'] ?? 0,
                        'Q'  => $item['Quality'] ?? 0,
                        'E'  => $item['E'] ?? 0,
                    ];
                } else {
                    // Taschen / Bank-Inventory
                    if (!isset($newInventory[$bagId]) || !is_array($newInventory[$bagId])) {
                        $newInventory[$bagId] = [];
                    }

                    $newInventory[$bagId][$slot] = [
                        'ID'  => $item['I'] ?? 0,
                        'Count' => $item['C'] ?? 1,
                        'G1' => $item['G1'] ?? 0,
                        'G2' => $item['G2'] ?? 0,
                        'G3' => $item['G3'] ?? 0,
                        'Q'  => $item['Quality'] ?? 0,
                        'E'  => $item['E'] ?? 0,
                    ];
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

        $chardata["reputation"] = $data["reputation"];

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
}
