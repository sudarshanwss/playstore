<?php
/**
 * Author : Abhijth Shetty
 * Date   : 29-12-2017
 * Desc   : This is a controller file for userGetDetail Action
 */
class userLevelUpAction extends baseAction{
	/**
   * @OA\Get(path="?methodName=user.levelUp", tags={"Users"}, 
   * @OA\Parameter(parameter="applicationKey", name="applicationKey", description="The applicationKey specific to this event",
   *   @OA\Schema(type="string"), in="query", required=false),
   * @OA\Parameter(parameter="user_id", name="user_id", description="The user ID specific to this event",
   *   @OA\Schema(type="string"), in="query", required=false),
   * @OA\Parameter(parameter="access_token", name="access_token", description="The access_token specific to this event",
   *   @OA\Schema(type="string"), in="query", required=false),
   * @OA\Response(response="200", description="Success, Everything worked as expected"),
   * @OA\Response(response="201", description="api_method does not exists"),
   * @OA\Response(response="202", description="The requested version does not exists"),
   * @OA\Response(response="203", description="The requested request method does not exists"),
   * @OA\Response(response="204", description="The auth token is invalid"),
   * @OA\Response(response="205", description="Response code failure"),
   * @OA\Response(response="206", description="paramName should be a Valid email address"),
   * @OA\Response(response="216", description="Invalid Credential, Please try again."),
   * @OA\Response(response="228", description="error"),
   * @OA\Response(response="231", description="Device token is mandatory."),
   * @OA\Response(response="232", description="Custom Error"),
   * @OA\Response(response="245", description="Player is offline"),
   * @OA\Response(response="404", description="Not Found")
   * )
   */
  public function execute()
  {
    $userLib = autoload::loadLibrary('queryLib', 'user');
    $cardLib = autoload::loadLibrary('queryLib', 'card');
    $roomLib = autoload::loadLibrary('queryLib', 'room');
    $badgeLib = autoload::loadLibrary('queryLib', 'badge');
    $deckLib = autoload::loadLibrary('queryLib', 'deck');

    $result = $deckList = $temp = array();

    //User detail with cards list which is in use deck.
    $userDetail = $userLib->getUserDetail($this->userId);
    $userDeck = $deckLib->getUserDeckDetail($this->userId);
    if(!empty($userDeck)) {
      $deckData = json_decode($userDeck['deck_data'],true);
      $deckCards = formatArr($deckData['deck_details'], 'deck_id');
      $data = (array_column($deckCards[$deckData['current_deck_number']]['cards'], 'master_id'));
      $userCardList = $cardLib->getUserCardForCurrentDeck($this->userId, DECK_ACTIVE, implode(',',$data)); 
    } else {
      $userCardList = $cardLib->getUserCardForActiveDeck($this->userId, DECK_ACTIVE); 
    }
    

    $result['name'] = $userDetail['name'];
    $result['total_wins'] = $userDetail['total_wins'];
     $result['total_match'] = $userDetail['total_match'];
     $result['total_winrate'] = (!empty($userDetail['total_match'])||$userDetail['total_match']>0)?(($userDetail['total_wins']/$userDetail['total_match'])*100):0;
    $result['level_id'] = $userDetail['level_id'];
    $result['total_circlet'] = $userDetail['circlet'];
    $result['total_relic'] = $userDetail['relics'];
    $result['total_crystal'] = $userDetail['crystal'];
    $result['total_gold'] = $userDetail['gold'];
    $result['xp'] = $userDetail['xp'];
    $result['facebook_id'] = $userDetail['facebook_id'];
    $result['google_id'] = $userDetail['google_id'];
    $result['game_center_id'] = $userDetail['game_center_id'];
    $result['master_stadium_id'] = $userDetail['master_stadium_id'];
    $result['god_tower_health'] = $userDetail['god_tower_health'];
    $result['stadium_tower_health'] = $userDetail['stadium_tower_health'];
    $result['god_tower_damage'] = $userDetail['god_tower_damage'];
    $result['stadium_tower_damage'] = $userDetail['stadium_tower_damage'];

    foreach ($userCardList as $card)
    {
      $cardPropertyInfo = $temp = array();
      $temp['user_card_id'] = $card['user_card_id'];
      $temp['master_card_id'] = $card['master_card_id'];
      $temp['title'] = $card['title'];
      $temp['card_type'] = $card['card_type'];
      $temp['card_type_message'] = ($card['card_type'] == CARD_TYPE_TROOP)?"Troop":(($card['card_type'] == CARD_TYPE_SPELL)?"Spell":"Building");
      $temp['card_rarity_type'] = $card['card_rarity_type'];
      $temp['rarity_type_message'] = ($card['card_rarity_type'] == CARD_RARITY_COMMON)?"Common":(($card['card_rarity_type'] == CARD_RARITY_RARE)?"Rare":(($card['card_rarity_type'] == CARD_RARITY_EPIC)?"Epic":"Ultra Epic"));
      $temp['is_deck_message'] = ($card['is_deck'] == CONTENT_ACTIVE)?"in deck":"not in deck";
      $temp['is_deck'] = $card['is_deck'];
      $cardLevelUpDetail = $cardLib->getMasterCardLevelUpgradeForCardCount($card['level_id']+1, $card['card_rarity_type']);
      $temp['next_level_card_count'] = $cardLevelUpDetail['card_count'];
      $temp['next_level_gold_cost'] = $cardLevelUpDetail['gold'];
      $temp['total_card'] = $card['user_card_count'];
      $temp['card_level'] = $card['level_id'];
      $temp['card_description'] = $card['card_description'];

      $cardPropertyList = $cardLib->getCardPropertyForUseCardId($card['user_card_id']);
      foreach($cardPropertyList as $cardProperty)
      {
        $tempProperty = array();
        if($cardProperty['is_default'] == true){
          $temp[$cardProperty['property_id']] = $cardProperty['user_card_property_value'];
        } else
        {
          $tempProperty['property_id'] = $cardProperty['property_id'];
          $tempProperty['property_name'] = $cardProperty['property_name'];
          $tempProperty['property_value'] = $cardProperty['user_card_property_value'];
          $cardPropertyInfo[] = $tempProperty;
        }
      }
      $temp['property_list'] = $cardPropertyInfo;
      $deckList[] = $temp;
    }
    //print_log("test completed");
    $result['deck_list'] = $deckList;
    $result['notification_status'] = $userDetail['notification_status'];
    $result['notification_status_message'] = "1-Active; 0-inActive";
    $result['is_tutorial_completed'] = $userDetail['is_tutorial_completed'];
    $result['editname_count'] = $userDetail['editname_count'];
    $result['android_appversion'] = "v_1.0";
    $result['IOS_appversion'] = "v_1.0";
    $result['updatedurl'] = "url";
    $result['maintainanceon'] = false;

    //settype($result['android_appversion'], "float");
    //settype($result['IOS_appversion'], "float");
    //settype($result['updatedurl'], "url");
    

    $dailyCrystal = $userLib->getUserDailyAdReward($this->userId, date('Y-m-d'));

    $result['is_rewarded_ad_shown'] = !empty($dailyCrystal)?CONTENT_ACTIVE:CONTENT_INACTIVE;
    $winStreak = $roomLib->getUserWinStreak($this->userId);
    $result['win_streak'] = empty($winStreak['win_streak'])?0:$winStreak['win_streak'];

    $latestBadge = $badgeLib->getUserLatestBadge($this->userId);
    $result['current_badge'] = empty($latestBadge['master_badge_id'])?0:$latestBadge['master_badge_id'];

    //dev reference, we can remove going frwd
    $result['android_push_token'] = $userDetail['android_push_token'];
    $result['ios_push_token'] = $userDetail['ios_push_token'];

    $this->setResponse('SUCCESS');
    return $result;
  }
}
