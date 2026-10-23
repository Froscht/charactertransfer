<?php


class CharacterSqlGenerator
{
    /**
     * Fraktions-Tabelle aus constants.php
     * (wird für Reputation gebraucht)
     * @var array
     */
    private $factions = [];

    public function __construct(array $factions = [])
    {
        // transfer_model uebergibt bisher ['factions' => [...]] - beides akzeptieren
        if (isset($factions['factions']) && is_array($factions['factions'])) {
            $factions = $factions['factions'];
        }

        $this->factions = $factions;
    }

    /**
     * Fraktions-ID ueber den Namen finden (Fallback, wenn der Dump keine ID enthaelt).
     *
     * @param  string $name
     * @param  string $locale z.B. enUS / deDE
     * @return int 0 wenn nichts gefunden wurde
     */
    private function findFactionByName($name, $locale = '')
    {
        $name = trim((string)$name);
        if ($name === '') {
            return 0;
        }

        foreach ($this->factions as $factionId => $locales) {
            if (!is_array($locales)) {
                continue;
            }

            if ($locale !== '' && isset($locales[$locale]['Name']) && $locales[$locale]['Name'] === $name) {
                return (int)$factionId;
            }

            foreach ($locales as $entry) {
                if (isset($entry['Name']) && $entry['Name'] === $name) {
                    return (int)$factionId;
                }
            }
        }

        return 0;
    }

    /**
     * INSERT fuer character_inventory.
     *
     * @param int $bag  Guid des Container-Items, 0 = ohne Beutel
     * @param int $slot Core-Slot (0-based) bzw. Position im Container
     * @param int $item Guid des item_instance-Eintrags
     * @return string
     */
    private function inventorySql($bag, $slot, $item)
    {
        return "INSERT INTO `character_inventory` (`guid`, `bag`, `slot`, `item`) VALUES ('999', '{$bag}', '{$slot}', '{$item}');<br>";
    }

    /**
     * INSERT fuer item_instance.
     * Haltbarkeit/Charges/Roll-Property sind aus dem Dump nicht ermittelbar und bleiben Placeholder.
     *
     * @param int   $item
     * @param array $itemData normalisierter Item-Datensatz (ID, E, ...)
     * @return string
     */
    private function itemInstanceSql($item, array $itemData)
    {
        return "INSERT INTO `item_instance` (`guid`, `itemEntry`, `owner_guid`, `creatorGuid`, `giftCreatorGuid`, `count`, `duration`, `charges`, `flags`, `enchantments`, `randomPropertyId`, `durability`, `playedTime`, `text`) VALUES ('{$item}', '{$itemData["ID"]}', '999', '0', '0', '1', '0', '0 0 0 0 0 ', '0', '{$itemData["E"]} 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ', '0', '35', '0', 'NULL');<br>";
    }

