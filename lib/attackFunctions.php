<?php
require_once "checkvalid_class.php";
require_once "database_class.php";

class attackFunctions extends DataBase{
	
	public function __construct() {
		parent::__construct();
		$this->db = $this;
	}
	
	public function getUserInfo($id){
		if($this->valid->validID($id)){
			$user = $this->db->getFieldsBetter( "users", "id", $id, array("id", "login", "league", "Strengh", "Defence", "Agility", "Mastery", "Physique",
                "power", "lvl", "helmet", "armor", "bracers", "leggings", "primaryWeapon", "secondaryWeapon", "currentExp", "currentHp"), $sign = "=");
		    return $user[0];
        }
		else exit;
	}
	
	private function getInfo($id){
        $where = "";
        $limit = 0;
        $allArmors = array();
		$user = $this->getUserInfo($id);
		$userInventory = $this->db->getElementOnID("user_inventory", $id, true);

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
        $count = count($allArmors);
        for($i = 0; $i <= $count; $i++){
            if($allArmors[$i]["thing"] == 2){
                $user["armor"] = $allArmors[$i];
                $user["armor"]["defence"] = $this->getBonus($user["armor"]["defence"], $user["armor"]["armor"]);
            }
            if($allArmors[$i]["thing"] == 3){
                $user["helmet"] = $allArmors[$i];
                $user["helmet"]["defence"] = $this->getBonus($user["helmet"]["defence"], $user["helmet"]["armor"]);
            }
            if($allArmors[$i]["thing"] == 4){
                $user["leggings"] = $allArmors[$i];
                $user["leggings"]["defence"] = $this->getBonus($user["leggings"]["defence"], $user["leggings"]["armor"]);
            }
            if($allArmors[$i]["thing"] == 5){
                $user["bracers"] = $allArmors[$i];
                $user["bracers"]["defence"] = $this->getBonus($user["bracers"]["defence"], $user["bracers"]["armor"]);
            }
            if($allArmors[$i]["thing"] == 6){
                $user["secondaryWeapon"]= $allArmors[$i];
                $user["secondaryWeapon"]["defence"] = $this->getBonus($user["secondaryWeapon"]["defence"], $user["secondaryWeapon"]["armor"]);
            }
        }
        if($user["primaryWeapon"] != 0){
            $user["primaryWeapon"] = $this->db->getAllOnField("weapon", "id", $user["primaryWeapon"]["id"], "", "");
            $user["primaryWeapon"]["damage"] = $this->getBonus($user["primaryWeapon"]["damage"], $user["primaryWeapon"]["damage"]);
            $user["primaryWeapon"]["crit"] = $this->getBonus($user["primaryWeapon"]["crit"], $user["primaryWeapon"]["crit"]);
        }
        else{
            $userWeapon["damage"] = 0.1;
            $userWeapon["crit"] = 0.2;
        }
        if($user["secondaryWeapon"] != 0 and $user["secondaryWeapon"]["id"] < 500){
            if($user["secondaryWeapon"]["id"] == $user["primaryWeapon"]["id"]){
                $user["secondaryWeapon"]["damage"] = $this->getBonus($user["secondaryWeapon"]["damage"], $user["primaryWeapon"]["damage"]);
                $user["secondaryWeapon"]["crit"] = $this->getBonus($user["secondaryWeapon"]["crit"], $user["primaryWeapon"]["crit"]);
            }
            else{
                $user["secondaryWeapon"] = $this->db->getAllOnField("weapon", "id",$user["secondaryWeapon"]["id"] , "", "");
                $user["secondaryWeapon"]["damage"] = $this->getBonus($user["secondaryWeapon"]["damage"], $user["secondaryWeapon"]["damage"]);
                $user["secondaryWeapon"]["crit"] = $this->getBonus($user["secondaryWeapon"]["crit"], $user["secondaryWeapon"]["crit"]);
            }
        }
        $userTotalArmor = 0;
        $userArmorTypes = array(0,0,0);
        foreach($userEquip as $key=>$value){
            $userTotalArmor += $user[$value]["defence"];
            $userArmorTypes[$user[$value]["typeDefence"]] = 1;
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

        return array("user" => $user, "totalArmor" => $userTotalArmor, "armorTypes" => $userArmorTypes, "userInventory" => $userInventory);
    }
	
	public function attack($type, $avatar, $id){
		if($type == "arenaUser"){
			if(!$this->valid->validID($id) or !$this->db->existsID("users", $id))
				echo "Location:?view=arena";
			$defender = $this->getInfo($id);
		}
        session_start();
		$agressor =  $this->getInfo($_SESSION["id"]);
		$idUser = $agressor["user"]["id"];

		$time = time();
		if($type == "arenaUser"){
			if($agressor["timerAttack"] < $time and $defender["currentHp"] > 50 and $agressor["lvl"] - $defender["lvl"] <= 3 and $defender["lvl"] - $agressor["lvl"] <= 3){
				if($agressor["league"] != 0){
					if($agressor["league"] == $defender["league"]){
						echo "friend";
						exit;
					}
				}
			}
		}
		if($type == "arenaBot"){
			$defender = $this->db->getAllOnField("arena_bots", "id", $_SESSION["botId"], "", "");
			$equipment = unserialize($defender["equipment"]);
			$defender["Strengh"] = $_SESSION["bot_strengh"];
			$defender["Defence"] = $_SESSION["bot_defence"];
			$defender["Agility"] = $_SESSION["bot_agility"];
			$defender["Physique"] = $_SESSION["bot_physique"];
			$defender["Mastery"] = $_SESSION["bot_mastery"];
			if(rand(0,1))	$defender["primaryWeapon"] = $equipment["primaryWeapon"];
			else $defender["primaryWeapon"] = 0;
			if(rand(0,1))	$defender["secondaryWeapon"] = $equipment["secondaryWeapon"];
			else $defender["secondaryWeapon"] = 0;
			if(rand(0,1))	$defender["armor"] = $equipment["armor"];
			else $defender["armor"] = 0;
			if(rand(0,1))	$defender["helmet"] = $equipment["helmet"];
			else $defender["helmet"] = 0;
			if(rand(0,1))	$defender["leggings"] = $equipment["leggings"];
			else $defender["leggings"] = 0;
			if(rand(0,1))	$defender["bracers"] = $equipment["bracers"];
			else $defender["bracers"] = 0;
			unset($_SESSION["bot_strengh"]);
			unset($_SESSION["bot_defence"]);
			unset($_SESSION["bot_agility"]);
			unset($_SESSION["bot_physique"]);
			unset($_SESSION["bot_mastery"]);
		}
			
			//Высчитываем урон
			$allAgrDamage = array();
			$allDefDamage = array();
			$agrDamageBlocked = array();
			$defDamageBlocked = array();
			for($i = 0; $i < 5; $i++){
				$agressorDmg = $this->getDamage($agressor, $defender);
				$defenderDmg = $this->getDamage($defender, $agressor);
				$allAgrDamage[$i] = $agressorDmg[0];
				$allDefDamage[$i] = $defenderDmg[0];
				$typesAgrDamage[$i] = $agressorDmg[1];
				$typesDefDamage[$i] = $defenderDmg[1];
				if($agressorDmg[1] == "shield")
					$defDamageBlocked[$i] = $agressorDmg[2];
				if($defenderDmg[1] == "shield")
					$agrDamageBlocked[$i] = $defenderDmg[2];
			}
			$totalAgrDamage = 0;
			$totalDefDamage = 0;
			for($i = 0; $i <= count($allAgrDamage); $i++)
				$totalAgrDamage += $allAgrDamage[$i];
			for($i = 0; $i <= count($allDefDamage); $i++)
				$totalDefDamage += $allDefDamage[$i];
			
			//Добавляем конечные результаты в таблицу
			$agrStatistic = $this->db->getAllOnField("user_statistic", "id", $agressor["user"]["id"], "", "");
			if($type == "arenaUser")
				$defStatistic = $this->db->getAllOnField("user_statistic", "id", $defender["user"]["id"], "", "");
			$this->mysqli->autocommit(FALSE);
			if($totalAgrDamage > $totalDefDamage){
				$winner = $agressor["user"]["id"];
				$prize = $this->getPrize($agressor["user"], $defender["user"], $type, $agrStatistic, $defStatistic);
			}
			if($totalAgrDamage < $totalDefDamage){
				$winner = $defender["user"]["id"];
				$prize = $this->getPrize($defender["user"], $agressor["user"], $type, $defStatistic, $agrStatistic);
			}
			if($totalAgrDamage == $totalDefDamage){
				$winner = 0;
				$prize = array("gold"=>0);
			}
			$agressorHp = $agressor["user"]["currentHp"] - $totalDefDamage;
			$defenderHp = $defender["user"]["currentHp"] - $totalAgrDamage;
			if($agressorHp <=0) $agressorHp = 1;
			if($defenderHp <=0) $defenderHp = 1;
			
			
			//ХП
			$this->db->setField("users", "currentHp", $agressorHp, "id", $agressor["user"]["id"]);
			if($type == "arenaUser")
				$this->db->setField("users", "currentHp", $defenderHp, "id", $defender["user"]["id"]);
			
			//Статистика
			$agrUserStatistic = unserialize($agrStatistic["userStatistic"]);
			$agrDamageStatistic = unserialize($agrStatistic["damageStatistic"]);
			if($type == "arenaUser"){
				$defUserStatistic = unserialize($defStatistic["userStatistic"]);
				$defDamageStatistic = unserialize($defStatistic["damageStatistic"]);
				$agrDamageStatistic["damageDealt"] += $totalAgrDamage;
				$agrDamageStatistic["damageReceived"] += $totalDefDamage;
				$defDamageStatistic["damageDealt"] += $totalDefDamage;
				$defDamageStatistic["damageReceived"] += $totalAgrDamage;
				for($i = 0; $i < 5; $i++){
					if($typesAgrDamage[$i] == "damage" or $typesAgrDamage[$i] == "crit"){
						$agrDamageStatistic["hits"]++;
					}
					if($typesAgrDamage[$i] == "crit"){
						$agrDamageStatistic["crits"]++;
						$agrDamageStatistic["critDamage"] += $allAgrDamage[$i];
					}
					if($typesAgrDamage[$i] == "Dodge"){
						$agrDamageStatistic["misses"]++;
						$defDamageStatistic["dodges"]++;
					}
					if($typesAgrDamage[$i] == "SecondHit")
						$agrDamageStatistic["secondHits"]++;
					if($typesAgrDamage[$i] == "shield"){
						$defDamageStatistic["damageBlocked"] += $defDamageBlocked[$i];
						$defDamageStatistic["Blocks"]++;
					}
					
					if($typesDefDamage[$i] == "damage" or $typesDefDamage[$i] == "crit"){
						$defDamageStatistic["hits"]++;
					}
					if($typesDefDamage[$i] == "crit"){
						$defDamageStatistic["crits"]++;
						$defDamageStatistic["critDamage"] += $allDefDamage[$i];
					}
					if($typesDefDamage[$i] == "Dodge"){
						$defDamageStatistic["misses"]++;
						$agrDamageStatistic["dodges"]++;
					}
					if($typesDefDamage[$i] == "SecondHit")
						$defDamageStatistic["secondHits"]++;
					if($typesDefDamage[$i] == "shield"){
						$agrDamageStatistic["damageBlocked"] += $agrDamageBlocked[$i];
						$agrDamageStatistic["Blocks"]++;
					}	
				}

				if($winner == $agressor["user"]["id"]){
					$agrUserStatistic["wins"]++;
					$agrUserStatistic["goldStolen"] += $prize["coinBlack"];
					$defUserStatistic["lose"]++;
					$defUserStatistic["goldLost"] += $prize["coinBlack"];
				}
				if($winner == $defender["user"]["id"]){
					$agrUserStatistic["lose"]++;
					$agrUserStatistic["goldLost"] += $prize["coinBlack"];
					$defUserStatistic["wins"]++;
					$defUserStatistic["goldStolen"] += $prize["coinBlack"];
				}
				if($winner == 0){
					$agrUserStatistic["draw"]++;
					$defUserStatistic["draw"]++;
				}
				$this->updateStatistic($agrUserStatistic, $agrDamageStatistic, $agressor["user"]["id"]);
				$this->updateStatistic($defUserStatistic, $defDamageStatistic, $defender["user"]["id"]);
			}
			if($type == "arenaBot"){
				$allBots = unserialize($agrStatistic["allBots"]);
				$botStatistic = unserialize($agrStatistic["arenaBot".$defender["lvl"]]);
				for($i = 0; $i <= count($botStatistic); $i++){
					if($botStatistic[$i]["id"] == $defender["user"]["id"]){
						$thisBotStatistic = $botStatistic[$i];
						break;
					}
				}
				if($winner == $agressor["user"]["id"]){
					if(!$_SESSION["goNextLvl"])
						$_SESSION["goNextLvl"] = 1;
					$thisBotStatistic["wins"]++;
					$allBots["wins"]++;
					if($prize["coinBlack"] != 0){
						$allBots["stoleGold"] += $prize["coinBlack"];
						$thisBotStatistic["stoleGold"] += $prize["coinBlack"];
					}
				}
				if($winner == 0){
					$thisBotStatistic["draw"]++;
				}
				if($winner == $defender["id"]){
					$thisBotStatistic["lose"]++;
					$allBots["lose"]++;
					if($prize["coinBlack"] != 0){
						$allBots["lostGold"] += $prize["coinBlack"];
						$thisBotStatistic["lost"] += $prize["coinBlack"];
					}
				}
				$botStatistic[$i] = $thisBotStatistic;
				$this->db->update("user_statistic", array("arenaBot".$_SESSION["botLvl"] => serialize($botStatistic), "allBots" =>  serialize($allBots)), "`id` = '".$agressor["user"]["id"]."'");
			}
			$prize = serialize($prize);

			$allAgrDamage = serialize($allAgrDamage);
			$allDefDamage = serialize($allDefDamage);
			$typesAgrDamage = serialize($typesAgrDamage);
			$typesDefDamage = serialize($typesDefDamage);
            $agressorInfo = $agressor["user"];
            $defenderInfo= $defender["user"];
            $agressor = serialize($agressor);
            $defender = serialize($defender);

        $table_name = $this->config->db_prefix."logs";
			$query = "INSERT INTO `$table_name` (idAgressor, idDefender,  typeFight, Time, winner, prize, allAgrDamage, allDefDamage, typesAgrDamage, typesDefDamage,
			avatar, agressor, defender)
			VALUES ($idUser, $id, '$type', $time, $winner, '$prize', '$allAgrDamage', '$allDefDamage', '$typesAgrDamage', '$typesDefDamage','$avatar', '$agressor', '$defender')";
			$log_result = $this->query($query);
			
			if($log_result){
				//Таймер
				if($type == "arenaUser")
					$this->db->setField("users", "timerAttack", $time, "id", $agressorInfo["id"]);		//ЗДЕСЬ ДОБАВИТЬ + 900
					
				$log = $this->db->getAllOnField("logs", "Time", $time, "Time", "");
				$idLog = $log["idLog"];
				
				$date = date("Y").date("m").date("d").date("H").date("i").date("s");
				$time = date("H").":".date("i").":".date("s");
				$beautifulDate = date("d").".".date("m").".".date("y");
				$defenderNick = $defenderInfo["login"];
				$agressorNick = $agressorInfo["login"];
				
				//Почта
				$mass = array("idDialog" =>$idLog, "idUser"=>$agressorInfo["id"], "idSender" => 0, "textMessage"=>"<a href='?view=fightLog&id=$idLog'>Отчет о сражении.</a>",
				"time"=>$time, "date"=>$date, "beautifulDate"=>$beautifulDate, "type"=>2, "title"=>"Вы напали на $defenderNick", "status" => 1, "extra" => serialize(array("winner" => $winner, "resources" => $prize)));
				$this->db->insert("mail", $mass);
				if($type == "arenaUser"){
					$mass = array("idDialog" =>$idLog, "idUser"=>$defenderInfo["id"], "idSender" => 0, "textMessage"=>"<a href='?view=fightLog&id=$idLog'>Отчет о сражении.</a>",
					"time"=>$time, "date"=>$date, "beautifulDate"=>$beautifulDate, "type"=>2, "title"=>"На вас напал $agressorNick", "status" => 1, "extra" => serialize(array("winner" => $winner, "resources" => $prize)));
					$this->db->insert("mail", $mass);
				}
				$this->mysqli->commit();
			}
			unset($_SESSION["botId"]);
			die($idLog);
	}
	
