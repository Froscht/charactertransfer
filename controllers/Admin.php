<?php

class Admin extends MX_Controller
{
    // ------------------------------------------------------------------------
    //  Konfiguration & Konstanten
    // ------------------------------------------------------------------------

    /**
     * Slot-Mapping für Equipment -> Template-Key based on Client Lua 3.3.5
     */
    private const SLOT_NAMES = [
        1  => "head",
        2  => "neck",
        3  => "shoulders",
        4  => "body",
        5  => "chest",
        6  => "waist",
        7  => "legs",
        8  => "feet",
        9  => "wrists",
        10 => "hands",
        11 => "finger1",
        12 => "finger2",
        13 => "trinket1",
        14 => "trinket2",
        15 => "back",
        16 => "mainhand",
        17 => "offhand",
        18 => "ranged",
        19 => "tabard",
        20 => "bag1",
        21 => "bag2",
        22 => "bag3",
        23 => "bag4",
    ];



    /**
     * Slots, die im 3D-Modell dargestellt werden dürfen.
     */
    private const ALLOWED_MODEL_SLOTS = [
        1, 3, 4, 5, 6, 7, 8,
        9, 10, 15, 16, 17, 18, 19,
    ];

    /**
     * Wowhead-Konfiguration (Defaults, können per CI-Config überschrieben werden)
     */
    private $wowheadBaseUrl   = 'https://www.wowhead.com';
    private $wowheadLocale    = 'de';  // z.B. de, en, fr, ...
    private $wowheadTimeout   = 10;    // Sekunden
    private $wowheadUserAgent = 'FusionGen Character Transfer/1.0';

    /**
     * Caching
     */
    private $canCache = false;
    private $cache_dir = 'application/cache/charactertransfer/';

    /**
     * Assets
     */
    private $css;
    private $js;

    /**
     * Charakter-/Realm-bezogene Properties
     */
    private $charData = [];


    /**
     * Item-/Model-Daten für das Template
     */
    private $items = [];
    private $model = [];

    public function __construct()
    {
        parent::__construct();

        // Config-Datei laden
        $this->config->load('charactertransfer');

        // Libraries & Models
        $this->load->library('administrator');
        $this->load->model('transfer_model');
        $this->load->model('admin_transfer_model');

        // Enforce admin permission for this controller
       // requirePermission("viewAdmin");

        // Wowhead-Locale anhand Userlanguage bestimmen
        $this->wowheadLocale = $this->resolveWowheadLocale();

        // Assets
        $this->css = "modules/charactertransfer/css/character.css";

        // Initialzustände
        $this->model    = [];
        $this->items    = [];
        $this->canCache = false;

        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }



    // ------------------------------------------------------------------------
    //  Caching
    // ------------------------------------------------------------------------

    /**
     * Simple file-based caching for API calls.
     *
     * @param string $key
     * @return mixed|false
     */
    private function getCache($key)
    {
        $file = $this->cache_dir . md5($key) . '.cache';

        if (file_exists($file) && (time() - filemtime($file) < 3600)) { // 1 hour
            return unserialize(file_get_contents($file));
        }

        return false;
    }

    /**
     * @param string $key
     * @param mixed  $data
     * @return void
     */
    private function setCache($key, $data)
    {
        $file = $this->cache_dir . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }
    /**
     * Ermittelt die passende Wowhead-Locale anhand der User-Sprache.
     *
     * Erwartet Userlanguage als ausgeschriebenes Wort, z.B.
     * "english", "german", "spanish", ...
     *
     * @return string
     */
    private function resolveWowheadLocale()
    {
        $map     = $this->config->item('wowhead_locale_map') ?: [];
        $default = $this->config->item('wowhead_default_locale') ?: 'en';

        // Hier kommt deine Userlanguage rein.
        // Du sagtest: "wird als english oder german oder spanish etc übergeben"
        // -> Also z.B. aus Template oder Session:
        $userLang = null;

        // Beispiel 1: aus Template-Objekt (falls vorhanden)
        if (isset($this->template->language)) {
            $userLang = $this->template->language;
        }

        // Beispiel 2: aus Session (falls ihr das so macht)
        if (!$userLang && $this->session->userdata('language')) {
            $userLang = $this->session->userdata('language');
        }

        if (!$userLang) {
            return $default;
        }

        // In Kleinbuchstaben normalisieren, Leerzeichen kappen
        $key = strtolower(trim($userLang)); // "English " -> "english"

        if (isset($map[$key])) {
            return $map[$key];
        }

        // Fallback
        return $default;
    }


