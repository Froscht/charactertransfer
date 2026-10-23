<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Globale Ausgabe-Steuerung für die Playerdump-SQLs
$mysqloutput = true;

class transfer_model extends CI_Model
{
    // Config-Daten
    private $spelldata = [];
    private $talentspells = [];
    private $factions = [];

    /** @var CharacterDumpParser */
    private $dumpParser;

    /** @var CharacterSqlGenerator */
    private $sqlGenerator;

    public function __construct()
    {
        parent::__construct();

        // constants.php laden (liegt meist unter application/config/constants.php)
        $this->load->config('constants');

        $this->spelldata    = (array) $this->config->item('spelldata');
        $this->talentspells = (array) $this->config->item('talentspells');
        $this->factions     = (array) $this->config->item('factions');

        /**
         * Libraries direkt einbinden, unabhängig vom MX_Loader:
         * application/modules/charactertransfer/libraries/CharacterDumpParser.php
         * application/modules/charactertransfer/libraries/CharacterSqlGenerator.php
         */
        require_once(APPPATH . 'modules/charactertransfer/libraries/CharacterDumpParser.php');
        require_once(APPPATH . 'modules/charactertransfer/libraries/CharacterSqlGenerator.php');

        // Parser und Generator manuell instanziieren
        $this->dumpParser = new CharacterDumpParser([
            'spelldata'    => $this->spelldata,
            'talentspells' => $this->talentspells,
            'factions'     => $this->factions,
        ]);

        $this->sqlGenerator = new CharacterSqlGenerator([
            'factions' => $this->factions,
        ]);
    }

    /**
     * Alle Transfers holen (Admin-Ansicht)
     *
     * @return array|false
     */
    public function getAllTransfers()
    {
        $query = $this->db
            ->select('id, accountid, granted, status, charactername, realm, server, class, race, gender')
            ->from('character_transfer')
            ->order_by('id', 'ASC')
            ->get();

        if (!$query->num_rows()) {
            return false;
        }

        return $this->ReturnTransferData($query->result_array());
    }

    /**
     * Transfers eines Accounts
     *
     * @param int $userid
     * @return array|false
     */
    public function getTransfersByAccountID($userid)
    {
        if (!is_numeric($userid)) {
            return false;
        }

        $userid = (int) $userid;

        $query = $this->db
            ->select('id, accountid, granted, status, charactername, realm, server, class, race, gender')
            ->from('character_transfer')
            ->where('accountid', $userid)
            ->order_by('id', 'ASC')
            ->get();

        if (!$query->num_rows()) {
            return false;
        }

        return $this->ReturnTransferData($query->result_array());
    }

    /**
     * Transfer per ID holen
     *
     * @param int $id
     * @return array|false
     */
    public function getTransferByID($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $id = (int) $id;

        $query = $this->db
            ->from('character_transfer')
            ->where('id', $id)
            ->get();

        if (!$query->num_rows()) {
            return false;
        }

        $data = [];

        foreach ($query->result_array() as $character) {
            $data[] = [
                'id'            => (int) $character['id'],
                // chardump wird hier bereits JSON-dekodiert
                'chardump'      => json_decode($character['chardump'], true),
                'userid'        => (int) $character['accountid'],
                'charactername' => $character['charactername'],
                'race'          => $this->getRaceString($character['race']),
                'gender'        => $this->getGenderString($character['gender']),
                'class'         => $this->getClassString($character['class']),
                'realm'         => $character['realm'],
                'status'        => $this->getStatus($character['status']),
            ];
        }

        return $data;
    }

    /**
     * Helfer: DB-Row(s) in saubere Ausgabe-Struktur konvertieren
     *
     * @param array $rows
     * @return array
     */
    public function ReturnTransferData(array $rows)
    {
        $data = [];

        foreach ($rows as $character) {
            $data[] = [
                'id'            => (int) $character['id'],
                'userid'        => (int) $character['accountid'],
                'charactername' => $character['charactername'],
                'race'          => $this->getRaceString($character['race']),
                'gender'        => $this->getGenderString($character['gender']),
                'class'         => $this->getClassString($character['class']),
                'realm'         => $character['realm'],
                'status'        => $this->getStatus($character['status']),
            ];
        }

        return $data;
    }

    /**
     * Prüfen, ob derselbe Char auf demselben Realm/Server schon einen Transfer hat
     */
    public function checkTransfer($userid, $charname, $realm, $serverip)
    {
        if (!is_numeric($userid)) {
            return false;
        }

        $userid   = (int) $userid;
        $charname = (string) $charname;
        $realm    = (string) $realm;
        $serverip = (string) $serverip;

        $query = $this->db
            ->from('character_transfer')
            ->where([
                'accountid'     => $userid,
                'charactername' => $charname,
                'realm'         => $realm,
                'server'        => $serverip,
            ])
            ->order_by('id', 'ASC')
            ->get();

        return $query->num_rows() > 0;
    }

    public function getGenderString($gender)
    {
        $gender = (int) $gender;

        switch ($gender) {
            case 0:  return 'Male';
            case 1:  return 'Female';
            default: return 'Unknown';
        }
    }

    public function getStatus($statuscode)
    {
        $statuscode = (int) $statuscode;

        switch ($statuscode) {
            case 0:  return 'Not Reviewd';
            case 1:  return 'Approved';
            case 2:  return 'In Progress';
            case 3:  return 'Denied';
            default: return 'uknown';
        }
    }