	private function getPrize($winner, $loser, $type, $winnerStatistic, $loserStatistic){
		if($type == "arenaUser"){
            $loserResources = $this->db->getAllOnField("user_resources", "id", $loser["id"], "", "");
            $winnerResources = $this->db->getAllOnField("user_resources", "id", $winner["id"], "", "");
			$loserHouse = $this->db->getAllOnField("user_house", "id", $loser["id"], "", "");
			$safeGold = $loserHouse["safe"] * 5;
			
			//грабеж
			$gold = round(($loserResources["Gold"] - $loserResources["Gold"]* $safeGold / 100)/10,0);
				$this->db->setField("user_resources", "Gold", $loserResources["Gold"] - $gold, "id", $loser["id"]);
				$this->db->setField("user_statistic", "goldLost", $loserStatistic["goldLost"] + $gold, "id", $loser["id"]);
				$this->db->setField("user_resources", "Gold", $winnerResources["Gold"] + $gold, "id", $winner["id"]);
				$this->db->setField("user_statistic", "goldStolen", $winnerStatistic["goldStolen"] + $gold, "id", $winner["id"]);
				
				//опыт
				if($winner["lvl"] >= $loser["lvl"]) $exp = 1;
				if($loser["lvl"] - $winner["lvl"] == 1 or $loser["lvl"] - $winner["lvl"] == 2) $exp = 2;
				if($loser["lvl"] - $winner["lvl"] == 3 ) $exp = 3;
				$this->db->setField("users", "currentExp", $winner["currentExp"] + $exp, "id", $winner["id"]);
			
			$this->checkUpLvl($winner["id"]);
			$resources = array("coinBlack"=>$gold, "expBlack" => $exp);
		}
		if($type == "arenaBot"){
			$bot = $this->db->getAllOnField("arena_bots", "id", $_SESSION["botId"], "", "");
			$botResources = unserialize($bot["prize"]);
			if($winner["id"] == $this->user["id"]){
				$exp = $botResources["exp"];
				if($exp != 0)
					$this->db->setField("users", "currentExp", $this->user["currentExp"] + $exp, "id", $winner["id"]);
				$this->checkUpLvl($this->user["id"]);
				$gold = round(rand(75,125) * $botResources["Gold"] /100, 0);
				$another = round(rand(75,125) * $botResources["Another"] /100, 0);
				$tournament_icon = $botResources["tournament_icon"];
				$values = "";
				if($gold != 0){
					$goldWin = $gold + $this->resources["Gold"];
					$values .= "`Gold` = '$goldWin',";
				}
				if($another != 0){
					$anotherWin = $another + $this->resources["Another"];
					$values .= " `Another` = '$anotherWin',";
				}
				if($tournament_icon != 0){
					$tournament_icon_win = $tournament_icon + $this->resources["tournament_icon"];
					$values .= " `tournament_icon` = '$tournament_icon_win'";
				}
				$table_name = $this->config->db_prefix."user_resources";
				$query = "UPDATE $table_name SET $values WHERE id=".$this->user["id"];
				$this->query($query);
				$resources = array("coinBlack"=>$gold, "diamond"=>$another, "tournament_icon"=>$tournament_icon, "expBlack"=>$exp);
			}
			else{
				$gold = round($this->resources["Gold"]/20, 0);
				$this->db->setField("user_resources", "Gold", $this->resources["Gold"] - $gold, "id", $this->user["id"]);
				$resources = array("coinBlack"=>$gold);
			}
		}
		return $resources;
	}
	
