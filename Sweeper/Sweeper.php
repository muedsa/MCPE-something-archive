<?php
/*
__PocketMine Plugin__
name=Sweeper
description=Sweeper-查水表插件组-箱子与背包版
version=0.3
author=MUedsa
class=Sweeper
apiversion=12,13
*/
class Sweeper implements Plugin{
      private $api;

   	public function __construct(ServerAPI $api, $server = false){
     	$this->api = $api;
   	}

   	public function init(){
   		date_default_timezone_set("Asia/Shanghai");
   		$this->path = $this->api->plugin->configPath($this);
   		$this->config = new Config($this->path."config.yml", CONFIG_YAML, array());
         $this->api->console->register("sw","查看某世界里的所有箱子的物品", array($this, "SearchWorld"));
   		$this->api->console->register("cw","清理你所在世界的箱子的物品", array($this, "CleanWorld"));
         $this->api->console->register("si","查看玩家的背包", array($this, "SearchInventory"));
         $this->api->console->register("ri","移除玩家的背包物品", array($this, "RemoveInventory"));
         console(FORMAT_RED."\t[Sweeper] ".FORMAT_YELLOW."查水表插件-箱子与背包版\n\t\t\t\t".FORMAT_GREEN."作者:".FORMAT_RED."MUedsa\n\t\t\t\t".FORMAT_GREEN."QQ:".FORMAT_RED."471215557\n");
   	}
      
      public function SearchWorld($cmd, $args, $issuer){
         $output = "";
         if(!isset($args[0])){
            $output .= "[SWP] 查看某世界里的所有箱子的物品 , 用法 : /sw < 世界名 >";
         }else{
            $level = $this->api->level->get($args[0]);
            if($level){
               $output .= "[SWP] 开始搜索整个 <".$args[0]."> 世界 , 找到物品:\n";
               $tiles = $this->api->tile->getAll($level);
               foreach ($tiles as $tile){
                  if($tile->class=== "Chest"){
                     foreach ($tile->data["Items"] as $item){
                        if($item["Damage"] == 0){
                           if(isset($itemlist[$item["id"]])){
                              $itemlist[$item["id"]] = $itemlist[$item["id"]] + $item["Count"];
                           }else{
                              $itemlist[$item["id"]] = $item["Count"];
                           }
                        }else{
                           if(isset($itemlist[$item["id"].":".$item["Damage"]])){
                              $itemlist[$item["id"].":".$item["Damage"]] = $itemlist[$item["id"].":".$item["Damage"]] + $item["Count"];
                           }else{
                              $itemlist[$item["id"].":".$item["Damage"]] = $item["Count"];
                           }
                        }
                     }
                  }
               }
               $i = 1;
               foreach ($itemlist as $mid => $count){
                  $name = $this->getname($mid);
                  $output .= "ID=".$mid." ".$name." *".$count." | ";
                  $n = $i % 4;
                     if($n == 0){
                        $output .= "\n";
                     }
                  $i = $i + 1;
               }
               unset($level);
               unset($tiles);
               unset($tile);
               unset($itemlist);
            }else{
               $output .= "[SWP] 请输入正确的世界名字\n[SWP]查看某世界里的所有箱子的物品 , 用法 : /sw < 世界名 >";
            }
         }
         return $output;
      }