    // ------------------------------------------------------------------------
    //  Wowhead Helper
    // ------------------------------------------------------------------------

    /**
     * Baut eine Wowhead-URL für Items (XML).
     *
     * @param int    $itemId
     * @param string|null $locale
     * @return string
     */
    private function buildWowheadItemXmlUrl($itemId, $locale = null)
    {
        $locale = $locale ?: $this->wowheadLocale;
        $itemId = (int)$itemId;

        // Beispiel: https://www.wowhead.com/wotlk/de/item=ITEMID&xml
        return sprintf(
            '%s/%s/item=%d&xml',
            $this->wowheadBaseUrl,
            $locale,
            $itemId
        );
    }

    /**
     * Baut eine Wowhead-URL für Items (HTML).
     *
     * @param int    $itemId
     * @param string|null $locale
     * @return string
     */
    private function buildWowheadItemHtmlUrl($itemId, $locale = null)
    {
        $locale = $locale ?: $this->wowheadLocale;
        $itemId = (int)$itemId;

        // Beispiel: https://www.wowhead.com/wotlk/de/item=ITEMID
        return sprintf(
            '%s/wotlk/%s/item=%d',
            $this->wowheadBaseUrl,
            $locale,
            $itemId
        );
    }

    /**
     * Lädt das Wowhead XML für ein Item.
     *
     * @param int         $itemId
     * @param string|null $locale
     * @return SimpleXMLElement|null
     */
    private function fetchWowheadItemXml($itemId, $locale = null)
    {
        $url = $this->buildWowheadItemXmlUrl($itemId, $locale);

        $context = stream_context_create([
            'http' => [
                'timeout'    => $this->wowheadTimeout,
                'user_agent' => $this->wowheadUserAgent,
            ],
        ]);

        $xmlString = @file_get_contents($url, false, $context);
        if ($xmlString === false) {
            error_log("fetchWowheadItemXml: Failed to fetch XML from Wowhead for item {$itemId} ({$url})");
            return null;
        }

        $xml = @simplexml_load_string($xmlString);
        if ($xml === false) {
            error_log("fetchWowheadItemXml: Failed to parse XML for item {$itemId}");
            return null;
        }

        return $xml;
    }

    // ------------------------------------------------------------------------
    //  Item-Informationen
    // ------------------------------------------------------------------------