	public function getDamage($agressor, $defender){
            $armorTypes = $defender["armorTypes"];
            $Weapon  = $agressor["user"]["primaryWeapon"];
            $secWeapon =  $agressor["user"]["secondaryWeapon"];
            $second = false;
			$DamageBonus = 1;
			$secDamageBonus = 1;
			if(!$Weapon){
				$Weapon["damage"] = 0.1;
				$Weapon["crit"] = 0.2;
			}
			
			//Расчет бонуса от оружия против брони
			if($Weapon["typeDamage"] == 1){
				if($armorTypes[0] == 1) $DamageBonus *= 1;
				if($armorTypes[0] == 2) $DamageBonus *= 1.25;
				if($armorTypes[0] == 2) $DamageBonus *= 0.75;
			}
			if($Weapon["typeDamage"] == 2){
				if($armorTypes[0] == 1) $DamageBonus *= 1.25;
				if($armorTypes[0] == 2) $DamageBonus *= 1;
				if($armorTypes[0] == 2) $DamageBonus *= 0.75;
			}
			if($Weapon["typeDamage"] == 3){
				if($armorTypes[0] == 1) $DamageBonus *= 0.75;
				if($armorTypes[0] == 2) $DamageBonus *= 1;
				if($armorTypes[0] == 2) $DamageBonus *= 1.25;
			}
			
			if($secWeapon["typeDamage"] == 1){
				if($armorTypes[0] == 1) $secDamageBonus *= 1;
				if($armorTypes[0] == 2) $secDamageBonus *= 1.25;
				if($armorTypes[0] == 2) $secDamageBonus *= 0.75;
			}
			if($secWeapon["typeDamage"] == 2){
				if($armorTypes[0] == 1) $secDamageBonus *= 1.25;
				if($armorTypes[0] == 2) $secDamageBonus *= 1;
				if($armorTypes[0] == 2) $secDamageBonus *= 0.75;
			}
			if($secWeapon["typeDamage"] == 3){
				if($armorTypes[0] == 1) $secDamageBonus *= 0.75;
				if($armorTypes[0] == 2) $secDamageBonus *= 1;
				if($armorTypes[0] == 2) $secDamageBonus *= 1.25;
			}
			
			//Проверка на уворот
			$dodgeChance =  $agressor["user"]["Strengh"] /($agressor["user"]["Strengh"] + $defender["user"]["Agility"]) * 100;
			$dodgeRand = rand(0, 100);
			if( $dodgeRand < $dodgeChance){
			
				//Проверка на крит
				if($defender["user"]["Mastery"] > 15)	$critChance = ($agressor["user"]["Mastery"] + $defender["user"]["Mastery"])/($defender["user"]["Mastery"]/15);
				else $critChance = 100;
				$critRand = rand(0, 100);
				if( $critRand <= $critChance){
					$DamageBonus += $Weapon["crit"] - 1;
					$typeHit = "crit";
				}
				else $typeHit = "damage";
				
				//Расчет наносимого урона
				$damageRand = rand(75, 125);
				$damageRand = $damageRand / 100;
				$Damage = $agressor["user"]["Strengh"] * $damageRand * $DamageBonus * $Weapon["damage"];
				$defenceRand = rand(50, 100);
				$defenceRand = $defenceRand / 100;
				$TakenDamage = $defender["user"]["Defence"] * $defenceRand * $defender["totalArmor"];
				
				$Dmg = $Damage - $TakenDamage;
				if($Dmg < 0){
					$Dmg = 0;
					$typeHit = "armor";
				}
			}
			else{
				$Dmg = 0;
				$typeHit = "Dodge";
			}

			//Вычисляем второй удар
			if($secWeapon["type"] == 1 and $second == false){
				$secondHitChance = ($agressor["user"]["Strengh"]/2 + $agressor["user"]["Mastery"])/($defender["user"]["Mastery"]/10);
				$secondRand = rand(0, 100);
				if($secondHitChance >= $secondRand){
					$second = true;
					if(rand(0,100) <= $dodgeChance){
						if(rand(0,100) <= $critChance) $secDamageBonus += $secWeapon["crit"] - 1;
						$newDamage = $agressor["user"]["Strengh"] * $damageRand * $secDamageBonus * $secWeapon["damage"];
					}
					$Dmg += $newDamage;
					$typeHit = "SecondHit";
				}
			}
			//Шанс на блок щитом
			if($defender["user"]["secondaryWeapon"]["thing"] == 6){
				$blockChance = 100 - (($agressor["user"]["Strengh"] + $agressor["user"]["Mastery"])/($defender["user"]["defence"] + $defender["user"]["Mastery"]) * 100);
				if(rand(0,100) <= $blockChance){
					$blockDamage = $defender["user"]["Defence"] * $defenceRand * $defender["user"]["secondaryWeapon"]["defence"];
					$Dmg -= $blockDamage;
					$typeHit = "shield";
				}
			}
			return array(round($Dmg,0),$typeHit, $blockDamage);
	}
	