   	public function CleanWorld($cmd, $args, $issuer){
   		$output = "";
   		if(!($issuer instanceof Player)){
			   $output .= "[SWP] 请在游戏里运行此命令.";
			   return $output;
		   }
   		if(is_null($args[0])){
   			$level = $issuer->entity->level;
   			$levelname = $level->getName();
			   $tiles = $this->api->tile->getAll($level);
			   $time = date("Y-m-d-H-i-s");
			   $output .= "[SWP] ".$time."开始移除地图 : ".$levelname." 所有箱子里面的东西\n";
   			foreach ($tiles as $tile){
   				if($tile->class=== "Chest"){
   					$filedata[] = array(
   						"id" => $tile->id,
   						"x" => $tile->x,
   						"y" => $tile->y,
   						"z" => $tile->z,
   						"data" => $tile->data,
   						);
   					$this->api->tile->remove($tile->id);
   					$output .= "[SWP] 移除 id=".$tile->id." x=".$tile->x." ,y=".$tile->y." ,z=".$tile->z." 的箱子里全部东西\n";
   					$tile->data["Items"] = array();
   					$this->api->tile->add($level, $class = "Chest", $tile->x, $tile->y, $tile->z, $data = $tile->data);
   				}
   			}
   			if(!is_null($filedata)){
				   $chestnew = new Config($this->path."Chest-".$levelname."-".$time.".yml", CONFIG_YAML, $filedata);
				   $output .= "[SWP] 创建备份 : ".$this->path."Chest-".$levelname."-".$time.".yml";
			   }else{
				  $output .= "[SWP] 没有箱子被清空";
			   }
            unset($level);
            unset($tiles);
            unset($tile);
            unset($filedata);
   		}elseif(is_numeric($args[0])){
   			$level = $issuer->entity->level;
   			$levelname = $level->getName();
			   $tiles = $this->api->tile->getAll($level);
			   $time = date('Y-m-d-H-i-s');
			   $output .= "[SWP] ".$time."开始移除地图 : ".$levelname." 所有箱子里面 ID=".$args[0]." 的物品\n";
			   foreach ($tiles as $tile){
				  if($tile->class=== "Chest"){
					   $isdel = false;
                  $olddata = $tile->data;
					   foreach ($tile->data["Items"] as $key => $item){
						   if($item["id"] == $args[0]){
							  $isdel = true;
							  unset($tile->data["Items"][$key]);
						   }
						   if($isdel){
                        $filedata[] = array(
                           "id" => $tile->id,
                           "x" => $tile->x,
                           "y" => $tile->y,
                           "z" => $tile->z,
                           "data" => $olddata,
                           );
							   $this->api->tile->remove($tile->id);
   						   $output .= "[SWP] 移除 id=".$tile->id." x=".$tile->x." ,y=".$tile->y." ,z=".$tile->z." 的箱子里 ID=".$args[0]." 的物品\n";
   						   $this->api->tile->add($level, $class = "Chest", $tile->x, $tile->y, $tile->z, $data = $tile->data);
						   }
					   }
				   }
			   }
			   if(!is_null($filedata)){
				  $chestnew = new Config($this->path."Chest-".$levelname."-ID=".$args[0]."-".$time.".yml", CONFIG_YAML, $filedata);
				  $output .= "[SWP] 创建备份 : ".$this->path."Chest-".$levelname."-ID=".$args[0]."-".$time.".yml";
			   }else{
				  $output .= "[SWP] 没有箱子被清理";
			   }
            unset($level);
            unset($tiles);
            unset($tile);
            unset($filedata);
   		}else{
   			$output .= "[SWP] /cw (ID) 清空你所在地图所以箱子里指定ID的物品 , 留空则清空全部物品 !";
   		}
   		return $output;
   	}

