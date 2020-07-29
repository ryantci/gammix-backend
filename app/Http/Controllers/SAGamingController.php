<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\SAHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class SAGamingController extends Controller
{

    // XML BUILD RECURSIVE FUNCTION
    public function siteMap()
    {
        $test_array = array (
            'bla' => 'blub',
            'foo' => 'bar',
            'another_array' => array (
                'stack' => 'overflow',
            ),
        );

        $xml_template_info = new \SimpleXMLElement("<?xml version=\"1.0\"?><template></template>");

        $this->array_to_xml($test_array,$xml_template_info);
        $xml_template_info->asXML(dirname(__FILE__)."/sitemap.xml") ;
        header('Content-type: text/xml');
        dd(readfile(dirname(__FILE__)."/sitemap.xml"));
    }

    public function array_to_xml(array $arr, \SimpleXMLElement $xml)
    {
      foreach ($arr as $k => $v) {
          is_array($v)
              ? $this->array_to_xml($v, $xml->addChild($k))
              : $xml->addChild($k, $v);
      }
      return $xml;
    }
   

    public function GetUserBalance(Request $request){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        // $data = json_encode($data);
        // $data = json_decode($data);

    	$user_id = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $data['username']);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);

    	$response = [
    		"username" => config('providerlinks.sagaming.prefix').$client_details->player_id,
    		"currency" => $client_details->default_currency,
    		"amount" => $player_details->playerdetailsresponse->balance,
    		"error" => 0,
    	];

        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><RequestResponse></RequestResponse>');
        $xml_file = $this->array_to_xml($response, $xml_data);
        echo $xml_file->asXML();


        // $xml = json_encode($response);
        // $xml = new \SimpleXMLElement(json_encode($response));
        // $xml = simplexml_load_string($xml);
        // return $xml;

        // return response($response)
        // ->withHeaders([
        //     'Content-Type' => 'text/xml'
        // ]);
        // return response()->xml($response, 200);
        // return Response::make($response, '200')->header('Content-Type', 'text/xml');

        // $content = view('response.xml', compact($response));
        // return response($content, 200)
        //     ->header('Content-Type', 'text/xml');
    	// return $response;
    }

    public function PlaceBet(){
    	Helper::saveLog('SA Place Bet', config('providerlinks.sagaming.pdbid'), json_encode(file_get_contents("php://input")), 'ENDPOINT HIT');
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);

        $username = $data['username'];
        $playersid = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
        $currency = $data['currency'];
        $amount = $data['amount'];
        $txnid = $data['txnid'];
        $ip = $data['ip'];
        $gametype = $data['gametype'];
        $game_id = $data['gameid'];
        $betdetails = $data['betdetails'];

        $client_details = ProviderHelper::getClientDetails('player_id',$playersid);
        if($client_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1000];
            return $data_response;
        }
        $getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
        if($getPlayer == 'false'){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 9999];  
            return $data_response;
        }
        $game_details = Helper::findGameDetails('game_code', config('providerlinks.sagaming.pdbid'), $game_id);
        if($game_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 134];  
            return $data_response;
        }
        $provider_reg_currency = ProviderHelper::getProviderCurrency(config('providerlinks.sagaming.pdbid'), $client_details->default_currency);
        $data_response = ["username" => $username,"currency" => $currency, "error" => 1001];
        if($provider_reg_currency == 'false'){ // currency not in the provider currency agreement
            return $data_response;
        }else{
            if($currency != $provider_reg_currency){
                return $data_response;
            }
        }

            $transaction_check = ProviderHelper::findGameExt($txnid, 1,'transaction_id');
            if($transaction_check != 'false'){
                $data_response = ["username" => $username,"currency" => $currency, "error" => 122];
                return $data_response;
            }

            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);
            $requesttosend = [
                  "access_token" => $client_details->client_access_token,
                  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                  "type" => "fundtransferrequest",
                  "datesent" => Helper::datesent(),
                  "gamedetails" => [
                    "gameid" => $game_details->game_code, // $game_details->game_code
                    "gamename" => $game_details->game_name
                  ],
                  "fundtransferrequest" => [
                      "playerinfo" => [
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $client_details->player_token,
                      ],
                      "fundinfo" => [
                              "gamesessionid" => "",
                              "transactiontype" => "debit",
                              "transferid" => "",
                              "rollback" => false,
                              "currencycode" => $client_details->default_currency,
                              "amount" => abs($amount)
                       ],
                  ],
            ];

            return $requesttosend;
            $guzzle_response = $client->post($client_details->fund_transfer_url,
                ['body' => json_encode($requesttosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());

            // TEST
            $transaction_type = 'debit';
            $game_transaction_type = 1; // 1 Bet, 2 Win
            $game_code = $game_details->game_id;
            $token_id = $client_details->token_id;
            $bet_amount = $amount; 
            $pay_amount = $amount;
            $income = $amount;
            $win_type = 0;
            $method = 1;
            $win_or_lost = $win_type; // 0 lost,  5 processing
            $payout_reason = 'Bet';
            $provider_trans_id = $transaction_uuid;
            // TEST

            $data_response = [
                "username" => $username,
                "currency" => $client_details->default_currency,
                "amount" => $client_response->fundtransferresponse->balance,
                "error" => 0
            ];

            $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
            $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $provider_trans_id, $pay_amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);

            return response($data_response,200)->header('Content-Type', 'application/json');
    }

    public function PlayerWin(){
    	Helper::saveLog('SA Player Win', config('providerlinks.sagaming.pdbid'), file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlayerLost(){
    	Helper::saveLog('SA Player Lost', config('providerlinks.sagaming.pdbid'), file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlaceBetCancel(){
    	Helper::saveLog('SA Place Bet Cancel', config('providerlinks.sagaming.pdbid'), file_get_contents("php://input"), 'ENDPOINT HIT');
    }

}