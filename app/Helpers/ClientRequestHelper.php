<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class ClientRequestHelper{
    
    public static function getTransactionId($provider_trans_id,$roundId){
        $transaction = DB::table("game_transactions")
                    ->where("provider_trans_id",$provider_trans_id)
                    ->where("round_id",$roundId)->first();
        $transaction_ext = DB::table("game_transaction_ext")->latest()->first();
        $data = array(
            "transferId" => $transaction_ext->game_trans_ext_id + 1,
            "roundId" => $transaction
        );
        return $data;
    }


}