      public function SearchInventory($cmd, $args, $issuer){
         $output = "";
         if(isset($args[0])){
            $args[0] = strtolower($args[0]);
            $player = $this->api->player->get($args[0]);
            if($player instanceof Player){
               //玩家在线
               if($player->gamemode == CREATIVE){
                  return "[SWP] 玩家 : ".$args[0]."( 在线 ) 为创造模式 !";
               }
               foreach($player->inventory as $item) {
                  if($item->getID() != 0){
                     if($item->getMetadata() == 0){
                        $mid = $item->getID();
                     }else{
                        $mid = $item->getID().":".$item->getMetadata();
                     }
                     if(!isset($itemlist[$mid])){
                        $itemlist[$mid] = $item->count;
                     }else{
                        $itemlist[$mid] = $itemlist[$mid] + $item->count;
                     }
                  }
               }
               $i = 1;
               $output .= "[SWP] 查看玩家 : ".$args[0]."( 在线 ) 的背包物品 ===>\n";
               foreach($itemlist as $key => $num){
                  $name = $this->getname($key);
                  $output .= "ID=".$key." ".$name." *".$num." | ";
                  $n = $i % 4;
                  if($n == 0){
                     $output .= "\n";
                  }
                  $i = $i + 1;
               }
               unset($player);
               unset($itemlist);
            }else{
               //玩家不在线
               if(file_exists(DATA_PATH."players/".$args[0].".yml")){
                  $data = $this->api->plugin->readYAML(DATA_PATH."players/".$args[0].".yml");
                  if($data["gamemode"] == CREATIVE){
                     return "[SWP] 玩家 : ".$args[0]."( 离线 ) 为创造模式 !";
                  }
                  $output .= "[SWP] 查看玩家 : ".$args[0]."( 离线 ) 的背包物品 ===>\n";
                  $i = 1;
                  foreach($data["inventory"] as $item){
                     if($item[0] != 0){
                        if($item[1] != 0){
                           $mid = $item[0].":".$item[1];
                        }else{
                           $mid = $item[0];
                        }
                        $name = $this->getname($mid);
                        $output .= "ID=".$mid." ".$name." *".$item[2]." | ";
                        $n = $i % 4;
                        if($n == 0){
                           $output .= "\n";
                        }
                        $i = $i + 1;
                     }
                  }
                  unset($data);
               }else{
                  $output .= "[SWP] 玩家不存在";
               }
            }
         }else{
            //$args[0] 为空
            $output .= "[SWP] 查看玩家的背包 , 用法 : /si < 玩家名 >";
         }
         return $output;
      }

