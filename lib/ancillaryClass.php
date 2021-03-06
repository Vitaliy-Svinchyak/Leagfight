<?php

class Ancillary{

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getUserInfo($id){
        if($this->db->valid->validID($id)){
            $user = $this->db->getFieldsBetter( "users", "id", $id, array("id", "avatar", "login", "league", "Strengh", "Defence", "Agility", "Mastery", "Physique",
                "power", "lvl", "helmet", "armor", "bracers", "leggings", "primaryWeapon", "secondaryWeapon", "currentExp", "currentHp"), $sign = "=");
            return $user[0];
        }
        else exit;
    }

    public function getAllArmors($user){
        //изменение, попытка 2
        $where = "";
        $limit = 0;
        if(is_array($user["armor"])){
            $where .= "`id`='".$user["armor"]["id"]."'";
            $userEquip[] = "armor";
            $limit++;
        }
        if(is_array($user["helmet"])){
            $where .= " || `id`='".$user["helmet"]["id"]."'";
            $userEquip[] = "helmet";
            $limit++;
        }
        if(is_array($user["bracers"])){
            $where .= " || `id`='".$user["bracers"]["id"]."'";
            $userEquip[] = "bracers";
            $limit++;
        }
        if(is_array($user["leggings"])){
            $where .= " || `id`='".$user["leggings"]["id"]."'";
            $userEquip[] = "leggings";
            $limit++;
        }
        if(is_array($user["secondaryWeapon"]) and $user["secondaryWeapon"]["id"] > 500){
            $where .= " || `id`='".$user["secondaryWeapon"]["id"]."'";
            $userEquip[] = "secondaryWeapon";
            $limit++;
        }
        if($where != "")
            $allArmors = $this->db->select("armor", array("*"), $where, "", "", $limit);
        return array($allArmors, $userEquip);
    }