    /**
     * Wichtig:
     * - $chardata = Ergebnis von CharacterDumpParser::ReadCharacterDump()
     * - $original = (optional) Original-Dump-Array (für skills/reputation/statistics)
     * - SQL-Ausgabe ist inhaltlich wie in deinem ursprünglichen genCharDump
     */
    public function genCharDump(array $chardata, array $original = null)
    {
        // Das globale Flag wie in deinem bisherigen Code
        global $mysqloutput;
        if ($mysqloutput === null) {
            $mysqloutput = true;
        }

        // ---------------------------- CHARACTERS -----------------------------
        $array = [
            'guid' => '999',
            'account' => '888',
            'name' => $chardata["main"]["name"],
            'race' => $chardata["main"]["race"],
            'class' => $chardata["main"]["class"],
            'gender' => $chardata["main"]["gender"],
            'level' => $chardata["main"]["level"],
            'xp' => '0',
            'money' => $chardata["main"]["money"]["gold"] * 100 * 100
                    + $chardata["main"]["money"]["silver"] * 100
                    + $chardata["main"]["money"]["copper"],
            'skin' => '0',
            'face' => '0',
            'hairStyle' => '0',
            'hairColor' => '9',
            'facialStyle' => '4',
            'bankSlots' => '7',
            'restState' => '2',
            'playerFlags' => '0',
            'position_x' => '5877.93',
            'position_y' => '641.871',
            'position_z' => '646.24',
            'map' => '571',
            'instance_id' => '0',
            'instance_mode_mask' => '0',
            'orientation' => '0',
            'taximask' => ' ',
            'online' => '1',
            'cinematic' => '1',
            'totaltime' => $chardata["main"]["playtimeSeconds"],
            'leveltime' => $chardata["main"]["playtimeOnLevelSeconds"],
            'logout_time' => '1685703040',
            'is_logout_resting' => '0',
            'rest_bonus' => '0.0289333',
            'resettalents_cost' => '0',
            'resettalents_time' => '0',
            'trans_x' => '0',
            'trans_y' => '0',
            'trans_z' => '0',
            'trans_o' => '0',
            'transguid' => '0',
            'extra_flags' => '0',
            'stable_slots' => '0',
            'at_login' => '0',
            'zone' => '12',
            'death_expire_time' => '0',
            'taxi_path' => null,
            'arenaPoints' => $chardata["main"]["arenapoints"],
            'totalHonorPoints' => $chardata["main"]["honor"],
            'todayHonorPoints' => '0',
            'yesterdayHonorPoints' => '0',
            'totalKills' => $chardata["main"]["kills"],
            'todayKills' => '0',
            'yesterdayKills' => '0',
            'chosenTitle' => '0',
            'knownCurrencies' => '0',
            'watchedFaction' => '0',
            'drunk' => '100',
            'health' => $chardata["main"]["stats"]["health"],
            'power1' => '0',
            'power2' => '0',
            'power3' => '100',
            'power4' => '0',
            'power5' => '0',
            'power6' => '0',
            'power7' => '8',
            'latency' => '1',
            'talentGroupsCount' => '2',
            'activeTalentGroup' => '0',
            'exploredZones' => '0',
            'equipmentCache' => $chardata["equipmentCache"],
            'ammoId' => '0',
            'knownTitles' => '0',
            'actionBars' => '15',
            'grantableLevels' => '0',
            'order' => '0',
            'creation_date' => date('Y-m-d H:i:s'),
            'deleteInfos_Account' => null,
            'deleteInfos_Name' => null,
            'deleteDate' => null,
            'innTriggerId' => '0'
        ];

        $keys = '`' . implode('`, `', array_keys($array)) . '`';

        $values = [];
        foreach ($array as $value) {
            // null = echtes SQL-NULL; ein String 'NULL' waer ein Integer-/Blob-Fehlwert
            $values[] = ($value === null) ? 'NULL' : "'" . $value . "'";
        }
        $values = implode(', ', $values);

        $query = "INSERT INTO `characters` ($keys) VALUES ($values);";

        if ($mysqloutput) {
            echo $query . "<br>";
        }

        // ---------------------------- ITEMS/EQUIPMENT ------------------------
        $itemnumber = 1337;

        $bagOne = "";
        $bagTwo = "";
        $bagThree = "";
        $bagfour = "";

        krsort($chardata["Equippment"]);

        foreach ($chardata["Equippment"] as $slot => $item) {
            $newslot = $slot - 1;

            switch ($newslot) {
                case 19:
                    $bagOne = $itemnumber;
                    break;
                case 20:
                    $bagTwo = $itemnumber;
                    break;
                case 21:
                    $bagThree = $itemnumber;
                    break;
                case 22:
                    $bagfour = $itemnumber;
                    break;
            }

            if ($mysqloutput) {
                echo $this->inventorySql(0, $newslot, $itemnumber);
                echo $this->itemInstanceSql($itemnumber, $item);
            }
            $itemnumber++;
        }

        ksort($chardata["Inventory"]);

        foreach ($chardata["Inventory"] as $bag => $itemarray) {
            $bagnumber = $bag - 1000;
            ksort($itemarray);
            foreach ($itemarray as $slot => $item) {
                $newslot = $slot - 1;

                switch ($bagnumber) {
                    case 0:
                        // Rucksack: der Core fuehrt den Rucksack im flachen Slot-Bereich
                        // hinter den Ausruestungsslots (23..38), nicht ab 0
                        $bag = 0;
                        $newslot = $slot + 22;
                        break;
                    case 1:
                        $bag = $bagOne;
                        break;
                    case 2:
                        $bag = $bagTwo;
                        break;
                    case 3:
                        $bag = $bagThree;
                        break;
                    case 4:
                        $bag = $bagfour;
                        break;
                    default:
                        $bag = $bagnumber;
                        break;
                }

                if ($mysqloutput) {
                    echo $this->inventorySql($bag, $newslot, $itemnumber);
                    echo $this->itemInstanceSql($itemnumber, $item);
                }

                $itemnumber++;
            }
        }

        // ---------------------------- BANK ------------------------------------
        // Bank fuehrt eigene Slots im flachen Bereich des Cores:
        // 39..66 = Bankplaetze, 67..73 = Bankbeutel; der Inhalt haengt am
        // Guid des jeweiligen Bankbeutels (Container 5..11 -> Slot 67..73).
        if (isset($chardata["bank"]) && is_array($chardata["bank"])) {
            $bank         = $chardata["bank"];
            $bankBagGuids = [];

            // 1) Bankbeutel selbst - muss vor dem Inhalt kommen, da deren Guids referenziert werden
            if (isset($bank["bags"]) && is_array($bank["bags"])) {
                foreach ($bank["bags"] as $slot => $item) {
                    $bankBagGuids[(int)$slot] = $itemnumber;

                    if ($mysqloutput) {
                        echo $this->inventorySql(0, (int)$slot, $itemnumber);
                        echo $this->itemInstanceSql($itemnumber, $item);
                    }
                    $itemnumber++;
                }
            }

            // 2) Bankplaetze 39..66
            if (isset($bank["slots"]) && is_array($bank["slots"])) {
                foreach ($bank["slots"] as $slot => $item) {
                    if ($mysqloutput) {
                        echo $this->inventorySql(0, (int)$slot, $itemnumber);
                        echo $this->itemInstanceSql($itemnumber, $item);
                    }
                    $itemnumber++;
                }
            }

            // 3) Inhalt der Bankbeutel
            if (isset($bank["contents"]) && is_array($bank["contents"])) {
                foreach ($bank["contents"] as $container => $items) {
                    if (!is_array($items)) {
                        continue;
                    }

                    $bagSlot = 67 + ((int)$container - 5);

                    if (!isset($bankBagGuids[$bagSlot])) {
                        // Beutel existiert nicht (leerer Bankslot) - Inhalt kann nicht haengen
                        continue;
                    }

                    foreach ($items as $slot => $item) {
                        if ($mysqloutput) {
                            echo $this->inventorySql($bankBagGuids[$bagSlot], (int)$slot - 1, $itemnumber);
                            echo $this->itemInstanceSql($itemnumber, $item);
                        }
                        $itemnumber++;
                    }
                }
            }
        }

        // ---------------------------- ACHIEVEMENTS ---------------------------
        ksort($chardata["achievements"]);
        foreach ($chardata["achievements"] as $achievement) {
            if ($achievement['completed'] == 1) {
                $time = $achievement["time"];
                $id = $achievement["id"];
                if ($mysqloutput) {
                    echo "INSERT INTO `character_achievement` (`guid`, `achievement`, `date`) VALUES ('999', '{$id}', '{$time}');<br>";
                }
            }
        }

        // newspells – hier hattest du nichts ausgegeben, das bleibt so
        foreach ($chardata["newspells"] as $spellId) {
            // nix
        }

        // ---------------------------- SKILLS/STATISTICS/REPUTATION ----------
        $hasOriginal = is_array($original);

        // Der Parser hat die Skillline-IDs bereits aufgeloest; der Rohdump dient nur
        // als Fallback (dort stehen ausschliesslich die Waffenfertigkeiten aus dem Addon).
        $skillRows = [];

        if (isset($chardata["skills"]) && is_array($chardata["skills"]) && $chardata["skills"] !== []) {
            $skillRows = $chardata["skills"];
        } elseif ($hasOriginal && isset($original["skills"]) && is_array($original["skills"])) {
            $skillRows = $original["skills"];
        }

        $emittedSkills = [];

        foreach ($skillRows as $skill) {
            if (!is_array($skill) || !isset($skill["Skill"]) || (int)$skill["Skill"] <= 0) {
                continue;
            }

            $skillid = (int)$skill["Skill"];

            // (guid, skill) ist Primary Key - ein Duplikat waere ein fehlerhafter Import
            if (isset($emittedSkills[$skillid])) {
                continue;
            }
            $emittedSkills[$skillid] = true;

            $current = isset($skill["C"]) ? (int)$skill["C"] : 0;
            $max     = isset($skill["M"]) ? (int)$skill["M"] : 0;

            if ($mysqloutput) {
                echo "INSERT INTO `character_skills` (`guid`, `skill`, `value`, `max`) VALUES ('999', '{$skillid}', '{$current}', '{$max}');<br>";
            }
        }

        // Statistik (weiterhin auskommentiert)
        $insertArray = array();
        if ($hasOriginal && isset($original["statistic"])) {
            foreach ($original["statistic"] as $value) {
                if (isset($value["criteria"])) {
                    foreach ($value["criteria"] as $criteria) {
                        if (isset($criteria["criteriaID"]) && isset($criteria["quantity"]) && isset($criteria["criteriaString"])) {
                            $id = $criteria["criteriaID"];
                            $statisticquantity = $criteria["quantity"];
                            $time = time();

                            if ($statisticquantity != 0) {
                                if (isset($insertArray[$id])) {
                                    if ($statisticquantity < $insertArray[$id]["quantity"]) {
                                        $insertArray[$id]["quantity"] = $statisticquantity;
                                        $insertArray[$id]["time"] = $time;
                                    }
                                } else {
                                    $insertArray[$id] = array(
                                        "quantity" => $statisticquantity,
                                        "time" => $time
                                    );
                                }
                            }
                        }
                    }
                }
            }

            foreach ($insertArray as $id => $data) {
                // echo "INSERT INTO `character_achievement_progress` (`guid`, `criteria`, `counter`, `date`) VALUES ('999', '{$id}', '{$data["quantity"]}', '{$data["time"]}');<br>";
            }
        }

        if ($hasOriginal && isset($original["reputation"]) && is_array($original["reputation"])) {

            $locale = isset($chardata["main"]["locale"]) ? $chardata["main"]["locale"] : '';

            foreach ($original["reputation"] as $reputation) {
                if (!is_array($reputation)) {
                    continue;
                }

                // Der Dump liefert die Fraktions-ID direkt (ID/Value/Flags aus GetFactionInfoByID)
                $factionId = isset($reputation["ID"]) ? (int)$reputation["ID"] : 0;
                $value     = isset($reputation["Value"]) ? (int)$reputation["Value"] : (isset($reputation["V"]) ? (int)$reputation["V"] : 0);
                $flags     = isset($reputation["Flags"]) ? (int)$reputation["Flags"] : 1;

                // Fallback fuer Dumps, die nur den Fraktionsnamen enthalten
                if ($factionId <= 0 && isset($reputation["N"])) {
                    $factionId = $this->findFactionByName($reputation["N"], $locale);
                }

                if ($factionId <= 0 || abs($value) === 0) {
                    continue;
                }

                if ($mysqloutput) {
                    echo "INSERT INTO `character_reputation` (`guid`, `faction`, `standing`, `flags`) VALUES ('999', '{$factionId}', '{$value}', '{$flags}');<br>";
                }
            }
        }

        // ---------------------------- TALENTE / SPEC MASK -------------------
        $spellmaskcount = array();

        if (!empty($chardata["specmask"][0])) {
            foreach ($chardata["specmask"][0] as $spell) {
                $spellmaskcount[$spell] = 1;
            }
        }

        if (!empty($chardata["specmask"][1])) {
            foreach ($chardata["specmask"][1] as $spell) {
                if (isset($spellmaskcount[$spell])) {
                    $spellmaskcount[$spell] += 2;
                } else {
                    $spellmaskcount[$spell] = 2;
                }
            }
        }

        ksort($spellmaskcount);
        foreach ($spellmaskcount as $spell => $spellmask) {
            if ($mysqloutput) {
                echo "INSERT INTO `character_talent` (`guid`, `spell`, `specMask`) VALUES ('999', '{$spell}', '{$spellmask}');<br>";
            }
        }

        // ---------------------------- GLYPHS --------------------------------
        foreach ($chardata["glyphs"] as $key => $glyphList) {
            if (!is_array($glyphList)) {
                continue;
            }

            // character_glyphs speichert die Glyph-Indizes (glyphID), nicht die Spell-IDs
            $values = [];
            for ($slot = 0; $slot < 6; $slot++) {
                $glyphId = isset($glyphList[$slot]["glyphID"]) ? (int)$glyphList[$slot]["glyphID"] : 0;
                $values[] = "'{$glyphId}'";
            }

            $talentGroup = (int)$key;

            if ($mysqloutput) {
                echo "INSERT INTO `character_glyphs` (`guid`, `talentGroup`, `glyph1`, `glyph2`, `glyph3`, `glyph4`, `glyph5`, `glyph6`) VALUES ('999', '{$talentGroup}', " . implode(', ', $values) . ");<br>";
            }
        }
    }
}
