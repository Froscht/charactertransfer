<?php

/**
 * Note to module developers:
 *  Keeping a module specific language file like this
 *  in this external folder is not a good practise for
 *  portability - I do not advice you to do this for
 *  your own modules since they are non-default.
 *  Instead, simply put your language files in
 *  application/modules/yourModule/language/
 *  You do not need to change any code, the system
 *  will automatically look in that folder too.
 */

$lang['race'] = "Rasse";
$lang['class'] = "Klasse";
$lang['gender'] = "Geschlecht";

// Character Transfer Page
$lang['installing_chardump_addon'] = "Installation des Chardump Addons";
$lang['download_chardump_zip'] = "Laden Sie die <a href=\"download\" download>Chardump</a> Addon ZIP-Datei herunter.";
$lang['extract_zip_contents'] = "Entpacken Sie den Inhalt der ZIP-Datei.";
$lang['locate_wow_folder'] = "Suchen Sie Ihren World of Warcraft Installationsordner.";
$lang['open_interface_folder'] = "Öffnen Sie den \"Interface\" Ordner.";
$lang['create_addons_folder'] = "Erstellen Sie im \"Interface\" Ordner einen neuen Ordner namens \"AddOns\", falls er noch nicht existiert.";
$lang['copy_chardump_folder'] = "Kopieren Sie den entpackten Chardump Addon Ordner in den \"AddOns\" Ordner.";
$lang['restart_wow'] = "Starten Sie World of Warcraft neu.";
$lang['character_selection_screen'] = "Wenn Sie auf dem Charakterauswahl-Bildschirm sind, klicken Sie auf die \"AddOns\" Schaltfläche in der unteren linken Ecke.";
$lang['check_chardump_checkbox'] = "Stellen Sie sicher, dass das Kontrollkästchen neben \"Chardump\" aktiviert ist.";
$lang['login_open_bank'] = "Melden Sie sich bei Ihrem Charakter an, öffnen Sie Ihre Bank einmal und geben Sie \"/chardump\" im Chat ein, um Charakterinformationen zu dumpen.";
$lang['logout_store_file'] = "Melden Sie sich ab, damit die Datei in (PfadZuWoWOrdner)\\WTF\\Account\\IhrAccount\\SavedVariables\\chardump.lua gespeichert wird";
$lang['choose_file_upload'] = "Wählen Sie die Datei unten aus und klicken Sie auf CharakterDump hochladen";
$lang['character_status'] = "Charakterstatus";
$lang['character'] = "Charakter";
$lang['server'] = "Server";
$lang['status'] = "Status";
$lang['no_chardump_uploaded'] = "Es wurde noch kein CharacterDump hochgeladen, Transferdaten verfügbar.";
$lang['select_file_upload'] = "Datei zum Hochladen auswählen:";
$lang['upload'] = "Hochladen";

// View Page
$lang['character_tab'] = "Charakter";
$lang['name'] = "Name";
$lang['achievements_tab'] = "Erfolge";
$lang['inventory_tab'] = "Inventar";
$lang['realmlist_realm_playtime'] = "Realmlist: %s, Realm: %s, Spielzeit: %s";
$lang['talent_specialization'] = "Talent-Spezialisierung";
$lang['first'] = "Erste";
$lang['second'] = "Zweite";
$lang['main_prof'] = "Hauptberuf";
$lang['secondary_prof'] = "Nebenberuf";
$lang['money'] = "Geld";
$lang['currency'] = "Währung";
$lang['amount'] = "Menge";
$lang['check'] = "Überprüfen";
$lang['summary'] = "Zusammenfassung";
$lang['overall_progress'] = "Gesamter Fortschritt";
$lang['general'] = "Allgemein";
$lang['exploration'] = "Erkundung";
$lang['professions'] = "Berufe";
$lang['player_vs_player'] = "Spieler gegen Spieler";
$lang['quests'] = "Quests";
$lang['reputation'] = "Ruf";
$lang['world_events'] = "Weltereignisse";
$lang['dungeon_raids'] = "Dungeon & Schlachtzug";
$lang['heroic_acts'] = "Heldentaten";
$lang['classic'] = "Classic";
$lang['burning_crusade'] = "The Burning Crusade";
$lang['wrath_lich_king'] = "Wrath of the Lich King";
$lang['eastern_kingdoms'] = "Östliche Königreiche";
$lang['kalimdor'] = "Kalimdor";
$lang['outland'] = "Scherbenwelt";
$lang['northrend'] = "Nordend";
$lang['alterac_valley'] = "Alteractal";
$lang['arathi_basin'] = "Arathibecken";
$lang['eye_storm'] = "Auge des Sturms";
$lang['warsong_gulch'] = "Kriegshymnenschlucht";
$lang['wintergrasp'] = "Tausendwinter";
$lang['isle_conquest'] = "Insel der Eroberung";
$lang['strand_ancients'] = "Strand der Uralten";
$lang['cooking'] = "Kochkunst";
$lang['fishing'] = "Angeln";
$lang['first_aid'] = "Erste Hilfe";
$lang['lunar_festival'] = "Mondfest";
$lang['midsummer_fire_festival'] = "Sonnenwendfest";
$lang['brewfest'] = "Braufest";
$lang['hallow_end'] = "Schlotternächte";
$lang['harvest_festival'] = "Erntedankfest";
$lang['winter_veil'] = "Winterhauch";
$lang['darkmoon_faire'] = "Dunkelmond-Jahrmarkt";
$lang['childrens_week'] = "Kinderwoche";
$lang['noblegarden'] = "Nobelgarten";
$lang['feats_strength'] = "Taten der Stärke";
$lang['name'] = "Name";
$lang['points'] = "Punkte";
$lang['time'] = "Zeit";
$lang['count'] = "Anzahl";
$lang['achievementpoints'] = "Erfolgspunkte";

// Admin Page
$lang['fusiongen_cms_title'] = "FusionGen CMS WoW Addon Vorlage";
$lang['id'] = "ID";
$lang['account_id'] = "AccountID";
$lang['options'] = "Optionen";
$lang['review'] = "Überprüfen";
$lang['deny'] = "Ablehnen";
$lang['approve'] = "Genehmigen";
$lang['delete'] = "Löschen";
$lang['no_chardump_admin'] = "Es wurde noch kein CharacterDump hochgeladen. Keine Transferdaten verfügbar.";

// Error Page
$lang['go_back'] = "Zurück";

// Controllers
$lang['character_transfer_title'] = "Charaktertransfer";
$lang['invalid_file_upload'] = "Ungültiger Datei-Upload. Bitte stellen Sie sicher, dass die Datei eine gültige .lua-Datei unter 5MB ist.";
$lang['failed_read_file'] = "Fehler beim Lesen des Dateiinhalts oder Datei ist zu groß.";
$lang['character_already_uploaded'] = "Charakter wurde bereits zuvor hochgeladen. Bei Fragen wenden Sie sich an den Administrator.";
$lang['uploaded_file_empty'] = "Die hochgeladene Datei ist leer.";
$lang['error'] = "Fehler";
$lang['validate_title'] = "Validieren: ";
$lang['character_validation'] = "Charaktervalidierung";
$lang['character_not_exists'] = "Charakter existiert nicht";
$lang['nope'] = "Nein";