    public function getClassString($class)
    {
        $class = (int) $class;

        switch ($class) {
            case 0:  return 'None';
            case 1:  return 'Warrior';
            case 2:  return 'Paladin';
            case 3:  return 'Hunter';
            case 4:  return 'Rogue';
            case 5:  return 'Priest';
            case 6:  return 'DeathKnight';
            case 7:  return 'Shaman';
            case 8:  return 'Mage';
            case 9:  return 'Warlock';
            case 10: return 'Monk';
            case 11: return 'Druid';
            case 12: return 'Demon Hunter';
            default: return 'Unknown';
        }
    }

    public function getRaceString($race)
    {
        $race = (int) $race;

        switch ($race) {
            case 1:  return 'Human';
            case 2:  return 'Orc';
            case 3:  return 'Dwarf';
            case 4:  return 'Night Elf';
            case 5:  return 'Undead';
            case 6:  return 'Tauren';
            case 7:  return 'Gnome';
            case 8:  return 'Troll';
            case 10: return 'Blood Elf';
            case 11: return 'Draenei';
            default: return 'Unknown';
        }
    }

    /**
     * Fügt einen neuen Transfer ein, wenn er noch nicht existiert.
     *
     * @param string $chardump Lua-Datei-Inhalt mit CHDMP_DATA / CHDMP_KEY
     * @return int|false Insert-ID oder false
     */
    public function insertTransfer($chardump)
    {
        $user_id = (int) $this->user->getId();

        // 1) Lua-Dump -> JSON über Parser
        $jsonString = $this->dumpParser->ReturnCharDumpAsJson($chardump);
        $decoded    = json_decode($jsonString, true);

        if (!is_array($decoded)) {
            return false;
        }

        // 2) Normalisiertes Char-Array
        $characterdata = $this->dumpParser->ReadCharacterDump($decoded);

        // 3) Doppelten Transfer verhindern
        if ($this->checkTransfer(
            $user_id,
            $characterdata["main"]["name"],
            $characterdata["main"]["ServerRealm"],
            $characterdata["main"]["ServerIP"]
        )) {
            return false;
        }

        // 4) In character_transfer schreiben
        $data = [
            'accountid'     => $user_id,
            'chardump'      => $jsonString, // JSON vom Parser
            'granted'       => 0,
            'status'        => 0,
            'charactername' => $characterdata["main"]["name"],
            'realm'         => $characterdata["main"]["ServerRealm"],
            'server'        => $characterdata["main"]["ServerIP"],
            'class'         => $characterdata["main"]["class"],
            'race'          => $characterdata["main"]["race"],
            'gender'        => $characterdata["main"]["gender"],
        ];

        $this->db->insert('character_transfer', $data);

        return (int) $this->db->insert_id();
    }

    /**
     * Helper: generiert den Playerdump-SQL für einen bereits geparsten Charakter.
     *
     * @param array      $chardata  Ergebnis von CharacterDumpParser::ReadCharacterDump()
     * @param array|null $original  Originaldump (JSON-Array) für Skills/Reputation/etc.
     */
    public function generatePlayerDumpSql(array $chardata, array $original = null)
    {
        $this->sqlGenerator->genCharDump($chardata, $original);
    }

    /**
     * Komfort-Funktion:
     * Holt einen Transfer-Datensatz, parst den Dump und gibt direkt die Playerdump-SQLs aus.
     *
     * @param int $transferId
     * @return bool true bei Erfolg, false wenn Transfer nicht gefunden oder defekt
     */
    public function generatePlayerDumpSqlFromTransfer($transferId)
    {
        $transfer = $this->getTransferByID($transferId);
        if (!$transfer || empty($transfer[0]['chardump'])) {
            return false;
        }

        // In getTransferByID() wurde chardump bereits zu Array dekodiert
        $original = $transfer[0]['chardump'];

        if (!is_array($original)) {
            $original = json_decode($original, true);
        }

        if (!is_array($original)) {
            return false;
        }

        // Wieder in das interne Format normalisieren
        $chardata = $this->dumpParser->ReadCharacterDump($original);

        // SQL ausgeben
        $this->sqlGenerator->genCharDump($chardata, $original);

        return true;
    }

    /**
     * Wrapper für alte Aufrufe:
     * Delegiert an CharacterDumpParser->decodecharacterdump()
     */
    public function decodecharacterdump($data)
    {
        return $this->dumpParser->decodecharacterdump($data);
    }

    /**
     * Wrapper für alte Aufrufe:
     * Delegiert an CharacterDumpParser->ReturnCharDumpAsJson()
     */
    public function ReturnCharDumpAsJson($data)
    {
        return $this->dumpParser->ReturnCharDumpAsJson($data);
    }

    /**
     * Wrapper für alte Aufrufe:
     * Delegiert an CharacterDumpParser->ReadCharacterDump()
     *
     * $data kann entweder bereits ein Array sein (z.B. aus getTransferByID)
     * oder noch ein JSON-String / Rohdump.
     */
    public function ReadCharacterDump($data)
    {
        // Falls es kein Array ist, versuchen zu dekodieren
        if (!is_array($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (!is_array($data)) {
            return [];
        }

        return $this->dumpParser->ReadCharacterDump($data);
    }

    /**
     * Update transfer status by id.
     *
     * @param int $id
     * @param int $status 0=Not Reviewed, 1=Approved, 2=In Progress, 3=Denied
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        $id = (int)$id;
        $status = (int)$status;
        if ($id < 1) {
            return false;
        }
        $this->db->where('id', $id)->update('character_transfer', ['status' => $status]);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a transfer by id.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTransfer($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            return false;
        }
        $this->db->where('id', $id)->delete('character_transfer');
        return $this->db->affected_rows() > 0;
    }
}
