
<script src="https://wow.zamimg.com/js/tooltips.js"></script>

<script>
    const whTooltips = {
        colorLinks: true,
        iconizeLinks: true,
        iconSize: true,
        renameLinks: false,

    };



    var talents = {json_encode($talenttree)};  // FusionGen template variable
    var achievements = {json_encode($achievements)};  // FusionGen template variable
    var professions = {json_encode($professions)};  // FusionGen template variable
    var currency = {json_encode($currency)};        // FusionGen template variable
    var money = {json_encode($main.money)};         // FusionGen template variable
    var mounts = {json_encode($mounts)};            // FusionGen template variable
    var pets = {json_encode($pets)};                // FusionGen template variable
    var reputation = {json_encode($reputation)};    // FusionGen template variable
    var playerclassindex = {json_encode($main.class)};
    var playerclass;
    switch (playerclassindex) {
        case 1: playerclass = "warrior"; break;
        case 2: playerclass = "paladin"; break;
        case 3: playerclass = "hunter"; break;
        case 4: playerclass = "rogue"; break;
        case 5: playerclass = "priest"; break;
        case 6: playerclass = "death-knight"; break;
        case 7: playerclass = "shaman"; break;
        case 8: playerclass = "mage"; break;
        case 9: playerclass = "warlock"; break;
        case 11: playerclass = "druid"; break;
        default: playerclass = "unknown"; break;
    }
</script>