    public function getInfo($id, $arrays = false){
        if(!$arrays) {
            $user = $this->getUserInfo($id);
            $userInventory = $this->db->getElementOnID("user_inventory", $id, true);
        }
        else{
            $user = $arrays["user"];
            $userInventory = $arrays["userInventory"];
        }
        //Экипировка
        for($i = 1; $i <= 24; $i++){
            $invItem = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["armor"]) $user["armor"] = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["helmet"]) $user["helmet"] = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["leggings"]) $user["leggings"] = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["bracers"]) $user["bracers"] = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["primaryWeapon"]) $user["primaryWeapon"] = unserialize($userInventory["slot$i"]);
            if( $invItem["hash"] == $user["secondaryWeapon"]) $user["secondaryWeapon"] = unserialize($userInventory["slot$i"]);
        }
        unset($invItem);
        $allArmors = $this->getAllArmors($user);
        $userEquip = $allArmors[1];
        $allArmors = $allArmors[0];

        $count = count($allArmors);
        for($i = 0; $i <= $count; $i++){
            if($allArmors[$i]["thing"] == 2){
                $armorLvl =  $user["armor"]["armor"];
                $user["armor"] = $allArmors[$i];
                $user["armor"]["defence"] = $this->getBonus($user["armor"]["defence"],  $armorLvl);
                $user["armor"]["armorLvl"] = $armorLvl;
            }
            if($allArmors[$i]["thing"] == 3){
                $armorLvl =  $user["helmet"]["armor"];
                $user["helmet"] = $allArmors[$i];
                $user["helmet"]["defence"] = $this->getBonus($user["helmet"]["defence"],  $armorLvl);
                $user["helmet"]["armorLvl"] = $armorLvl;
            }
            if($allArmors[$i]["thing"] == 4){
                $armorLvl =  $user["leggings"]["armor"];
                $user["leggings"] = $allArmors[$i];
                $user["leggings"]["defence"] = $this->getBonus($user["leggings"]["defence"],  $armorLvl);
                $user["leggings"]["armorLvl"] = $armorLvl;
            }
            if($allArmors[$i]["thing"] == 5){
                $armorLvl =  $user["bracers"]["armor"];
                $user["bracers"] = $allArmors[$i];
                $user["bracers"]["defence"] = $this->getBonus($user["bracers"]["defence"],  $armorLvl);
                $user["bracers"]["armorLvl"] = $armorLvl;
            }
            if($allArmors[$i]["thing"] == 6){
                $armorLvl =  $user["secondaryWeapon"]["armor"];
                $user["secondaryWeapon"]= $allArmors[$i];
                $user["secondaryWeapon"]["defence"] = $this->getBonus($user["secondaryWeapon"]["defence"],$armorLvl);
                $user["secondaryWeapon"]["armorLvl"] = $armorLvl;
            }
        }
        if($user["primaryWeapon"] != 0){
            $critLvl = $user["primaryWeapon"]["crit"];
            $damageLvl = $user["primaryWeapon"]["damage"];
            $user["primaryWeapon"] = $this->db->getAllOnField("weapon", "id", $user["primaryWeapon"]["id"], "", "");
            $user["primaryWeapon"]["damage"] = $this->getBonus($user["primaryWeapon"]["damage"], $damageLvl);
            $user["primaryWeapon"]["crit"] = $this->getBonus($user["primaryWeapon"]["crit"], $critLvl);
            $user["primaryWeapon"]["critLvl"] =  $critLvl;
            $user["primaryWeapon"]["damageLvl"] =  $damageLvl;
        }
        if($user["secondaryWeapon"] != 0 and $user["secondaryWeapon"]["id"] < 500){
            $critLvl = $user["secondaryWeapon"]["crit"];
            $damageLvl = $user["secondaryWeapon"]["damage"];
            $user["secondaryWeapon"] = $this->db->getAllOnField("weapon", "id",$user["secondaryWeapon"]["id"] , "", "");
            $user["secondaryWeapon"]["damage"] = $this->getBonus($user["secondaryWeapon"]["damage"], $damageLvl);
            $user["secondaryWeapon"]["crit"] = $this->getBonus($user["secondaryWeapon"]["crit"], $critLvl);
            $user["secondaryWeapon"]["critLvl"] =  $critLvl;
            $user["secondaryWeapon"]["damageLvl"] =  $damageLvl;
        }
        $userTotalArmor = 0;
        $userArmorTypes = array(1 => 0, 2 => 0, 3 => 0);
        if(is_null($userEquip))
            return false;
        foreach($userEquip as $key => $value){
            if($key != "secondaryWeapon"){
                $userTotalArmor += $user[$value]["defence"];
                $userArmorTypes[$user[$value]["typeDefence"]] = 1;
            }
            $user['Strengh'] +=  $user[$value]["bonusstr"];
            $user['Defence'] += $user[$value]["bonusdef"];
            $user['Agility'] +=  $user[$value]["bonusag"];
            $user['Physique'] +=  $user[$value]["bonusph"];
            $user['Mastery']  +=$user[$value]["bonusms"];
        }

        //Характеристики
        $user['Strengh'] +=  $user["primaryWeapon"]["bonusstr"] + $user["secondaryWeapon"]["bonusstr"];
        $user['Defence'] +=  $user["primaryWeapon"]["bonusdef"] + $user["secondaryWeapon"]["bonusdef"];
        $user['Agility'] +=  $user["primaryWeapon"]["bonusag"] + $user["secondaryWeapon"]["bonusag"];
        $user['Physique'] += $user["primaryWeapon"]["bonusph"] + $user["secondaryWeapon"]["bonusph"];
        $user['Mastery']  +=  $user["primaryWeapon"]["bonusms"] + $user["secondaryWeapon"]["bonusms"];

        return array("user" => $user, "totalArmor" => $userTotalArmor, "armorTypes" => $userArmorTypes);
    }

    private function getBonus($char, $lvl){
        $modificator = 1;
        for($i = 0; $i <= $lvl; $i++){
            $modificator += 0.05;
        }
        $result = round($char * $modificator, 2);
        return $result;
    }

    public function getDamageBonus($typedamage, $armorTypes){
        $damageBonus = 1;
        if($typedamage == "1"){
            if($armorTypes[1] == 1) $damageBonus += 0;
            if($armorTypes[2] == 1) $damageBonus += 0.25;
            if($armorTypes[3] == 1) $damageBonus -= 0.25;
        }
        if($typedamage == "2"){
            if($armorTypes[1] == 1) $damageBonus -= 0.25;
            if($armorTypes[2] == 1) $damageBonus += 0;
            if($armorTypes[3] == 1) $damageBonus -= 0.25;
        }
        if($typedamage == "3"){
            if($armorTypes[1] == 1) $damageBonus -= 0.25;
            if($armorTypes[2] == 1) $damageBonus += 0;
            if($armorTypes[3] == 1) $damageBonus += 0.25;
        }
        return $damageBonus;
    }
}
?>