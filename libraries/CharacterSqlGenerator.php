<?php


class CharacterSqlGenerator
{
    /**
     * Fraktions-Tabelle aus constants.php
     * (wird für Reputation gebraucht)
     * @var array
     */
    private $factions = [];

    public function __construct(array $factions)
    {
        $this->factions = $factions;
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
            'money' => $chardata["main"]["money"]["gold"] * 100 * 100,
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
            'totaltime' => $chardata["main"]["playtime"],
            'leveltime' => $chardata["main"]["playtime"],
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
            'taxi_path' => 'NULL',
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
            'deleteInfos_Account' => 'NULL',
            'deleteInfos_Name' => 'NULL',
            'deleteDate' => 'NULL',
            'innTriggerId' => '0'
        ];

        $keys = '`' . implode('`, `', array_keys($array)) . '`';
        $values = "'" . implode("', '", array_values($array)) . "'";

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
        $bagBankOne = "";
        $bagBankTwo = "";
        $bagBankThree = "";
        $bagBankFour = "";
        $bagBankFive = "";
        $bagBankSix = "";
        $bagBankSeven = "";

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
                case 67:
                    $bagBankOne = $itemnumber;
                    break;
                case 68:
                    $bagBankTwo = $itemnumber;
                    break;
                case 69:
                    $bagBankThree = $itemnumber;
                    break;
                case 70:
                    $bagBankFour = $itemnumber;
                    break;
                case 71:
                    $bagBankFive = $itemnumber;
                    break;
                case 72:
                    $bagBankSix = $itemnumber;
                    break;
                case 73:
                    $bagBankSeven = $itemnumber;
                    break;
            }

            if ($mysqloutput) {
                echo "INSERT INTO `character_inventory` (`guid`, `bag`, `slot`, `item`) VALUES ('999', '0', '{$newslot}', '{$itemnumber}'); <br>";
                echo "INSERT INTO `item_instance` (`guid`, `itemEntry`, `owner_guid`, `creatorGuid`, `giftCreatorGuid`, `count`, `duration`, `charges`, `flags`, `enchantments`, `randomPropertyId`, `durability`, `playedTime`, `text`) VALUES ('{$itemnumber}', '{$item["ID"]}', '999', '0', '0', '1', '0', '0 0 0 0 0 ', '0', '{$item["E"]} 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ', '0', '35', '0', 'NULL'); <br>";
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
                    case 5:
                        $bag = $bagBankOne;
                        break;
                    case 6:
                        $bag = $bagBankTwo;
                        break;
                    case 7:
                        $bag = $bagBankThree;
                        break;
                    case 8:
                        $bag = $bagBankFour;
                        break;
                    case 9:
                        $bag = $bagBankFive;
                        break;
                    case 10:
                        $bag = $bagBankSix;
                        break;
                    case 11:
                        $bag = $bagBankSeven;
                        break;
                    default:
                        $bag = $bagnumber;
                        break;
                }

                if ($mysqloutput) {
                    echo "INSERT INTO `character_inventory` (`guid`, `bag`, `slot`, `item`) VALUES ('999', '{$bag}', '{$newslot}', '{$itemnumber}');<br>";
                    echo "INSERT INTO `item_instance` (`guid`, `itemEntry`, `owner_guid`, `creatorGuid`, `giftCreatorGuid`, `count`, `duration`, `charges`, `flags`, `enchantments`, `randomPropertyId`, `durability`, `playedTime`, `text`) VALUES ('{$itemnumber}', '{$item["ID"]}', '999', '0', '0', '1', '0', '0 0 0 0 0 ', '0', '{$item["E"]} 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ', '0', '35', '0', 'NULL'); <br>";
                }

                $itemnumber++;
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

        if ($hasOriginal && isset($original["skills"]) && is_array($original["skills"])) {
            foreach ($original["skills"] as $skill) {
                if (isset($skill["Skill"])) {
                    $skillid = $skill["Skill"];
                    $current = $skill["C"];
                    $max = $skill["M"];
                    if ($mysqloutput) {
                        echo "INSERT INTO `character_skills` (`guid`, `skill`, `value`, `max`) VALUES ('999', '{$skillid}', '{$current}', '{$max}');<br>";
                    }
                }
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

            $factions = $this->factions;

            foreach ($original["reputation"] as $reputation) {
                $name = $reputation["N"];
                $value = $reputation["V"];

                if (abs($value) > 0) {
                    $result = null;

                    foreach ($factions as $key => $faction) {
                        $searchLocale = $chardata["main"]["locale"];
                        $searchName = $name;

                        if (isset($faction[$searchLocale]['Name']) && $faction[$searchLocale]['Name'] === $searchName) {
                            $result = $key;
                        } else {
                            $result = null;
                        }
                    }

                    if ($result !== null) {
                        if ($mysqloutput) {
                            echo "INSERT INTO `character_reputation` (`guid`, `faction`, `standing`, `flags`) VALUES ('999', '{$result}', '{$value}', '1');<br>";
                        }
                    }
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
        foreach ($chardata["glyphs"] as $key => $value) {

            $glyphs = implode(', ', $value);
            if ($mysqloutput) {
                echo "INSERT INTO `character_glyphs` (`guid`, `talentGroup`, `glyph1`, `glyph2`, `glyph3`, `glyph4`, `glyph5`, `glyph6`) VALUES ('999', {$key}, {$glyphs});<br>";
            }
        }
    }
}
