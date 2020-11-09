<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class ClientBOController extends Controller
{
    public function subProviderSettings(Request $request){
        
        DB::table('seamless_request_logs')->insert([
            "method_name" => "sub provider settings",
            "provider_id" => "000",
            "request_data" => json_encode($request->all()),
            "response_data" => "a"
        ]);
        if($request->enable == 'on'){
<<<<<<< HEAD
            $del = DB::table('excluded_sub_provider')->where('esp_id','=',$request->esp_id)->delete();
=======
            $del = DB::table('excluded_sub_provider')->where('cgs_id','=',$request->cgs_id)->where('sub_provider_id','=',$request->sub_provider_id)->delete();
>>>>>>> 681a9f73894c76e3205ea99001575a082be94b64
        }elseif($request->enable == 'off'){
            if($request->target_table == 'client_game_subscribe'){
                $insert = DB::table('client_game_subscribe')->insert(["client_id"=>$request->client_id,"provider_selection_type" => "all", "status_id"=>"1"]);
            }
            if($request->target_table == 'excluded_sub_provider'){
<<<<<<< HEAD
                $add_game =  DB::table('excluded_sub_provider')->insert([ 'esp_id' => $request->esp_id, 'cgs_id' => $request->cgs_id, 'sub_provider_id' => $request->sub_provider_id ]);
=======
                $add_game =  DB::table('excluded_sub_provider')->insert([  'cgs_id' => $request->cgs_id, 'sub_provider_id' => $request->sub_provider_id ]);
>>>>>>> 681a9f73894c76e3205ea99001575a082be94b64
            }
        }   
    }

    public function gameSettings(Request $request){
        DB::table('seamless_request_logs')->insert([
            "method_name" => "game settings",
            "provider_id" => "000",
            "request_data" => json_encode($request->all()),
            "response_data" => "a"
        ]);
        if($request->enable == 'on'){
<<<<<<< HEAD
            $del = DB::table("game_exclude")->where('ge_id','=',$request->ge_id)->delete();
=======
            $del = DB::table("game_exclude")->where('cgs_id','=',$request->cgs_id)->where('game_id','=',$request->game_id)->delete();
>>>>>>> 681a9f73894c76e3205ea99001575a082be94b64
        }elseif($request->enable == 'off'){
            if($request->target_table == 'client_game_subscribe'){
                $insert = DB::table('client_game_subscribe')->insert(["client_id"=>$request->client_id,"provider_selection_type" => "all", "status_id"=>"1"]);
            }
            if($request->target_table == 'game_exclude'){
<<<<<<< HEAD
                $add_game =  DB::table('game_exclude')->insert([ 'ge_id' => $request->ge_id, 'cgs_id' => $request->cgs_id, 'game_id' => $request->game_id ]);
=======
                $add_game =  DB::table('game_exclude')->insert([  'cgs_id' => $request->cgs_id, 'game_id' => $request->game_id ]);
>>>>>>> 681a9f73894c76e3205ea99001575a082be94b64
            }
            
        }
    }

    public function clientSettings(Request $request){
        DB::table('seamless_request_logs')->insert([
            "method_name" => "clients settings",
            "provider_id" => "000",
            "request_data" => json_encode($request->all()),
            "response_data" => "a"
        ]);
        $clients = DB::table('clients')->where('client_id','=',$request->client_id)
						->update([
							'timezone' =>	$request->timezone ,
							'client_name' => $request->client_name,
							'client_code' => $request->client_code,
							'default_language' => $request->default_language,
						]);
    }
}
