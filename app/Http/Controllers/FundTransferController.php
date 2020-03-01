<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Models\PlayerWallet;
use App\Helpers\Helper;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use DB;

class FundTransferController extends Controller
{
    public function __construct(){

		$this->middleware('oauth', ['except' => ['index']]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function process(Request $request) {
		$json_data = json_decode(file_get_contents("php://input"), true);

		$arr_result = [
						"fundtransferresponse" => [
							"status" => [
								"success" => false,
								"message" => "Insufficient balance.",
							],
								"balance" => false,
								"currencycode" => false
							]
						];

		if(empty($json_data) || count($json_data) == 0 || sizeof($json_data) == 0) {
			$arr_result["fundtransferresponse"]["status"]["message"] = "Request body is empty.";
		}
		else
		{
			$hash_key = $json_data["hashkey"];
			$access_token = $json_data["access_token"];	
			
			if(!Helper::auth_key($hash_key, $access_token)) {
				$arr_result["fundtransferresponse"]["status"]["message"] = "Authentication mismatched.";
			}
			else
			{
				if($json_data["type"] != "fundtransferrequest") {
					$arr_result["fundtransferresponse"]["status"]["message"] = "Invalid request.";
				}
				else
				{
					$token = $json_data["fundtransferrequest"]["playerinfo"]["token"];
					$amount = $json_data["fundtransferrequest"]["fundinfo"]["amount"];
					
					$player_details = PlayerSessionToken::select("player_id")->where("token", $token)->first();

					if (!$player_details) {
						$arr_result["fundtransferresponse"]["status"]["message"] = "Player token is expired.";
					}
					else
					{
						$player_session_token = $json_data["fundtransferrequest"]["playerinfo"]["token"];

						$client_details = DB::table("clients")
										 ->leftJoin("player_session_tokens", "clients.id", "=", "player_session_tokens.client_id")
										 ->leftJoin("client_endpoints", "clients.id", "=", "client_endpoints.client_id")
										 ->leftJoin("client_access_tokens", "clients.id", "=", "client_access_tokens.client_id")
										 ->where("player_session_tokens.token", $player_session_token)
										 ->first();

						if (!$client_details) {
							$arr_result["fundtransferresponse"]["status"]["message"] = "Invalid Endpoint.";
						}
						else
						{
							$client = new Client([
							    'headers' => [ 'Content-Type' => 'application/json' ]
							]);
							
								$response = $client->post($client_details->fund_transfer_url,
							    ['body' => json_encode(
							        	[
										  "access_token" => $client_details->token,
										  "hashkey" => md5($client_details->api_key.$client_details->token),
										  "type" => $json_data["type"],
										  "datetsent" => $json_data["datesent"],
										  "gamedetails" => [
										    "gameid" => $json_data["gamedetails"]["gameid"],
										    "gamename" => $json_data["gamedetails"]["gamename"]
										  ],
										  "fundtransferrequest" => [
												"playerinfo" => [
												"token" => $json_data["fundtransferrequest"]["playerinfo"]["token"]
											],
											"fundinfo" => [
											      "gamesessionid" => $json_data["fundtransferrequest"]["fundinfo"]["gamesessionid"],
											      "transactiontype" => $json_data["fundtransferrequest"]["fundinfo"]["transactiontype"],
											      "transferid" => $json_data["fundtransferrequest"]["fundinfo"]["transferid"],
											      "currencycode" => $json_data["fundtransferrequest"]["fundinfo"]["currencycode"],
											      "amount" => $json_data["fundtransferrequest"]["fundinfo"]["amount"]
											]
										  ]
										]
							    )]
							);

							return var_export($response->getBody()->getContents(), true);
						}
					}
				}
			}
		}
		

		echo json_encode($arr_result);
	}


}