      public function RemoveInventory($cmd, $args, $issuer){
            $output = "";
            if(isset($args[0]) AND isset($args[1])){
				$args[0] = strtolower($args[0]);
				$id = strtok($args[1], ":");
				$meta = strtok(":");
            $id = (int) $id;
				$player = $this->api->player->get($args[0]);
				if($player instanceof Player){
				//玩家在线
					if($player->gamemode == CREATIVE){
						return "[SWP] 玩家 : ".$args[0]."( 在线 ) 为创造模式 !";
					}
					foreach($player->inventory as $item) {
						if($item->getID() != 0){
							if($item->getMetadata() == 0){
								$mid = $item->getID();
							}else{
								$mid = $item->getID().":".$item->getMetadata();
							}
							if(!isset($itemlist[$mid])){
								$itemlist[$mid] = $item->count;
							}else{
								$itemlist[$mid] = $itemlist[$mid] + $item->count;
							}
						}
					}
					if(is_numeric($args[2])){
						$count = (int) $args[2];
					}else{
						$count = (int) $itemlist[$args[1]];
					}
               if(isset($itemlist[$args[1]]) AND $itemlist[$args[1]] >= $count){
                  if(!$meta){
                     $meta = 0;
                  }else{
                     $meta = (int) $meta;
                  }
                  $re = $player->removeItem($id,$meta,$count);
                  $name = $this->getname($args[1]);
                  if($re) {
                     $output .= "[SWP] 移除玩家 ".$args[0]."( 在线 ) 的背包物品 ID=".$args[1]." 的 ".$name." ".$count."个 成功";
                  }else{
                     $output .= "[SWP] 移除玩家 ".$args[0]."( 在线 ) 的背包物品 ID=".$args[1]." 的 ".$name." ".$count."个 出现未知错误 !";
                  }
               }else{
                  $output .= "[SWP] 移除玩家 ".$args[0]."( 在线 ) 的背包物品 ID=".$args[1]." 的 ".$name." ".$count."个 失败\n[SWP]该玩家没有这么多物品 !";
               }
               unset($data);
               unset($itemlist);
            }else{
                  //玩家离线
                  if(file_exists(DATA_PATH."players/".$args[0].".yml")){
                     $data = $this->api->plugin->readYAML(DATA_PATH."players/".$args[0].".yml");
                     if($data["gamemode"] == CREATIVE){
                        return "[SWP] 玩家 : ".$args[0]."( 离线 ) 为创造模式 !";
                     }else{
                        foreach($data["inventory"] as $item){
                           if($item[0] != 0){
                              if($item[1] != 0){
                                 $mid = $item[0].":".$item[1];
                              }else{
                                 $mid = $item[0];
                              }
                              if(isset($items[$mid])){
                                 $items[$mid] = $items[$mid] + $item[2];
                              }else{
                                 $items[$mid] = $item[2];
                              }
                           }
                        }
                        if(isset($items[$args[1]])){
                           if(is_numeric($args[2])){
                              $count = $args[2];
                           }else{
                              $count = $items[$args[1]];
                           }
                           if($items[$args[1]] >= $count){
                              if(is_null($meta)){
                                 $meta = 0;
                              }
                              foreach($data["inventory"] as $key => $itemm){
                                 if($itemm[0] == $id AND $itemm[1] == $meta){
                                    if($itemm[2] > $count){
                                       $data["inventory"][$key][2] = $itemm[2] - $count;
                                       $count = 0;
                                       break;
                                    }else{
                                       $data["inventory"][$key] = array(0,0,0);
                                       $count = $count - $itemm[2];
                                    }
                                 }
                              }
                              $name = $this->getname($args[1]);
                              $this->api->plugin->writeYAML(DATA_PATH."players/".$args[0].".yml", $data);
                              $output .= "[SWP] 移除玩家 ".$args[0]."( 离线 ) 的背包物品 ID=".$args[1]." 的 ".$name." ".$args[2]."个 成功";
                           }else{
                              $name = $this->getname($args[1]);
                              $output .= "[SWP] 玩家没有这么多 ID=".$args[1]." 的 ".$name." ( 物品 ) , 只有 ".$items[$args[1]]." 个!";
                           }
                        }else{
                           $output .= "[SWP] 玩家没有 ID=".$args[1]." 的物品 !";
                        }
                     }
                     unset($data);
                     unset($items);
                  }else{
                     $output .= "[SWP] 玩家不存在 !";
                  }

               }
            }else{
               $output .= "[SWP] 移除玩家的背包物品 , 用法 : /ri < 玩家名 > < 物品 ID > ( 物品数量/不填为移除全部 )";
            }
         return $output;
      }
      
      public function getname($mid){
         if(isset($this->items[$mid])){
            return $this->items[$mid];
         }else{
            $mid = strtok($mid, ":");
            if(isset($this->items[$mid])){
               return $this->items[$mid]."=>".strtok(":");
            }else{
               return null;
            }
         }
      }

   public function __destruct(){}