<div class="container">
    <h1 class="text-center">{$main.name} {$main.racename} {$main.classname}  </h1>
    <div class="text-center">
        Realmlist: {$main.ServerIP}, Realm: {$main.ServerRealm}, Total Playtime: {$main.playtime} ,
        level {$main.level} since {$main.playtimeOnLevel}
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="character-tab" data-bs-toggle="tab" data-bs-target="#character" type="button" role="tab" aria-controls="character" aria-selected="true">{lang("character_tab", "charactertransfer")}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button" role="tab" aria-controls="achievements" aria-selected="false">{lang("achievements_tab", "charactertransfer")}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="false">{lang("inventory_tab", "charactertransfer")}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics" aria-selected="false">Statistics</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mounts-tab" data-bs-toggle="tab" data-bs-target="#mounts" type="button" role="tab" aria-controls="mounts" aria-selected="false">Mounts</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="companions-tab" data-bs-toggle="tab" data-bs-target="#companions" type="button" role="tab" aria-controls="companions" aria-selected="false">Companions</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reputation-tab" data-bs-toggle="tab" data-bs-target="#reputation" type="button" role="tab" aria-controls="reputation" aria-selected="false">Reputation</button>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content" id="myTabContent">
        <!-- CHARACTER TAB -->
        <div class="tab-pane fade show active" id="character" role="tabpanel" aria-labelledby="character-tab">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12 col-md-2">
                        <table>
                            {assign var="specificItems" value=["head", "neck", "shoulders", "back", "chest", "body", "tabard", "wrists"]}
                            {foreach $specificItems as $key}
                                {if array_key_exists($key, $items)}
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="check{$key}">
                                        </td>
                                        <td>
                                            <div class="equipped">{$items[$key].equipped}</div>
                                            {if array_key_exists($key, $items) && array_key_exists("replacement", $items[$key])}
                                                <div class="replacement" style="display:none;">{$items[$key].replacement}</div>
                                            {/if}
                                        </td>
                                    </tr>
                                {/if}
                            {/foreach}
                        </table>
                    </div>

                    <!-- MODEL -->
                    <div id="modelColumn" class="col-12 col-md-4 d-flex flex-column justify-content-center align-items-center">
                        <div id="model_3d" class="model" style="display: block; height: min(70vh, 600px); position: relative; margin: 0 auto; width: 100%; overflow: hidden;"></div>
                    </div>
                    <!-- ===== Model Viewer config ONCE ===== -->

                    <script src="{$url}charactertransfer/replica/modelviewer/live/viewer/viewer.min.js"></script>
                    <script src="{$url}application/modules/charactertransfer/js/wowmodel.min.js"></script>

                    <script>
                        window.CONTENT_PATH = "{$url}charactertransfer/replica/modelviewer/live/";

                        const character = {
                            "race": {$main.race|default:7},
                            "gender": {$main.gender|default:1},
                            "skin": 4,
                            "face": 0,
                            "hairStyle": 5,
                            "hairColor": 5,
                            "facialStyle": 5,
                            items: {if isset($model) && is_array($model)}{json_encode($model)}{else}[]{/if}
                        };

                        const model = generateModels(1, '#model_3d', character);

                        WH.debug = function(param,param2, param3) {
                            console.log(param, param2, param3);
                        }
                    </script>


                    <div class="col-12 col-md-2">
                        <table>
                            {assign var="specificItems" value=["hands", "waist", "legs", "feet", "finger1", "finger2", "trinket1", "trinket2"]}
                            {foreach $specificItems as $key}
                                {if array_key_exists($key, $items)}
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="check{$key}">
                                        </td>
                                        <td>
                                            <div class="equipped">{$items[$key].equipped}</div>
                                            {if array_key_exists("replacement", $items[$key])}
                                                <div class="replacement" style="display:none;">{$items[$key].replacement}</div>
                                            {/if}
                                        </td>
                                    </tr>
                                {/if}
                            {/foreach}
                        </table>
                    </div>

                    <div class="col-12 col-md-4">
                        <h3>{lang("talent_specialization", "charactertransfer")}</h3>
                        <div class="specialization">
                            <table>
                                <thead>
                                <tr>
                                    <th>{lang("first", "charactertransfer")}</th>
                                    <th>{lang("second", "charactertransfer")}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr id="talents"></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="divider"></div>
                        <h3>{lang("main_prof", "charactertransfer")}</h3>
                        <div id="MainProf"></div>

                        <div class="divider"></div>
                        <h3>{lang("secondary_prof", "charactertransfer")}</h3>
                        <div id="SecondaryProf"></div>

                        <div class="divider"></div>
                        <div id="money" class="money">
                            <h3>{lang("money", "charactertransfer")}</h3>
                            <div class="row">
                                <div class="col-xs-2" style="display: flex; justify-content: space-between; align-items: center; width: 60%; height: 100%; border: 2px solid green;">
                                    <span class="gold"><i class="fa-solid fa-coins"></i></span>
                                    <input type="number" name="money[gold]" min="0" step="100" max="214747" value="{$main.money.gold}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-2" style="display: flex; justify-content: space-between; align-items: center; width: 60%; height: 100%; border: 2px solid green;">
                                    <span class="silver"><i class="fa-solid fa-coins"></i></span>
                                    <input type="number" name="money[silver]" min="0" max="99" value="{$main.money.silver}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-2" style="display: flex; justify-content: space-between; align-items: center; width: 60%; height: 100%; border: 2px solid green;">
                                    <span class="copper"><i class="fa-solid fa-coins"></i></span>
                                    <input type="number" name="money[copper]" min="0" max="99" value="{$main.money.copper}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- row -->

                <div class="row">
                    <div class="col-2" style="border: 2px solid pink;"></div>

                    <div class="col-5" style="border: 2px solid red;">
                        <div class="row" style="display: flex; justify-content: space-between;">
                            <div class="row">
                                {assign var="specificItems" value=["mainhand", "offhand", "ranged"]}
                                {foreach $specificItems as $key}
                                    {if array_key_exists($key, $items)}
                                        <div class="col-4">
                                            <div class="item">
                                                <input type="checkbox" id="check{$key}">
                                                <div class="equipped">{$items[$key].equipped}</div>
                                                {if array_key_exists("replacement", $items[$key])}
                                                    <div class="replacement" style="display:none;">{$items[$key].replacement}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    </div>

                    <div class="col-5" style="border: 2px solid yellow;">
                        <div class="row">
                            {assign var="specificItems" value=["bag1", "bag2", "bag3","bag4"]}
                            {foreach $specificItems as $key}
                                {if array_key_exists($key, $items)}
                                    <div class="col-md-3">
                                        <div class="row">
                                            <input type="checkbox" id="check{$key}">
                                            <div class="equipped">{$items[$key].equipped}</div>
                                            {if array_key_exists("replacement", $items[$key])}
                                                <div class="replacement" style="display:none;">{$items[$key].replacement}</div>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
                </div> <!-- row -->

                <div class="table-responsive">
                    <table name="currency" class="table table-bordered">
                        <thead>
                        <tr>
                            <th class="sortable" data-column="0" data-order="asc" style="cursor: pointer;">Currency</th>
                            <th class="sortable" data-column="1" data-order="asc" style="cursor: pointer;">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $currency as $item}
                            <tr>
                                <td type="currency">
                                    <a class="item"
                                       data-wh-rename-link="true"
                                       data-item-id="{$item.I}"
                                       data-wh-icon-size="small"
                                       href="https://www.wowhead.com/wotlk/de/item={$item.I}"></a>
                                </td>
                                <td>
                                    <input type="number" name="item" min="0" class="form-control" value="{$item.C}">
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>

                <button id="submitButton" class="btn btn-default">{lang("check", "charactertransfer")}</button>

                <script>
                    // Currency-Submit
                    document.getElementById('submitButton').addEventListener("click", function() {
                        let items = [];

                        let currencyTable = document.querySelector('table[name="currency"]');
                        currencyTable.querySelectorAll('tbody tr').forEach(tr => {
                            let aElement = tr.querySelector('td[type="currency"] a');
                            let inputElement = tr.querySelector('input[type="number"]');

                            if (aElement && inputElement) {
                                let itemId = aElement.getAttribute('data-item-id');
                                let itemValue = Number(inputElement.value);
                                items.push({ id: itemId, count: itemValue });
                            } else {
                                console.warn('One or more elements could not be found in a row.', tr);
                            }
                        });

                        console.log(items);
                    });
                </script>

            </div> <!-- container -->
        </div> <!-- /CHARACTER TAB -->

        <!-- ACHIEVEMENTS TAB -->
        <div class="tab-pane fade" id="achievements" role="tabpanel" aria-labelledby="achievements-tab">
            <div class="container">
                <div class="row">
                    <div class="col-3">
                        <div class="accordion" id="menuAccordion">
                            <!-- Summary & General & Quests -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingQuests">
                                    <button class="accordion-button custombtn collapsed" type="button" data-target="contentSummary">
                                        {lang("summary", "charactertransfer")}
                                    </button>
                                    <button class="accordion-button custombtn collapsed" data-bs-toggle="collapse" type="button" data-target="contentGeneral">
                                        {lang("general", "charactertransfer")}
                                    </button>
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuests" aria-expanded="false" aria-controls="collapseQuests" data-target="contentQuest">
                                        {lang("quests", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseQuests" class="accordion-collapse collapse" aria-labelledby="headingQuests">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentQuestsClassic">{lang("classic", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentQuestsTBC">{lang("burning_crusade", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentQuestsWotLK">{lang("wrath_lich_king", "charactertransfer")}</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Exploration -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingExploration">
                                    <button class="accordion-button collapsed" type="button" data-target="contentExploration" data-bs-toggle="collapse" data-bs-target="#collapseExploration" aria-expanded="false" aria-controls="collapseExploration">
                                        {lang("exploration", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseExploration" class="accordion-collapse collapse" aria-labelledby="headingExploration">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentExplorationEK">Eastern Kingdoms</a>
                                        <a href="#" class="d-block" data-target="contentExplorationKalimdor">Kalimdor</a>
                                        <a href="#" class="d-block" data-target="contentExplorationOutland">Outland</a>
                                        <a href="#" class="d-block" data-target="contentExplorationNorthrend">Northrend</a>
                                    </div>
                                </div>
                            </div>

                            <!-- PVP -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPlayervsPlayer">
                                    <button class="accordion-button collapsed" type="button" data-target="contentpvp" data-bs-toggle="collapse" data-bs-target="#collapsePlayervsPlayer" aria-expanded="false" aria-controls="collapsePlayervsPlayer">
                                        {lang("player_vs_player", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapsePlayervsPlayer" class="accordion-collapse collapse" aria-labelledby="headingPlayervsPlayer">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentpvpAlteracValley">{lang("alterac_valley", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpArathiBasin">{lang("arathi_basin", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpEyeoftheStorm">{lang("eye_storm", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpWarsongGulch">{lang("warsong_gulch", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpWintergrasp">{lang("wintergrasp", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpIsleofConquest">{lang("isle_conquest", "charactertransfer")}</a>
                                        <a href="#" class="d-block" data-target="contentpvpStrandoftheAncients">{lang("strand_ancients", "charactertransfer")}</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Dungeon and Raids -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingDungeonAndRaids">
                                    <button class="accordion-button collapsed" data-target="contentdar" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDungeonAndRaids" aria-expanded="false" aria-controls="collapseDungeonAndRaids">
                                        {lang("dungeon_raids", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseDungeonAndRaids" class="accordion-collapse collapse" aria-labelledby="headingDungeonAndRaids">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentdarclassic">Classic</a>
                                        <a href="#" class="d-block" data-target="contentdarburning-crusade">The Burning Crusade</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-dungeon">Lich King Dungeon</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-heroic">Lich King Heroic</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-10-raid">Lich King 10-Player Raid</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-25-raid">Lich King 25-Player Raid</a>
                                        <a href="#" class="d-block" data-target="contentdarulduar-10">Secrets of Ulduar 10</a>
                                        <a href="#" class="d-block" data-target="contentdarulduar-25">Secrets of Ulduar 25</a>
                                        <a href="#" class="d-block" data-target="contentdarcrusade-10">Call of the Crusade 10</a>
                                        <a href="#" class="d-block" data-target="contentdarcrusade-25">Call of the Crusade 25</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-10">Fall of the Lich King 10</a>
                                        <a href="#" class="d-block" data-target="contentdarlich-king-25">Fall of the Lich King 25</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Professions -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingProfessions">
                                    <button class="accordion-button collapsed" data-target="contentprofessions" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfessions" aria-expanded="false" aria-controls="collapseProfessions">
                                        {lang("professions", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseProfessions" class="accordion-collapse collapse" aria-labelledby="headingProfessions">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentprofessionscooking">Cooking</a>
                                        <a href="#" class="d-block" data-target="contentprofessionsfishing">Fishing</a>
                                        <a href="#" class="d-block" data-target="contentprofessionsfirstaid">First Aid</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Reputation -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingReputation">
                                    <button class="accordion-button collapsed" data-target="contentReputation" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReputation" aria-expanded="false" aria-controls="collapseReputation">
                                        {lang("reputation", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseReputation" class="accordion-collapse collapse" aria-labelledby="headingReputation">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentReputationclassic">Classic</a>
                                        <a href="#" class="d-block" data-target="contentReputationtbc">The Burning Crusade</a>
                                        <a href="#" class="d-block" data-target="contentReputationwotlk">Wrath of the Lich King</a>
                                    </div>
                                </div>
                            </div>

                            <!-- World Events -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingWorldEvents">
                                    <button class="accordion-button collapsed" data-target="contentWorldEvents" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWorldEvents" aria-expanded="false" aria-controls="collapseWorldEvents">
                                        {lang("world_events", "charactertransfer")}
                                    </button>
                                </h2>
                                <div id="collapseWorldEvents" class="accordion-collapse collapse" aria-labelledby="headingWorldEvents">
                                    <div class="accordion-body">
                                        <a href="#" class="d-block" data-target="contentWorldEventslunar-festival">Lunar Festival</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsmidsummer-fire-festival">Midsummer Fire Festival</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsbrewfest">Brewfest</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventshallow-end">Hallow End</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsharvest-festival">Harvest Festival</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventswinter-veil">Winter Veil</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsmidsummer-fire-festival">Midsummer Fire Festival</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsthe-darkmoon-faire">The Darkmoon Faire</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventschildrensweek">Children's Week</a>
                                        <a href="#" class="d-block" data-target="contentWorldEventsnobelgarden">Noblegarden</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Feats of Strength -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFeatsofStrength">
                                    <button class="accordion-button collapsed" data-target="contentFeatsofStrength" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFeatsofStrength" aria-expanded="false" aria-controls="collapseFeatsofStrength">
                                        {lang("feats_strength", "charactertransfer")}
                                    </button>
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div id="AchievementContent" class="col-9">
                        <!-- SUMMARY -->
                        <div id="contentSummary" class="content-item">
                            <div class="container">
                            <h1>{lang("achievementpoints", "charactertransfer")}: {$achievementspoints}</h1>

                                <label class="white-text" for="progressOverall">{lang("overall_progress", "charactertransfer")}</label>
                                <div class="progress position-relative" id="progressOverall">
                                    <div class="progress-bar" data-cat="overall" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                    <small class="justify-content-center d-flex position-absolute w-100"></small>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <label class="white-text" for="progressGeneral">{lang("general", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressGeneral">
                                            <div class="progress-bar" data-cat="92" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressExploration">{lang("exploration", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressExploration">
                                            <div class="progress-bar" data-cat="97" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressProf">{lang("professions", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressProf">
                                            <div class="progress-bar" data-cat="169" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <label class="white-text" for="progressPvP">{lang("player_vs_player", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressPvP">
                                            <div class="progress-bar" data-cat="95" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressQuests">Quests</label>
                                        <div class="progress position-relative" id="progressQuests">
                                            <div class="progress-bar" data-cat="96" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressReputation">{lang("reputation", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressReputation">
                                            <div class="progress-bar" data-cat="201" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-4">
                                        <label class="white-text" for="progressWorldEvents">{lang("world_events", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressWorldEvents">
                                            <div class="progress-bar" data-cat="155" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressDungeonRaids">{lang("dungeon_raids", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressDungeonRaids">
                                            <div class="progress-bar" data-cat="168" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="white-text" for="progressHeroicActs">{lang("heroic_acts", "charactertransfer")}</label>
                                        <div class="progress position-relative" id="progressHeroicActs">
                                            <div class="progress-bar" data-cat="81" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0"></div>
                                            <small class="justify-content-center d-flex position-absolute w-100"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Individual category containers -->
                        <div id="contentGeneral" data-category="92" class="content-item">Content for General</div>
                        <div id="contentQuest" data-category="96" class="content-item">Content for content Quest</div>
                        <div id="contentQuestsClassic" data-category="14861" class="content-item">Content for Quests - Classic</div>
                        <div id="contentQuestsTBC" data-category="14862" class="content-item">Content for Quests - TBC</div>
                        <div id="contentQuestsWotLK" data-category="14863" class="content-item">Content for Quests - WOTLK</div>

                        <div id="contentExploration" data-category="97" class="content-item">Content for Exploration</div>
                        <div id="contentExplorationEK" data-category="14777" class="content-item">Content for Exploration - Eastern Kingdoms</div>
                        <div id="contentExplorationKalimdor" data-category="14778" class="content-item">Content for Exploration - Kalimdor</div>
                        <div id="contentExplorationOutland" data-category="14779" class="content-item">Content for Exploration - Outland</div>
                        <div id="contentExplorationNorthrend" data-category="14780" class="content-item">Content for Exploration - Northrend</div>

                        <div id="contentpvp" data-category="95" class="content-item">Content for Player vs Player</div>
                        <div id="contentpvpAlteracValley" data-category="14801" class="content-item">Content for Player vs Player - Alterac Valley</div>
                        <div id="contentpvpArathiBasin" data-category="14802" class="content-item">Content for Player vs Player - Arathi Basin</div>
                        <div id="contentpvpEyeoftheStorm" data-category="14803" class="content-item">Content for Player vs Player - Eye of the Storm</div>
                        <div id="contentpvpWarsongGulch" data-category="14804" class="content-item">Content for Player vs Player - Warsong Gulch</div>
                        <div id="contentpvpWintergrasp" data-category="14901" class="content-item">Content for Player vs Player - Wintergrasp</div>
                        <div id="contentpvpIsleofConquest" data-category="15003" class="content-item">Content for Player vs Player - Isle of Conquest</div>

                        <div id="contentdar" data-category="168" class="content-item">Content for Dungeon and Raids</div>
                        <div id="contentdarclassic" data-category="14808" class="content-item">Content for Dungeon and Raids - Classic</div>
                        <div id="contentdarburning-crusade" data-category="14805" class="content-item">Content for Dungeon and Raids - The Burning Crusade</div>
                        <div id="contentdarlich-king-dungeon" data-category="14806" class="content-item">Content for Dungeon and Raids - Lich King Dungeon</div>
                        <div id="contentdarlich-king-heroic" data-category="14921" class="content-item">Content for Dungeon and Raids - Lich King Heroic</div>
                        <div id="contentdarlich-king-10-raid" data-category="14922" class="content-item">Content for Dungeon and Raids - Lich King 10-Player Raid</div>
                        <div id="contentdarlich-king-25-raid" data-category="14923" class="content-item">Content for Dungeon and Raids - Lich King 25-Player Raid</div>
                        <div id="contentdarulduar-10" data-category="14961" class="content-item">Content for Dungeon and Raids - Secrets of Ulduar 10</div>
                        <div id="contentdarulduar-25" data-category="14962" class="content-item">Content for Dungeon and Raids - Secrets of Ulduar 25</div>
                        <div id="contentdarcrusade-10" data-category="15001" class="content-item">Content for Dungeon and Raids - Call of the Crusade 10</div>
                        <div id="contentdarcrusade-25" data-category="15002" class="content-item">Content for Dungeon and Raids - Call of the Crusade 25</div>
                        <div id="contentdarlich-king-10" data-category="15041" class="content-item">Content for Dungeon and Raids - Fall of the Lich King 10</div>
                        <div id="contentdarlich-king-25" data-category="15042" class="content-item">Content for Dungeon and Raids - Fall of the Lich King 25</div>

                        <div id="contentprofessions" data-category="169" class="content-item">Content for Professions</div>
                        <div id="contentprofessionscooking" data-category="170" class="content-item">Content for Cooking</div>
                        <div id="contentprofessionsfishing" data-category="171" class="content-item">Content for Fishing</div>
                        <div id="contentprofessionsfirstaid" data-category="172" class="content-item">Content for First Aid</div>

                        <div id="contentReputation" data-category="201" class="content-item">Content for Reputation</div>
                        <div id="contentReputationclassic" data-category="14864" class="content-item">Content for Reputation - classic</div>
                        <div id="contentReputationtbc" data-category="14865" class="content-item">Content for Reputation - tbc</div>
                        <div id="contentReputationwotlk" data-category="14866" class="content-item">Content for Reputation - wotlk</div>

                        <div id="contentWorldEvents" data-category="155" class="content-item">Content for World Events</div>
                        <div id="contentWorldEventslunar-festival" data-category="160" class="content-item">Content for World Events - Lunar Festival</div>
                        <div id="contentWorldEventsmidsummerfirefestival2" data-category="187" class="content-item">Content for World Events - Midsummer Fire Festival</div>
                        <div id="contentWorldEventsbrewfest" data-category="159" class="content-item">Content for World Events - Brewfest</div>
                        <div id="contentWorldEventshallow-end" data-category="163" class="content-item">Content for World Events - Hallow End</div>
                        <div id="contentWorldEventsharvest-festival" data-category="161" class="content-item">Content for World Events - Harvest Festival</div>
                        <div id="contentWorldEventswinter-veil" data-category="162" class="content-item">Content for World Events - Winter Veil</div>
                        <div id="contentWorldEventsmidsummer-fire-festival" data-category="158" class="content-item">Content for World Events - Midsummer Fire Festival</div>
                        <div id="contentWorldEventsthe-darkmoon-faire" data-category="14981" class="content-item">Content for World Events - The Darkmoon Faire</div>
                        <div id="contentWorldEventschildrensweek" data-category="156" class="content-item">Content for World Events - Children's Week</div>
                        <div id="contentWorldEventsnobelgarden" data-category="14941" class="content-item">Content for World Events - Noblegarden</div>

                        <div id="contentFeatsofStrength" data-category="81" class="content-item">Content for Feats of Strength</div>
                    </div>
                </div>
            </div>

            <script>
                let filteredAchievements = achievements.filter(achievement => achievement.category !== 81);
                let totalAchievements = filteredAchievements.length;
                let completedAchievements = filteredAchievements.filter(achievement => achievement.completed === 1).length;
                let progressOverall = (completedAchievements / totalAchievements) * 100;

                $('#progressOverall .progress-bar').css('width', progressOverall + '%').attr('aria-valuenow', progressOverall);
                $('#progressOverall small').text(completedAchievements + ' / ' + totalAchievements);

                let categoryCounts = achievements.reduce((acc, achievement) => {
                    let category =
                        [14861, 14862, 14863].includes(achievement.category) ? 96 :
                            [165, 14801, 14802, 14803, 14804, 14881, 14901, 15003].includes(achievement.category) ? 95 :
                                [14777, 14778, 14779, 14780].includes(achievement.category) ? 97 :
                                    [14808, 14805, 14806, 14921, 14922, 14923, 14961, 14962, 15001, 15002, 15041, 15042].includes(achievement.category) ? 168 :
                                        [170, 171, 172].includes(achievement.category) ? 169 :
                                            [14864, 14865, 14866].includes(achievement.category) ? 201 :
                                                [160, 187, 159, 163, 161, 162, 158, 14981, 156, 14941].includes(achievement.category) ? 155 :
                                                    achievement.category;

                    if (!acc[category]) {
                        acc[category] = { total: 0, completed: 0 };
                    }

                    acc[category].total++;

                    if (achievement.completed === 1) {
                        acc[category].completed++;
                    }

                    return acc;
                }, {});

                $('#contentSummary .progress-bar').each(function() {
                    let category = $(this).data('cat');

                    if (categoryCounts[category]) {
                        let progress = (categoryCounts[category].completed / categoryCounts[category].total) * 100;
                        $(this).css('width', progress + '%').attr('aria-valuenow', progress);
                        $(this).parent().find('small').text(categoryCounts[category].completed + ' / ' + categoryCounts[category].total);
                    }
                });

                let divs = Array.from(document.querySelectorAll("#AchievementContent div[data-category]")).filter(div => !isNaN(div.getAttribute('data-category')));
                var userLang = navigator.language || navigator.userLanguage;

                divs.forEach((div) => {
                    let categoryId = div.getAttribute("data-category");

                    let achievementsWithCategory = achievements.filter((achievement) => achievement.category === Number(categoryId) && achievement.completed === 1);
                    achievementsWithCategory.sort((a, b) => b.points - a.points);

                    let contentHTMLtable = '<div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Points</th><th>Time</th></tr></thead><tbody>';

                    achievementsWithCategory.forEach((achievement) => {
                        const date = new Date(achievement.time * 1000);
                        const dateStr = date.toLocaleDateString();

                        contentHTMLtable +=
                            '<tr><td><a data-wh-rename-link="true" href="https://www.wowhead.com/wotlk/' + userLang + "/achievement=" + achievement.id + '&who={$main.name}&when=' + achievement.time + '"></a></td><td>' + achievement.points + "</td><td> " + dateStr + "</td></tr>";
                    });

                    contentHTMLtable += "</tbody></table></div>";
                    div.innerHTML = contentHTMLtable;
                });


                function showContent(targetId) {
                    document.querySelectorAll(".content-item").forEach((content) => {
                        content.style.display = "none";
                    });

                    const contentToShow = document.getElementById(targetId);
                    if (contentToShow) {
                        contentToShow.style.display = "block";
                    }
                }

                document.getElementById('achievements-tab').addEventListener('shown.bs.tab', function (e) {
                    showContent('contentSummary');
                });

                document.querySelectorAll(".col-3 a, .accordion-body a, button[data-target]").forEach((item) => {
                    item.addEventListener("click", function(e) {
                        e.preventDefault();
                        const targetId = this.getAttribute("data-target");
                        showContent(targetId);
                    });
                });
            </script>
        </div> <!-- /ACHIEVEMENTS TAB -->

        <!-- INVENTORY TAB -->
        <div class="tab-pane fade" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
            <!-- Content for Inventory -->
            <div id="inventoryContent" class="tab-content">
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        border: 1px solid black;
                        padding: 5px;
                        text-align: left;
                    }
                </style>

                <table id="dataTable">
                    <thead>
                    <tr>
                        <th class="sortable" data-column="0" data-order="asc" style="cursor: pointer;">{lang("name", "charactertransfer")}</th>
                        <th class="sortable" data-column="1" data-order="asc" style="cursor: pointer;">{lang("count", "charactertransfer")}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $Inventory as $slot}
                        {foreach $slot as $item}
                            <tr>
                                <td>
                                    <a class="item"
                                       data-wh-rename-link="true"
                                       data-wh-icon-size="small"
                                       data-item-id="{$item.ID}"
                                       href="https://www.wowhead.com/wotlk/de/item={$item.ID}"></a>
                                </td>
                                <td>
                                    <input type="number" name="item" min="0" class="form-control" value="{$item.Count}">
                                </td>
                            </tr>
                        {/foreach}
                    {/foreach}
                    </tbody>
                </table>

                <script>

                    professions.main.forEach(function(item) {
                        var html = '<div class="stub">' + item.Link + ' ' + item.Current + ' / ' + item.Max + '</div>';
                        document.getElementById("MainProf").innerHTML += html;
                    });

                    professions.secondary.forEach(function(item) {
                        var html = '<div class="stub">' + item.Link + ' ' + item.Current + ' / ' + item.Max + '</div>';
                        document.getElementById("SecondaryProf").innerHTML += html;
                    });

                    professions.other.forEach(function(item) {
                        var html = '<div class="stub">' + item.Link + ' ' + item.Current + ' / ' + item.Max + '</div>';
                        document.getElementById("SecondaryProf").innerHTML += html;
                    });

                    if (talents[1][1] === 0 && talents[1][2] === 0 && talents[1][3] === 0) {
                        td1 = 'None';
                    } else {
                        td1 = '<div class="icon"><a href="https://www.wowhead.com/wotlk/de/talent-calc/embed/' + playerclass + '/' +
                            talents[1].link + '"><img src="https://wow.zamimg.com/images/wow/icons/large/' + talents[1].highesttab
                                .toLowerCase() + '.jpg" width="25" height="25">' + talents[1][1] + ' / ' + talents[1][2] + '  / ' +
                            talents[1][3] + ' </a></div>';
                    }

                    if (talents[2][1] === 0 && talents[2][2] === 0 && talents[2][3] === 0) {
                        td2 = 'None';
                    } else {
                        td2 = '<div class="icon"><a href="https://www.wowhead.com/wotlk/de/talent-calc/embed/' + playerclass + '/' +
                            talents[2].link + '"><img src="https://wow.zamimg.com/images/wow/icons/large/' + talents[2]
                                .highestTabTwo.toLowerCase() + '.jpg" width="25" height="25">' + talents[2][1] + ' / ' + talents[2][2] +
                            '  / ' + talents[2][3] + ' </a></div>';
                    }

                    document.getElementById("talents").innerHTML += '<td>' + td1 + ' </td><td>' + td2 + ' </td>';

                    $(document).ready(function() {

                        // checkbox swap equipped/replacement
                        $('#myTabContent input[type="checkbox"]').click(function() {
                            if ($(this).is(':checked')) {
                                if ($(this).closest('td').next('td').html() !== null && $(this).closest('td').next('td').html() !== undefined) {
                                    $(this).closest('tr').find('.equipped').hide();
                                    $(this).closest('tr').find('.replacement').show();
                                } else {
                                    $(this).nextAll('div.equipped').first().hide();
                                    $(this).nextAll('div.replacement').first().show();
                                }
                            } else {
                                if ($(this).closest('td').next('td').html() !== null && $(this).closest('td').next('td').html() !== undefined) {
                                    $(this).closest('tr').find('.equipped').show();
                                    $(this).closest('tr').find('.replacement').hide();
                                } else {
                                    $(this).nextAll('div.equipped').first().show();
                                    $(this).nextAll('div.replacement').first().hide();
                                }
                            }
                        });

                        // Sortable tables
                        $('.sortable').click(function() {
                            var table  = $(this).closest('table');
                            var column = $(this).data('column');
                            var order  = $(this).data('order');
                            var rows   = table.find('tbody tr').toArray();

                            rows.sort(function(a, b) {
                                var aVal = $(a).find('td').eq(column).text().trim();
                                var bVal = $(b).find('td').eq(column).text().trim();

                                // Currency table: sort by item-id or amount
                                if (table.attr('name') === 'currency') {
                                    if (column === 0) { // item id
                                        aVal = $(a).find('td').eq(column).find('a').attr('data-item-id') || aVal;
                                        bVal = $(b).find('td').eq(column).find('a').attr('data-item-id') || bVal;
                                    } else if (column === 1) { // amount
                                        aVal = parseInt($(a).find('td').eq(column).find('input').val()) || 0;
                                        bVal = parseInt($(b).find('td').eq(column).find('input').val()) || 0;
                                    }
                                }

                                // Inventory table: sort by Wowhead *name* (link text), fallback to item-id
                                if (table.attr('id') === 'dataTable') {
                                    if (column === 0) { // Name column
                                        var aLink = $(a).find('td').eq(column).find('a');
                                        var bLink = $(b).find('td').eq(column).find('a');

                                        var aText = (aLink.text().trim() || '').toLowerCase();
                                        var bText = (bLink.text().trim() || '').toLowerCase();

                                        // If Wowhead hasn't filled the name yet, fallback to data-item-id
                                        if (!aText) {
                                            aText = (aLink.attr('data-item-id') || '').toLowerCase();
                                        }
                                        if (!bText) {
                                            bText = (bLink.attr('data-item-id') || '').toLowerCase();
                                        }

                                        aVal = aText;
                                        bVal = bText;
                                    } else if (column === 1) { // Count column
                                        aVal = parseInt($(a).find('td').eq(column).find('input').val()) || 0;
                                        bVal = parseInt($(b).find('td').eq(column).find('input').val()) || 0;
                                    }
                                }

                                if (order === 'asc') {
                                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                                } else {
                                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                                }
                            });

                            // Toggle order & re-append rows
                            $(this).data('order', order === 'asc' ? 'desc' : 'asc');
                            table.find('tbody').empty().append(rows);
                        });
                    });
                </script>
            </div>
        </div> <!-- /INVENTORY TAB -->

        <!-- STATISTICS TAB -->
        <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
            <div class="container">
                <h3>Character Statistics</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Statistic</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if isset($main.stats) && is_array($main.stats)}
                                {foreach $main.stats as $statName => $statValue}
                                    <tr>
                                        <td>{$statName}</td>
                                        <td>{$statValue}</td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="2">No statistics available</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- /STATISTICS TAB -->

        <!-- MOUNTS TAB -->
        <div class="tab-pane fade" id="mounts" role="tabpanel" aria-labelledby="mounts-tab">
            <div class="container">
                <h3>Mounts</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Mount</th>
                                <th>Spell ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if isset($mounts) && is_array($mounts)}
                                {foreach $mounts as $mount}
                                    <tr>
                                        <td>
                                            <a data-wh-rename-link="true" data-wh-icon-size="small" href="https://www.wowhead.com/wotlk/de/spell={$mount.spell}"></a>
                                        </td>
                                        <td>{$mount.spell}</td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="2">No mounts available</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- /MOUNTS TAB -->

        <!-- COMPANIONS TAB -->
        <div class="tab-pane fade" id="companions" role="tabpanel" aria-labelledby="companions-tab">
            <div class="container">
                <h3>Companions</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Companion</th>
                                <th>Creature ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if isset($pets) && is_array($pets)}
                                {foreach $pets as $pet}
                                    <tr>
                                        <td>
                                            <a data-wh-rename-link="true" data-wh-icon-size="small" href="https://www.wowhead.com/wotlk/de/npc={$pet.creature}"></a>
                                        </td>
                                        <td>{$pet.creature}</td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="2">No companions available</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- /COMPANIONS TAB -->

        <!-- REPUTATION TAB -->
        <div class="tab-pane fade" id="reputation" role="tabpanel" aria-labelledby="reputation-tab">
            <div class="container">
                <h3>Reputation</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Faction</th>
                                <th>Standing</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if isset($reputation) && is_array($reputation)}
                                {foreach $reputation as $rep}
                                    <tr>

                                        <td><a data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/faction={$rep.ID}">Faction name from db later</a>
                                        </td>
                                        <td>XX</td>
                                        <td>{$rep.Value}</td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="3">No reputation data available</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- /REPUTATION TAB -->
    </div> <!-- /tab-content -->
</div>
