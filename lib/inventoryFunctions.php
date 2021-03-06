<?php
require_once "database_class.php";
require_once "auth.php";

class inventoryFunctions extends DataBase{

    private $db;
    private $user;

    public function __construct() {
        parent::__construct();
        $this->db = $this;
        session_start();
        $this->user = $this->db->selectFromTables(array("users", "user_resources", "user_settings", "user_statistic"), "id", $_SESSION["id"]);
        $this->inventory = $this->db->getAllOnField("user_inventory", "id", $this->user["id"], "", "");
        $this->inventoryPotions = $this->db->getAllOnField("user_inventory_potions", "id", $this->user["id"], "", "");
    }

    public function query($query){
        if (!$result = $this->mysqli->query($query)) {
            return $query." Ошибка: ".$this->mysqli->error;
        }
        return $result;
    }

    public function putOff($slot){
        echo memory_get_usage()/1024 ." - начало";
        //Снятие через надетые вещи
        if(is_string($slot)){
            if($slot == "helmet" or $slot == "armor" or $slot == "bracers" or $slot == "leggings" or $slot == "primaryWeapon" or $slot == "secondaryWeapon"){
                $this->db->setFieldOnID("users", $this->user["id"], $slot, 0);
                exit;
            }
        }
        if($slot > count($this->inventory)) exit;
        //Снятие через инвентарь
        $invItem = unserialize($this->inventory["slot$slot"]);
        if($this->user["armor"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "armor", "0");
        if($this->user["helmet"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "helmet", "0");
        if($this->user["leggings"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "leggings", "0");
        if($this->user["bracers"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "bracers", "0");

        if($this->user["secondaryWeapon"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "secondaryWeapon", "0");
        if($this->user["primaryWeapon"] == $invItem["hash"])	$this->db->putThingOn($this->user["id"], "primaryWeapon", "0");
    }

    public function wantDelete($slot, $type){
        if(!$this->valid->check_sql($slot)) exit;
        if($type == 1){
            $invItem = unserialize($this->inventory["slot$slot"]);
            if($invItem["id"] < 500) $table_name = "weapon";
            if($invItem["id"] > 500 and $invItem["id"] < 1000) $table_name = "armor";
            $item = $this->db->getElementOnID($table_name, $invItem["id"]);
            $sr["text"] = "Вы действительно хотите удалить ".$item["name"]." ?";
        }
        if($type == 2){
            for($i = 1; $i <= 5; $i++){
                if($this->inventoryPotions["slot$i"] == $slot)
                    break;
            }
            $item = $this->db->getAllOnField("something", "image", $slot, "", "");
            $sr["text"] = "Вы действительно хотите удалить ".$item["title"]." ?";
        }
        $sr["onclick"] = "shureDelete('$slot', $type)";
        $sr["textDelete"] = "Удалить";
        $text = $this->getReplaceTemplate($sr, "deleteAlert");
        echo $text;
    }

    public function deleteThis($slot, $type){
        if($type == 1){
            $invItem = unserialize($this->inventory["slot$slot"]);
            $this->db->setFieldOnID("user_inventory", $this->user["id"], "slot$slot", 0);
            if($this->user["helmet"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "helmet", 0);
            if($this->user["armor"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "armor", 0);
            if($this->user["leggings"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "leggings", 0);
            if($this->user["bracers"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "bracers", 0);
            if($this->user["primaryWeapon"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "primaryWeapon", 0);
            if($this->user["secondaryWeapon"] == $invItem["hash"])
                $this->db->setFieldOnID("users", $this->user["id"], "secondaryWeapon", 0);
        }
        if($type == 2){
            for($i = 1; $i <= 5; $i++){
                if($this->inventoryPotions["slot$i"] == $slot)
                    break;
            }
            $this->db->setFieldOnID("user_inventory_potions", $this->user["id"], "slot$i", 0);
            $this->db->setFieldOnID("user_inventory_potions", $this->user["id"], "slot$i"."_count", 0);
        }
    }

    public function put($slot){
        $invItem = unserialize($this->inventory["slot$slot"]);
        if($invItem["id"] < 500){
            $weaponInformation = $this->db->getAllOnField("weapon", "id", $invItem["id"], "", "");
            if($this->user["lvl"] >= $weaponInformation["requiredlvl"]){
                $weapon = $this->db->getElementOnID("weapon",$invItem["id"]);

                $primaryWeapon = $this->db->getThingByHash($this->user["primaryWeapon"], $this->inventory);
                if($primaryWeapon["type"] == 2){
                    $this->db->putThingOn($this->user["id"], "primaryWeapon", $invItem["hash"]);
                    die("OK");
                }

                if($this->user["primaryWeapon"] == "0" ){
                    $this->db->putThingOn($this->user["id"], "primaryWeapon", $invItem["hash"]);
                    if($weapon["type"] == 2)
                        $this->db->putThingOn($this->user["id"], "secondaryWeapon", 0);
                    die("OK");
                }
                if($this->user["secondaryWeapon"] == "0" ){
                    $this->db->putThingOn($this->user["id"], "secondaryWeapon", $invItem["hash"]);
                }
                else{
                    $this->db->putThingOn($this->user["id"], "primaryWeapon", $invItem["hash"]);
                }

                die("OK");
            }
            else die("Нужен ".$weaponInformation["requiredlvl"]." уровень!");
        }

        if($invItem["id"] > 500 and $invItem["id"] < 1000){
            $armorInformation = $this->db->getAllOnField("armor", "id", $invItem["id"], "", "");
            if($this->user["lvl"] >= $armorInformation["requiredlvl"]){

                if($armorInformation["thing"] == 2){
                    $this->db->putThingOn($this->user["id"], "armor", $invItem["hash"]);
                    die("OK");
                }

                if($armorInformation["thing"] == 3){
                    $this->db->putThingOn($this->user["id"], "helmet", $invItem["hash"]);
                    die("OK");
                }

                if($armorInformation["thing"] == 4){
                    $this->db->putThingOn($this->user["id"], "leggings", $invItem["hash"]);
                    die("OK");
                }

                if($armorInformation["thing"] == 5){
                    $this->db->putThingOn($this->user["id"], "bracers", $invItem["hash"]);
                    die("OK");
                }

                if($armorInformation["thing"] == 6){
                    $weapon = $this->db->getThingByHash($this->user["primaryWeapon"], $this->inventory);
                    if($weapon["type"] == 2)
                        die("Нельзя надеть щит с двуручкой. ");
                    $this->db->putThingOn($this->user["id"], "secondaryWeapon", $invItem["hash"]);
                    die("OK");
                }
            }
            else die("Нужен ".$armorInformation["requiredlvl"]." уровень!");
        }
    }

    public function showDetailsSmith($slot){
        $invItem = unserialize($this->inventory["slot$slot"]);
        if($invItem["id"] < 500){
            $weapon = $this->db->getElementOnID("weapon", $invItem["id"]);
            if($weapon["type"] == 1)	$typeName="Одноручное";
            if($weapon["type"] == 2)	$typeName="Двуручное";
            if($weapon["type"] == 3)	$typeName="Древковое";
            if($weapon["typedamage"] == 1)	$typedamageName="Колющее";
            if($weapon["typedamage"] == 2)	$typedamageName="Режущее";
            if($weapon["typedamage"] == 3)	$typedamageName="Дробящее";
            $sr["type"] = $typeName;
            $sr["typedamage"] = $typedamageName;
            $sr["lvl"] = $weapon["requiredlvl"];
            $sr["damage"] = $weapon["damage"];
            $sr["crit"] = $weapon["crit"];
            if($weapon["bonusstr"]) $stats = "<tr><td> Сила </td><td> {$weapon['bonusstr']} </td></tr>";
            if($weapon["bonusdef"]) $stats .= "<tr><td> Защита </td><td> {$weapon['bonusdef']} </td></tr>";
            if($weapon["bonusag"]) $stats .= "<tr><td> Ловкость </td><td> {$weapon['bonusag']} </td></tr>";
            if($weapon["bonusph"]) $stats .= "<tr><td> Телосложение </td><td> {$weapon['bonusph']} </td></tr>";
            if($weapon["bonusms"]) $stats .= "<tr><td> Мастерство </td><td> {$weapon['bonusms']} </td></tr>";
            $sr["stats"] = $stats;
            $text = $this->getReplaceTemplate($sr, "informationSmithWeapon");
            echo $text;
        }
        if($invItem["id"] > 500 and $invItem["id"] < 1000){
            $armor = $this->db->getElementOnID("armor", $invItem["id"]);
            if($armor["thing"] == 2)	$typeThing="Броня";
            if($armor["thing"] == 3)	$typeThing="Шлем";
            if($armor["thing"] == 4)	$typeThing="Поножи";
            if($armor["thing"] == 5)	$typeThing="Наручи";
            if($armor["thing"] == 6)	$typeThing="Щит";
            if($armor["typeDefence"] == 1)	$typeName="Лёгкая";
            if($armor["typeDefence"] == 2)	$typeName="Средняя";
            if($armor["typeDefence"] == 3)	$typeName="Тяжелая";
            $sr["typeThing"] = $typeThing;
            $sr["type"] = $typeName;
            $sr["lvl"] = $armor["requiredlvl"];
            $sr["defence"] = $armor["defence"];
            if($armor["bonusstr"]) $stats .= "<tr><td> Сила </td><td> {$armor['bonusstr']} </td></tr>";
            if($armor["bonusdef"]) $stats .= "<tr><td> Защита </td><td> {$armor['bonusdef']} </td></tr>";
            if($armor["bonusag"]) $stats .= "<tr><td> Ловкость </td><td> {$armor['bonusag']} </td></tr>";
            if($armor["bonusph"]) $stats .= "<tr><td> Телосложение </td><td> {$armor['bonusph']} </td></tr>";
            if($armor["bonusms"]) $stats .= "<tr><td> Мастерство </td><td> {$armor['bonusms']} </td></tr>";
            $sr["stats"] = $stats;
            $text = $this->getReplaceTemplate($sr, "informationSmithArmor");
            echo $text;
        }
    }

    public function getMenuSmith($slot){
        $invItem = unserialize($this->inventory["slot$slot"]);
        if($invItem["id"] < 500){
            $weapon = $this->db->getElementOnID("weapon", $invItem["id"]);
            $modificator = 1;
            for($i = 1; $i <= $invItem['damage']; $i++)
                $modificator += 0.05;
            $damage = round($weapon['damage'] * $modificator, 2);
            $modificator = 1;
            for($i = 1; $i <= $invItem['crit']; $i++)
                $modificator += 0.05;
            $crit = round($weapon['crit'] * $modificator, 2);
            $text = "<div id='putSmith'> <img src=\"images/cloth/{$weapon['id']}.png\" class='inventoryItems'> </div>
				<div id='allPowerUpsSmith'>
				<table class='upSmithTable'>
					<tr><td>Урон</td>
					<td><a href='#' onclick='upCharsWeapon(\"down\", \"damage\",{$weapon['price']},{$weapon['damage']},{$invItem['damage']},{$weapon['crit']},{$invItem['crit']})'> < </a></td>
					<td ><div id='damageLvl'> {$invItem['damage']} </div></td>
					<td><a href='#' onclick='upCharsWeapon(\"up\", \"damage\",{$weapon['price']},{$weapon['damage']},{$invItem['damage']},{$weapon['crit']},{$invItem['crit']})'> > </a></td>
					<td id='weaponDamage'> $damage </td></tr>
					
					<tr><td>Крит</td>
					<td><a href='#' onclick='upCharsWeapon(\"down\", \"crit\",{$weapon['price']},{$weapon['damage']},{$invItem['damage']},{$weapon['crit']},{$invItem['crit']})'> < </a></td>
					<td ><div id='critLvl'> {$invItem['crit']} </div></td>
					<td><a href='#' onclick='upCharsWeapon(\"up\", \"crit\",{$weapon['price']},{$weapon['damage']},{$invItem['damage']},{$weapon['crit']},{$invItem['crit']})'> > </a></td>
					<td id='weaponCrit'> $crit </td></tr>
					<tr><td> Цена </td><td></td><td id='price'>0</td><td></td><td><a href='#' onclick='upWeapon($slot)'>Прокачать</a></td></tr>
				</table>
				</div>";

            echo $text;
        }

        if($invItem["id"] > 500 and $invItem["id"] < 1000){
            $armor = $this->db->getElementOnID("armor", $invItem["id"]);
            $modificator = 1;
            for($i = 1; $i <= $invItem['armor']; $i++)
                $modificator += 0.05;
            $defence = round($armor['defence'] * $modificator, 2);
            $text = "<div id='putSmith'> <img src=\"images/cloth/{$armor['id']}.png\" class='inventoryItems'> </div>
				<div id='allPowerUpsSmith'>
				<table class='upSmithTable'>
					<tr><td>Броня</td>
					<td><a href='#' onclick='upLvlArmor(\"del\",{$armor['defence']},{$armor['price']},{$invItem['armor']})'> < </a></td>
					<td ><div id='armorLvl'> {$invItem['armor']} </div></td>
					<td><a href='#' onclick='upLvlArmor(\"up\",{$armor['defence']},{$armor['price']},{$invItem['armor']})'> > </a></td>
					<td id='armorDefence'> $defence </td></tr>
					<tr><td> Цена </td><td></td><td id='price'>0</td><td></td><td><a href='#' onclick='upArmor($slot)'>Прокачать</a></td></tr>
				</table>
				</div>";
            echo $text;
        }
    }

    public function show($slot, $invItem, $inStorage){
        if($invItem == 0)
            $invItem = unserialize($this->inventory["slot$slot"]);
        if($invItem == null)
            return false;
        if($invItem["id"] < 500){
            $weapon = $this->db->getElementOnID("weapon", $invItem["id"]);
            if($weapon["type"] == 1){ $type="one"; $typeName="Одноручное";}
            if($weapon["type"] == 2){ $type="two"; $typeName="Двуручное";}
            if($weapon["type"] == 3){ $type="staff"; $typeName="Древковое";}
            if($weapon["typedamage"] == 1){ $typedamage="piercing"; $typedamageName="Колющее";}
            if($weapon["typedamage"] == 2){ $typedamage="cutting"; $typedamageName="Режущее";}
            if($weapon["typedamage"] == 3){ $typedamage="maces"; $typedamageName="Дробящее";}
            $damage[0] = $weapon["damage"];
            $crit[0] = $weapon["crit"];
            $modificator = 1;
            for($i = 1; $i <=5; $i++){
                $modificator += 0.05;
                $damage[$i] = round($damage[0] * $modificator,2);
                $crit[$i] = round($crit[0] * $modificator,2);
            }
            $sr["typeName"] = $typeName;
            $sr["type"] = $type;
            $sr["typedamage"] = $typedamage;
            $sr["damageLvl"] = $invItem["damage"];
            $sr["critLvl"] = $invItem["crit"];
            $sr["typedamageName"] = $typedamageName;
            $sr["requiredlvl"] = $weapon["requiredlvl"];
            $sr["damage"] = $damage[$invItem["damage"]];
            $sr["crit"] = $crit[$invItem["crit"]];

            $text = $this->getReplaceTemplate($sr, "weaponView");

            if($weapon["bonusstr"]) $text .= "<div class='detail2 photoDetail' data-title='Сила'><img src='image_char/image/strengh.png' alt='Сила'  height='20' > <br />".$weapon["bonusstr"]."</div>";
            if($weapon["bonusdef"]) $text .= "<div class='detail2 photoDetail' data-title='Защита'><img src='image_char/image/defence.png' alt='Защита'  height='20' > <br/>".$weapon["bonusdef"]."</div>";
            if($weapon["bonusag"]) $text .= "<div class='detail2 photoDetail' data-title='Ловкость'><img src='image_char/image/agility.png' alt='Ловкость' height='20' > <br/>".$weapon["bonusag"]."</div>";
            if($weapon["bonusph"]) $text .= "<div class='detail2 photoDetail' data-title='Телосложение'><img src='image_char/image/physique.png' alt='Телосложение'  height='20' > <br/>".$weapon["bonusph"]."</div>";
            if($weapon["bonusms"]) $text .= "<div class='detail2 photoDetail' data-title='Мастерство'><img src='image_char/image/mastery.png' alt='Мастерство'  height='20' > <br/>".$weapon["bonusms"]."</div>";

            if(!$inStorage)
                echo $text;
            else{
                if($weapon["bonusstr"])  $sr["strengh"] = $weapon["bonusstr"];
                if($weapon["bonusdef"]) $sr["defence"] = $weapon["bonusdef"];
                if($weapon["bonusag"]) $sr["agility"] = $weapon["bonusag"];
                if($weapon["bonusph"]) $sr["physique"] = $weapon["bonusph"];
                if($weapon["bonusms"]) $sr["mastery"] = $weapon["bonusms"];
                $sr["id"] = $invItem["id"];
                echo json_encode($sr);
            }
        }

        if($invItem["id"] > 500 and $invItem["id"] < 1000){
            $armor = $this->db->getElementOnID("armor", $invItem["id"]);
            if($armor["typeDefence"] == 1){ $type="light"; $typeName="Лёгкая";}
            if($armor["typeDefence"] == 2){ $type="medium"; $typeName="Средняя";}
            if($armor["typeDefence"] == 3){ $type="heavy"; $typeName="Тяжелая";}

            $defence[0] = $armor["defence"];
            $modificator = 1;
            for($i = 1; $i <=5; $i++){
                $modificator += 0.05;
                $defence[$i] = round($defence[0] * $modificator,2);
            }
            $sr["type"] = $type;
            $sr["typeName"] = $typeName;
            $sr["requiredlvl"] = $armor["requiredlvl"];
            $sr["armor"] = $defence[$invItem["armor"]];
            $sr["armorLvl"] = $invItem["armor"];
            $text = $this->getReplaceTemplate($sr, "armorView");

            if($armor["bonusstr"]) $text .= "<div class='detail2 photoDetail' data-title='Сила'><img src='image_char/image/strengh.png' alt='Сила'  height='20' > <br />".$armor["bonusstr"]."</div>";
            if($armor["bonusdef"]) $text .= "<div class='detail2 photoDetail' data-title='Защита'><img src='image_char/image/defence.png' alt='Защита'  height='20' > <br/>".$armor["bonusdef"]."</div>";
            if($armor["bonusag"]) $text .= "<div class='detail2 photoDetail' data-title='Ловкость'><img src='image_char/image/agility.png' alt='Ловкость' height='20' > <br/>".$armor["bonusag"]."</div>";
            if($armor["bonusph"]) $text .= "<div class='detail2 photoDetail' data-title='Телосложение'><img src='image_char/image/physique.png' alt='Телосложение'  height='20' > <br/>".$armor["bonusph"]."</div>";
            if($armor["bonusms"]) $text .= "<div class='detail2 photoDetail' data-title='Мастерство'><img src='image_char/image/mastery.png' alt='Мастерство'  height='20' > <br/>".$armor["bonusms"]."</div>";

            if(!$inStorage)
                echo $text;
            else{
                if($armor["bonusstr"])  $sr["strengh"] = $armor["bonusstr"];
                if($armor["bonusdef"]) $sr["defence"] = $armor["bonusdef"];
                if($armor["bonusag"]) $sr["agility"] = $armor["bonusag"];
                if($armor["bonusph"]) $sr["physique"] = $armor["bonusph"];
                if($armor["bonusms"]) $sr["mastery"] = $armor["bonusms"];
                $sr["id"] = $invItem["id"];
                echo json_encode($sr);
            }
        }
    }

    private function generateCode($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
        $code = "";
        $clen = strlen($chars) - 1;
        while (strlen($code) < $length) {
            $code .= $chars[mt_rand(0,$clen)];
        }
        return $code;
    }

    public function buy($iden){
        if(!$this->valid->validID($iden))
            exit;
        $hash = $this->generateCode();
        for($i = 1; $i < count($this->inventory); $i++){
            if($this->inventory["slot$i"] != "0" and $this->inventory["slot$i"] != "99"){
                $invItem = unserialize($this->inventory["slot$i"]);
                if($invItem["hash"] == $hash){
                    $i = 0;
                    $hash = $this->generateCode();
                }
            }
        }
        if($iden < 500){
            $table_name = "weapon";
            $newInvItem = array("hash"=>$hash, "id"=>$iden, "crit" => 0, "damage"=>0);
        }
        if($iden > 500){
            $table_name = "armor";
            $newInvItem = array("hash"=>$hash, "id"=>$iden, "armor" => 0);
        }
        if(!$this->valid->validID($iden) or !$this->db->existsID($table_name, $iden)) exit;
        $thing = $this->db->getAllOnField($table_name, "id", $iden, "", "");
        if($thing["requiredlvl"] > $this->user["lvl"] + 3)	exit;
        $price = $thing["price"];
        if($this->user["Gold"] > $price){
            $statistic = unserialize($this->user["shopStatistic"]);
            $statistic["spentGold"] += $price;
            $statistic["equipment"]++;
            $this->mysqli->autocommit(FALSE);
            $this->db->setFieldOnID("user_statistic", $this->user["id"], "shopStatistic", serialize($statistic));
            $newgold = $this->user["Gold"] - $price;
            $ready = $this->setFieldInventory($this->user["id"], serialize($newInvItem));
            if(!$ready){
                $this->mysqli->rollback();
                die("Нет места в инвентаре.");
            }
            else{
                $this->db->setField("user_resources", "Gold", $newgold, "id", $this->user["id"]);
                $result = $this->mysqli->commit();
                if($result)
                    die("OK".$newgold);
                else die("Что-то пошло не так");
            }
        }
        else die("Не хватает денег.");
    }

    public function buyPotion($id){
        $allPotions = $this->db->getAll("something", "", "");
        $exist = false;
        for($i = 0; $i < count($allPotions); $i++){
            if($allPotions[$i]["image"] == $id){
                $exist = true;
                break;
            }
        }
        if(!$exist) exit;
        $currentgold = $this->user["Gold"];
        $thing = $this->db->getAllOnField("something", "image", $id, "", "");
        if($thing["requiredlvl"] > $this->user["lvl"] + 3) exit;
        $price = $thing["price"];
        if($currentgold > $price){
            $newgold = $currentgold - $price;
            $field = $this->setFieldInvPotion($id);
            if($field){
                $statistic = unserialize($this->user["shopStatistic"]);
                $statistic["spentGold"] += $price;
                $statistic["potions"]++;
                $this->db->setFieldOnID("user_statistic", $this->user["id"], "shopStatistic", serialize($statistic));
                $this->db->setField("user_resources", "Gold", $newgold, "id", $this->user["id"]);
                echo $newgold;
                exit;
            }
            else{
                echo "?";
                exit;
            }
        }
        else{
            echo "!";
            exit;
        }
    }

    private function setFieldInventory($id, $iden) {
        $inventory = $this->db->getAllOnField("user_inventory", "id", $id, "", "");
        $field = false;
        foreach ($inventory as $key => $value){
            if($value == "0"){
                $field = $key;
                break;
            }
        }
        if(!$field)
            return false;
        else
            return $this->db->update("user_inventory", array($field=>$iden), "`id` = '".$id."'");
    }

    private function setFieldInvPotion($id){
        $inventory = $this->inventoryPotions;
        foreach ($inventory as $key => $value){
            if($value === $id){
                $field = $key;
                break;
            }
        }
        if($field != ""){
            $newCount = $inventory[$field."_count"] + 1;
            if($newCount <= 99){
                $this->db->setFieldOnID("user_inventory_potions", $this->user["id"], $field."_count", $newCount);
                return true;
            }
            else return false;
        }
        else{
            foreach ($inventory as $key => $value){
                if($value === "0"){
                    $field = $key;
                    break;
                }
            }
            $this->db->setFieldOnID("user_inventory_potions", $this->user["id"], $field."_count", 1);
            $this->db->setFieldOnID("user_inventory_potions", $this->user["id"], $field, $id);
            return true;
        }
    }

    public function useIt($name){
        if(!$this->valid->check_sql($name)) exit;
        $exist = false;
        for($i = 1; $i <= 5; $i++){
            if($name == $this->inventoryPotions["slot$i"]){
                $exist = true;
                $slot = $i;
                break;
            }
        }
        if(!$exist)		exit;
        $item = $this->db->getAllOnField("something", "image", $name, "", "");
        if($item["typeEffect"] == 1){
            $regenHp = ($this->user["maxHp"] * $item["valueEffect"])/100;
            $newHp = $this->user["currentHp"] + $regenHp;

            if($newHp > $this->user["maxHp"]) $newHp = $this->user["maxHp"];
            $this->db->setField("users", "currentHp", $newHp, "id", $this->user["id"]);
            $newCount = $this->inventoryPotions["slot$i"."_count"] - 1;
            $this->db->setField("user_inventory_potions", "slot$i"."_count", $newCount, "id", $this->user["id"]);
            if($newCount == 0)
                $this->db->setField("user_inventory_potions", "slot$i", 0 , "id", $this->user["id"]);
        }
    }

    public function upSmith($type, $slot, $damageLvl, $critLvl, $armorLvl){
        $invItem = unserialize($this->inventory["slot$slot"]);
        if($type == "weapon"){
            if($damageLvl > $invItem["damage"] or $critLvl > $invItem["crit"]){
                $shopItem = $this->db->getElementOnID("weapon", $invItem["id"]);

                //цена и урон с критом
                $priceCrit[0] = round($shopItem["price"] /10, 0);
                $priceDamage[0] = round($shopItem["price"] /10, 0);
                $damage[0] = $shopItem["damage"];
                $crit[0] = $shopItem["crit"];
                $modificator = 1;
                for($i = 1; $i <=5; $i++){
                    $priceCrit[$i] = round($priceCrit[$i - 1] * 1.5, 0);
                    $priceDamage[$i] = round($priceDamage[$i - 1] * 1.75, 0);
                    $modificator += 0.05;
                    $damage[$i] = round($damage[0] * $modificator,2);
                    $crit[$i] = round($crit[0] * $modificator,2);
                }

                $totalPrice = 0;
                if($damageLvl > $invItem["damage"] and $damageLvl <=5 and $damageLvl > 0){
                    for($i = $invItem["damage"]; $i <= $damageLvl; $i++)
                        $totalPrice += $priceDamage[$i];
                    $invItem["damage"] = $damageLvl;
                    $changes["damageLvl"] = $damageLvl;
                    $changes["damage"] = "{$damage[$damageLvl]}";
                }
                if($critLvl > $invItem["crit"] and $critLvl <=5 and $critLvl > 0){
                    for($i = $invItem["crit"]; $i <= $critLvl; $i++)
                        $totalPrice += $priceCrit[$i];
                    $invItem["crit"] = $critLvl;
                    $changes["critLvl"] = $critLvl;
                    $changes["crit"] = "{$crit[$critLvl]}";
                }
                if($this->user["Another"] < $totalPrice)
                    die("Недостаточно жемчуга. ");
                else{
                    $this->mysqli->autocommit(FALSE);
                    $this->db->setFieldOnID("user_inventory", $this->user["id"], "slot$slot", serialize($invItem));
                    $this->db->setFieldOnID("user_resources", $this->user["id"], "Another", $this->user["Another"] - $totalPrice);
                    $statistic = unserialize($this->user["shopStatistic"]);
                    $statistic["spentAnother"] += $totalPrice;
                    $this->db->setFieldOnID("user_statistic", $this->user["id"], "shopStatistic", serialize($statistic));
                    $result = $this->mysqli->commit();
                    if($result){
                        $changes = json_encode($changes);
                        die("OK".$changes."".$invItem["hash"]);
                    }
                    else die("Что-то пошло не так.");
                }
            }
        }
        if($type == "armor"){
            if($armorLvl > $invItem["armor"] and $armorLvl <=5 and $armorLvl > 0){
                $shopItem = $this->db->getElementOnID("armor", $invItem["id"]);
                $priceArmor[0] = round($shopItem["price"] /10, 0);
                $totalPrice = 0;
                $armor[0] = $shopItem["armor"];
                $modificator = 1;
                for($i = 1; $i <=5; $i++){
                    $priceArmor[$i] = round($priceArmor[$i - 1] * 1.9, 0);
                    $modificator += 0.05;
                    $armor[$i] = round($armor[0] * $modificator,2);
                }
                if($armorLvl > $invItem["armor"] and $armorLvl <=5 and $armorLvl > 0){
                    for($i = $invItem["armor"]; $i <= $armorLvl; $i++)
                        $totalPrice += $priceArmor[$i];
                    $invItem["armor"] = $armorLvl;
                    $changes["armorLvl"] = $armorLvl;
                    $changes["armor"] = "{$armor[$armorLvl]}";
                }
                if($this->user["Another"] < $totalPrice)
                    die("Недостаточно жемчуга. ");
                else{
                    $this->db->setFieldOnID("user_inventory", $this->user["id"], "slot$slot", serialize($invItem));
                    $this->db->setFieldOnID("user_resources", $this->user["id"], "Another", $this->user["Another"] - $totalPrice);
                    $changes = json_encode($changes);
                    die("OK".$changes."".$invItem["hash"]);
                }
            }
        }
    }

}
    $inventoryFunctions = new inventoryFunctions();

switch ($_REQUEST["WhatIMustDo"]) {
    case "putOffThisThing":
        $inventoryFunctions->putOff($_REQUEST["slot"]);
        break;
    case "deleteThis":
        $inventoryFunctions->deleteThis($_REQUEST["slot"], $_REQUEST["type"]);
        break;
    case "wantDelete":
        $inventoryFunctions->wantDelete($_REQUEST["slot"], $_REQUEST["type"]);
        break;
    case "putOnThisThing":
        $inventoryFunctions->put($_REQUEST["slot"]);
        break;
    case "showDetails":
        $inventoryFunctions->show($_REQUEST["iden"], 0, $_REQUEST["inStorage"]);
        break;
    case "showDetailsSmith":
        $inventoryFunctions->showDetailsSmith($_REQUEST["slot"]);
        break;
    case "getMenuSmith":
        $inventoryFunctions->getMenuSmith($_REQUEST["slot"]);
        break;
    case "buyThing":
        $inventoryFunctions->buy($_REQUEST["iden"]);
        break;
    case "useIt":
        $inventoryFunctions->useIt($_REQUEST["name"]);
        break;
    case "buyPotion":
        $inventoryFunctions->buyPotion($_REQUEST["iden"]);
        break;
    case "upSmith":
        $inventoryFunctions->upSmith($_REQUEST["type"], $_REQUEST["slot"], $_REQUEST["damageLvl"], $_REQUEST["critLvl"], $_REQUEST["armorLvl"]);
        break;
}
?>