   private $items = array(
      "0" => "空气",
      "1" => "石头",
      "2" => "草块",
      "3" => "泥土",
      "4" => "圆石",
      "5" => "木板",
      "6" => "橡树苗",
      "6:1" => "云杉树苗",
      "6:2" => "桦树苗",
      "7" => "基岩",
      "8" => "水",
      "9" => "静止水",
      "10" => "岩浆",
      "11" => "静止岩浆",
      "12" => "沙子",
      "13" => "沙烁",
      "14" => "金矿",
      "15" => "铁矿",
      "16" => "煤矿",
      "17" => "木头",
      "17:1" => "松木",
      "17:2" => "桦木",
      "18" => "树叶",
      "18:1" => "松树叶",
      "18:2" => "桦树叶",
      "19" => "海绵",
      "20" => "玻璃",
      "21" => "青金矿石",
      "22" => "青金石",
      "24" => "沙石",
      "24:1" => "錾制沙石",
      "24:2" => "平滑沙石",
      "26" => "床方块",
      "27" => "动力铁轨",
      "30" => "蜘蛛网",
      "31" => "枯死灌木",
      "35" => "白羊毛",
      "35:1" => "橙羊毛",
      "35:2" => "品红羊毛",
      "35:3" => "淡蓝羊毛",
      "35:4" => "黄羊毛",
      "35:5" => "黄绿羊毛",
      "35:6" => "粉红羊毛",
      "35:7" => "灰羊毛",
      "35:8" => "淡灰羊毛",
      "35:9" => "青色羊毛",
      "35:10" => "紫羊毛",
      "35:11" => "蓝羊毛",
      "35:12" => "棕色羊毛",
      "35:13" => "绿羊毛",
      "35:14" => "红羊毛",
      "35:15" => "黑羊毛",
      "37" => "黄色花",
      "38" => "青色花",
      "39" => "棕蘑菇",
      "40" => "红蘑菇",
      "41" => "金块",
      "42" => "铁块",
      "43" => "双石台阶",
      "43:1" => "双沙石台阶",
      "43:2" => "双木台阶",
      "43:3" => "双圆石台阶",
      "43:4" => "双砖台阶",
      "43:6" => "双石台阶",
      "44" => "石台阶",
      "44:1" => "沙石台阶",
      "44:2" => "木台阶",
      "44:3" => "圆石台阶",
      "44:4" => "红砖台阶",
      "44:5" => "石砖台阶",
      "44:6" => "石台阶",
      "44:7" => "石英台阶",
      "45" => "砖块",
      "46" => "TNT",
      "47" => "书架",
      "48" => "苔石",
      "49" => "黑曜石",
      "50" => "火把",
      "51" => "火",
      "53" => "木楼梯",
      "54" => "箱子",
      "56" => "钻石矿",
      "57" => "钻石块",
      "58" => "工作台",
      "59" => "小麦种子方块",
      "60" => "耕地",
      "61" => "熔炉",
      "62" => "燃烧中熔炉",
      "63" => "告示牌方块",
      "64" => "木门方块",
      "65" => "梯子",
      "66" => "铁轨",
      "67" => "石楼梯",
      "68" => "墙上告示牌",
      "71" => "铁门方块",
      "73" => "红石矿",
      "74" => "发光红石矿",
      "78" => "雪",
      "79" => "冰",
      "80" => "雪块",
      "81" => "仙人掌",
      "82" => "粘土块",
      "85" => "栅栏",
      "86" => "南瓜",
      "87" => "地狱岩",
      "89" => "荧石",
      "91" => "南瓜灯",
      "92" => "蛋糕方块",
      "95" => "隐形基岩",
      "96" => "活板门",
      "98" => "石砖",
      "98:1" => "苔石砖",
      "98:2" => "裂石砖",
      "101" => "铁栏杆",
      "102" => "玻璃板",
      "103" => "西瓜",
      "104" => "西瓜梗",
      "105" => "西瓜梗",
      "107" => "栅栏门",
      "108" => "砖楼梯",
      "109" => "石砖楼梯",
      "112" => "地狱砖方块",
      "114" => "地狱砖楼梯",
      "126" => "蛋糕",
      "128" => "沙石楼梯",
      "142" => "马铃薯",
      "155" => "石英方块",
      "155:1" => "花纹石英块",
      "155:2" => "柱石英方块",
      "156" => "石英楼梯",
      "157" => "双木台阶",
      "170" => "干草块",
      "171" => "地毯",
      "173" => "煤块",
      "244" => "甜菜",
      "245" => "切石机",
      "246" => "发光黑曜石",
      "247" => "下界反应核",
      "248" => "更新块1",
      "249" => "更新块2",
      "253" => "故障草",
      "254" => "故障叶",
      "255" => "故障石",
      "256" => "铁锹",
      "257" => "铁镐",
      "258" => "铁斧",
      "259" => "剪刀",
      "260" => "红苹果",
      "261" => "弓",
      "262" => "箭",
      "263" => "煤炭",
      "263:1" => "木炭",
      "264" => "钻石",
      "265" => "铁锭",
      "266" => "金锭",
      "267" => "铁剑",
      "268" => "木剑",
      "269" => "木锹",
      "270" => "木镐",
      "271" => "木斧",
      "272" => "石剑",
      "273" => "石锹",
      "274" => "石镐",
      "275" => "石斧",
      "276" => "钻石剑",
      "277" => "钻石锹",
      "278" => "钻石镐",
      "279" => "钻石斧",
      "280" => "木棍",
      "281" => "碗",
      "282" => "蘑菇汤",
      "283" => "金剑",
      "284" => "金锹",
      "285" => "金镐",
      "286" => "金斧",
      "287" => "线",
      "288" => "羽毛",
      "289" => "火药",
      "290" => "木锄",
      "291" => "石锄",
      "292" => "铁锄",
      "293" => "钻石锄",
      "294" => "金锄",
      "295" => "小麦种子",
      "296" => "小麦",
      "297" => "面包",
      "298" => "皮革帽子",
      "299" => "皮革外套",
      "300" => "皮革裤子",
      "301" => "皮革靴子",
      "302" => "锁链头盔",
      "303" => "锁链胸甲",
      "304" => "锁链护腿",
      "305" => "锁链靴子",
      "306" => "铁头盔",
      "307" => "铁胸甲",
      "308" => "铁护腿",
      "309" => "铁靴子",
      "310" => "钻石头盔",
      "311" => "钻石胸甲",
      "312" => "钻石护腿",
      "313" => "钻石靴子",
      "314" => "金头盔",
      "315" => "金胸甲",
      "316" => "金护腿",
      "317" => "金靴子",
      "318" => "燧石",
      "319" => "生猪排",
      "320" => "熟猪排",
      "321" => "画",
      "323" => "告示牌",
      "324" => "木门",
      "325" => "桶",
      "325:1" => "牛奶桶",
      "325:8" => "水桶",
      "325:10" => "岩浆桶",
      "326" => "水桶",
      "328" => "矿车",
      "329" => "鞍",
      "330" => "铁门",
      "331" => "红石",
      "332" => "雪球",
      "334" => "皮革",
      "336" => "红砖",
      "337" => "粘土",
      "338" => "甘蔗",
      "339" => "纸",
      "340" => "书",
      "341" => "粘液球",
      "344" => "鸡蛋",
      "345" => "指南针",
      "347" => "钟",
      "348" => "荧石粉",
      "351" => "墨囊染料",
      "351:1" => "玫瑰红染料",
      "351:2" => "仙人绿染料",
      "351:3" => "可可豆染料",
      "351:4" => "金青石染料",
      "351:5" => "紫染料",
      "351:6" => "青染料",
      "351:7" => "淡灰染料",
      "351:8" => "灰染料",
      "351:9" => "粉红染料",
      "351:10" => "黄绿染料",
      "351:11" => "黄染料",
      "351:12" => "淡蓝染料",
      "351:13" => "品红染料",
      "351:14" => "橙染料",
      "351:15" => "骨粉",
      "352" => "骨头",
      "353" => "蔗糖",
      "354" => "蛋糕",
      "355" => "床",
      "359" => "剪刀",
      "360" => "西瓜片",
      "361" => "南瓜种子",
      "362" => "西瓜种子",
      "363" => "生牛肉",
      "364" => "牛排",
      "365" => "生鸡肉",
      "366" => "熟鸡肉",
      "391" => "胡萝卜",
      "392" => "土豆",
      "393" => "烤土豆",
      "400" => "南瓜饼",
      "405" => "地狱砖",
      "406" => "地狱石英",
      "456" => "照相机",
      "457" => "甜菜根",
      "458" => "甜菜种子",
      "459" => "甜菜汤",
  );

}