	private function checkUpLvl($id){
		$user = $this->db->getAllOnField("users", "id", $id, "", "");
		//Если полученный опыт не влезает апнуть лвл
		if($user["currentExp"] >= $user["needExp"]){
			$newExp = $user["currentExp"] - $user["needExp"];
			$newLvl = $user["lvl"] + 1;
			$newNeedExp = round($user["needExp"] * 1.25,0);
			$table_name = $this->config->db_prefix."users";
			$query = "UPDATE $table_name SET `currentExp` = '$newExp', `needExp` = '$newNeedExp', `lvl` = $newLvl WHERE id = ".$user["id"];
			$this->query($query);
			
		//Изменить границы поиска противника на арене, елси там стоит минимальный лвл
		$winnerSettings = $this->db->getAllOnField("user_settings", "id", $user["id"], "", "");
		if($winnerSettings["minLvl"] < $user["lvl"] - 3)
			$this->db->setField("user_settings", "minLvl", $user["lvl"] - 3, "id", $user["id"]);
		}
	}
	
	private function getBonus($char, $lvl){
		$modificator = 1;
		for($i = 0; $i <= $lvl; $i++){
			$modificator += 0.05;
		}
		$result = round($char * $modificator, 2);
		return $result;
	}
	
	private function updateStatistic($userStatistic, $damageStatistic, $id){
		$this->db->update("user_statistic", array("userStatistic" => serialize($userStatistic), "damageStatistic" =>  serialize($damageStatistic)), "`id` = '".$id."'");
	}
}

$attackFunctions = new attackFunctions();
if($_REQUEST["WhatIMustDo"] == "attackUser")		$attackFunctions->attack($_REQUEST["type"], $_REQUEST["avatar"], $_REQUEST["id"]);
?>