    /**
     * Itemnamen über Wowhead holen (mit Cache).
     *
     * @param int $itemId
     * @return string
     */
    public function getItemName($itemId)
    {
        $itemId   = (int)$itemId;
        $cacheKey = 'name_' . $itemId;
        $cached   = $this->getCache($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        try {
            $xml = $this->fetchWowheadItemXml($itemId);
            if (!$xml || !isset($xml->item->name)) {
                throw new Exception('XML invalid or no name node');
            }

            $name = (string)$xml->item->name;
            if ($name === '') {
                $name = 'Unknown';
            }

            $this->setCache($cacheKey, $name);
            return $name;

        } catch (Exception $e) {
            error_log("getItemName error for item {$itemId}: " . $e->getMessage());
            $this->setCache($cacheKey, 'Unknown');
            return 'Unknown';
        }
    }

    /**
     * Erzeugt das Wowhead-HTML für ein Item (mit/ohne Gems/Enchant).
     *
     * @param array|int $item
     * @return string
     */
    public function getItemHTML($item)
    {
        // Mit vollständigen Itemdaten (inkl. Gems/Enchant)
        if (is_array($item)) {
            $itemId = (int)$item["ID"];
            $url    = $this->buildWowheadItemHtmlUrl($itemId);

            $gems   = $item["G1"] . ":" . $item["G2"] . ":" . $item["G3"];
            $enchId = $item["E"];

            return '<a data-wh-icon-size="large" href="' . $url . '" rel="item=' . $itemId . '&gems=' . $gems . '&ench=' . $enchId . '"></a>';
        }

        // Nur Item-ID
        $itemId = (int)$item;
        $url    = $this->buildWowheadItemHtmlUrl($itemId);

        return '<a data-wh-icon-size="large" href="' . $url . '" rel="item=' . $itemId . '"></a>';
    }

    /**
     * DisplayID für ein Item über Wowhead holen.
     *
     * @param int $itemId
     * @return string
     */
    public function getItemDisplayID(int $itemId): int
    {
        try {
            $xml = $this->fetchWowheadItemXml($itemId);

            if (!$xml || !isset($xml->item->icon['displayId'])) {
                throw new Exception('XML invalid or no displayId');
            }

            return (int)$xml->item->icon['displayId'];

        } catch (Exception $e) {
            error_log("getItemDisplayID error for item {$itemId}: " . $e->getMessage());
            return 0;
        }
    }


    // ------------------------------------------------------------------------
    //  Replacement-Item Logik (aufgeteilt)
    // ------------------------------------------------------------------------

    /**
     * Prüft DB und Cache auf bestehende Replacement-Zuordnung.
     *
     * @param int $itemId
     * @return int|null  Replacement-ID oder null, wenn nichts bekannt.
     */
    private function getReplacementFromDbOrCache($itemId)
    {
        $itemId = (int)$itemId;

        // Erst DB (falls Tabelle existiert)
        if ($this->db->table_exists('character_transfer_item_replacements')) {
            $query = $this->db->query(
                "SELECT replacementitemid FROM character_transfer_item_replacements WHERE itemid = ?",
                [$itemId]
            );

            if ($query->num_rows() > 0) {
                $row = $query->row_array();
                return (int)$row["replacementitemid"];
            }
        }

        // Dann Cache
        $cacheKey = 'replacement_' . $itemId;
        $cached   = $this->getCache($cacheKey);
        if ($cached !== false) {
            return (int)$cached;
        }

        return null;
    }

    /**
     * Parst aus dem Wowhead-HTML die See-Also-Liste (Listview) und
     * gibt die Itemliste zurück (roh als Array).
     *
     * @param string $html
     * @return array
     */
    private function parseSeeAlsoListviewItems($html)
    {
        $doc   = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $scriptTags    = $xpath->query('//script');
        $listviewItems = [];

        foreach ($scriptTags as $tag) {
            $jsCode = $tag->nodeValue;

            if (preg_match("/new Listview\(\{\s*template: 'item',\s*id: 'see-also',.*?\}\);/s", $jsCode, $matches)) {
                if (preg_match('/data: (\[.*?\]),\s*\}\);/s', $matches[0], $dataMatches)) {
                    $jsonData  = $dataMatches[1];
                    $dataArray = json_decode($jsonData, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($dataArray)) {
                        $listviewItems = $dataArray;
                    }
                }
                break;
            }
        }

        return $listviewItems;
    }

    /**
     * Holt ein Replacement-Item direkt von Wowhead (HTML-Parsing "see-also").
     * Wählt das Item mit dem niedrigsten Level oder fällt auf das Original zurück.
     *
     * @param int $itemId
     * @return int Replacement-ID
     */
    private function fetchReplacementFromWowhead($itemId)
    {
        $itemId = (int)$itemId;
        $url    = $this->buildWowheadItemHtmlUrl($itemId, ''); // /wotlk/item=ID (ohne locale im Pfad)?

        // Falls du den Locale-Pfad behalten willst:
        // $url = $this->buildWowheadItemHtmlUrl($itemId);

        $context = stream_context_create([
            'http' => [
                'timeout'    => max($this->wowheadTimeout, 10), // für HTML ruhig etwas länger
                'user_agent' => $this->wowheadUserAgent,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            throw new Exception("Failed to fetch HTML from Wowhead ({$url})");
        }

        $listviewItems = $this->parseSeeAlsoListviewItems($html);
        if (empty($listviewItems)) {
            // Keine Vorschläge -> Original behalten
            return $itemId;
        }

        // Wähle Item mit dem niedrigsten Level
        $lowestLevelItem = null;
        foreach ($listviewItems as $entry) {
            if (!isset($entry['id'], $entry['level'])) {
                continue;
            }

            if ($lowestLevelItem === null || $entry['level'] < $lowestLevelItem['level']) {
                $lowestLevelItem = $entry;
            }
        }

        if ($lowestLevelItem === null || !isset($lowestLevelItem['id'])) {
            return $itemId;
        }

        return (int)$lowestLevelItem['id'];
    }

    /**
     * Öffentliche API wie vorher: ermittelt ein Ersatz-Item
     * und speichert Ergebnis in DB + Cache. Methodennamen
     * aus Kompatibilitätsgründen beibehalten.
     *
     * @param int $itemid
     * @return int
     */
    public function getreplacementItem($itemid)
    {
        $itemid = (int)$itemid;

        // 1) DB / Cache
        $existing = $this->getReplacementFromDbOrCache($itemid);
        if ($existing !== null) {
            return $existing;
        }

        // 2) Wowhead anfragen
        $cacheKey = 'replacement_' . $itemid;
        try {
            $replacementId = $this->fetchReplacementFromWowhead($itemid);
        } catch (Exception $e) {
            error_log("getreplacementItem error for item {$itemid}: " . $e->getMessage());
            $replacementId = $itemid; // Fallback: Item bleibt selbst
        }

        // 3) In DB & Cache schreiben (DB optional)
        if ($this->db->table_exists('character_transfer_item_replacements')) {
            $this->db->query(
                "INSERT INTO `character_transfer_item_replacements` (`itemid`, `replacementitemid`) VALUES (?, ?)",
                [$itemid, $replacementId]
            );
        }
        $this->setCache($cacheKey, $replacementId);

        return $replacementId;
    }

    // ------------------------------------------------------------------------
    //  Charakterinfos
    // ------------------------------------------------------------------------

    /**
     * Liest den Transfer, baut charData, Items und Modeldaten zusammen.
     *
     * @param int|false $id
     * @return void
     */
    private function getInfo($id = false)
    {
        if ($id != (int)$id || (int)$id < 1) {
            echo "Invalid ID {$id} <br>";
        }

        $data = $this->transfer_model->getTransferByID($id);
        if ($data === false || empty($data[0]["chardump"])) {
            $this->getError();
            return;
        }

        // Charakter-Dump lesen
        $this->charData = $this->transfer_model->ReadCharacterDump($data[0]["chardump"]);

        // Inventar: Itemnamen hinzufügen
        if (isset($this->charData['Inventory']) && is_array($this->charData['Inventory'])) {
            foreach ($this->charData['Inventory'] as &$bagSlots) {
                if (!is_array($bagSlots)) {
                    continue;
                }

                foreach ($bagSlots as &$item) {
                    if (is_array($item) && isset($item['ID'])) {
                        $item['Name'] = $this->getItemName($item['ID']);
                    }
                }
            }
            unset($bagSlots, $item);
        }

        // Inventar aufräumen: nur Arrays zulassen
        if (isset($this->charData['Inventory']) && is_array($this->charData['Inventory'])) {
            foreach ($this->charData['Inventory'] as $bagId => &$bagSlots) {
                if (!is_array($bagSlots)) {
                    unset($this->charData['Inventory'][$bagId]);
                    continue;
                }

                foreach ($bagSlots as $slotId => $item) {
                    if (!is_array($item)) {
                        unset($bagSlots[$slotId]);
                    }
                }
            }
            unset($bagSlots);
        }

        // Ausrüstung
        $equipment = isset($this->charData["Equippment"]) ? $this->charData["Equippment"] : [];

        // Items & Modelle für Template vorbereiten
        if (is_array($equipment)) {

            foreach (self::SLOT_NAMES as $slotId => $slotName) {

                // Es gibt ein Item im Slot
                if (isset($equipment[$slotId]) && is_array($equipment[$slotId])) {
                    $equippedItem = $equipment[$slotId];
                    $equippedId   = isset($equippedItem["ID"]) ? (int)$equippedItem["ID"] : 0;

                    // Ausgerüstetes Item
                    $this->items[$slotName]["equipped"] = $this->getItemHTML($equippedItem);

                    // Ersatz-Item
                    $replacementId = $this->getreplacementItem($equippedId);
                    $this->items[$slotName]["replacement"] = $this->getItemHTML($replacementId);

                    // 3D-Modeldaten
                    if (in_array($slotId, self::ALLOWED_MODEL_SLOTS, true) && $equippedId > 0) {
                        (int)$DisplayID = $this->getItemDisplayID($equippedId);
                        if ($DisplayID > 0) {
                            switch ($slotId) { // new whead slots moved
                                case 15:
                                    $slotIdModel = 16;
                                    break;
                                case 16:
                                    $slotIdModel = 21;
                                    break;
                                case 17:
                                    $slotIdModel = 22;
                                    break;
                                    default:
                                    $slotIdModel = $slotId;


                            }
                            $this->model[] = array($slotIdModel, $DisplayID);

                        }
                    }
                } else {
                    // Kein Item: Platzhalter-Bild
                    $this->items[$slotName]["equipped"] =
                        "<img style='width:68px;height:68px' src='" .
                        $this->template->page_url .
                        "application/modules/charactertransfer/img/" . $slotName . ".png' />";
                }
            }
        }

        // Für das Template
        $this->charData['items'] = $this->items;
        $this->charData['model'] = $this->model;

    }

    // ------------------------------------------------------------------------
    //  Controller-Actions
    // ------------------------------------------------------------------------

    public function index()
    {
        // Load language for this module via custom Language library
        $language = $this->session->userdata('language') ?: 'english';
        $this->language->setLanguage($language);

        // Handle admin actions
        if ($this->input->post('option')) {
            $option = $this->input->post('option', true);
            if (preg_match('/^(approve|deny|delete)_(\d+)$/', $option, $m)) {
                $action = $m[1];
                $id     = (int)$m[2];

                if ($action === 'approve') {
                    $this->transfer_model->updateStatus($id, 1);
                    $this->session->set_flashdata('message', 'Transfer #' . $id . ' approved.');
                } elseif ($action === 'deny') {
                    $this->transfer_model->updateStatus($id, 3);
                    $this->session->set_flashdata('message', 'Transfer #' . $id . ' denied.');
                } elseif ($action === 'delete') {
                    $this->transfer_model->deleteTransfer($id);
                    $this->session->set_flashdata('message', 'Transfer #' . $id . ' deleted.');
                }

                redirect('charactertransfer/admin');
                return;
            }
        }

        $data = [];
        $data['url']          = $this->template->page_url;
        $data['transferdata'] = $this->transfer_model->getAllTransfers();
        // Build language map for template from module language file
        $langKeys = [
            'character_status','id','account_id','character','race','gender','class',
            'server','status','options','review','deny','approve','delete','no_chardump_admin'
        ];
        $data['lang'] = [];
        foreach ($langKeys as $k) {
            $data['lang'][$k] = $this->language->get($k, 'charactertransfer');
        }
        $data['message']      = $this->session->flashdata('message');
        $data['csrf_name']    = $this->security->get_csrf_token_name();
        $data['csrf_hash']    = $this->security->get_csrf_hash();

        $output = $this->template->loadPage('admin.tpl', $data);
        $this->template->view($output);
    }

    public function view($id)
    {
        $this->getInfo($id);

        if (!isset($this->charData["main"]["name"])) {
            // Fallback, falls Dump kaputt ist
            $this->getError();
            return;
        }

        $this->template->setTitle("Validate: " . $this->charData["main"]["name"]);

        $data = [
            "module"   => "default",
            "headline" => "Charactervalidation",
            "content"  => $this->template->loadPage("view.tpl", $this->charData),
        ];

        $page = $this->template->loadPage("page.tpl", $data);
        $this->template->view($page, $this->css, $this->js);
    }

    // ------------------------------------------------------------------------
    //  Fehlerseite
    // ------------------------------------------------------------------------

    /**
     * Show "character doesn't exist" error
     *
     * @param bool $get
     * @return void|string
     */
    private function getError($get = false)
    {
        $this->template->setTitle("Error");

        $data = [
            "module"   => "default",
            "headline" => "Character Does not Exists",
            "content"  => "<center style='margin:10px;font-weight:bold;'>nope</center>",
        ];

        $page = $this->template->loadPage("page.tpl", $data);

        if ($get) {
            return $page;
        }

        $this->template->view($page);
    }
}
