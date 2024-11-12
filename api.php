<?php
 
function get($k){
	if(isset($_GET[$k])){
		return $_GET[$k];
	}
	return "";
}
function post($k){
	if(isset($_POST[$k])){
		return $_POST[$k];
	}
	return "";
}

require "sunoApi.php";

sunoApi::$cookie=file_get_contents("./serviceid/1.txt");
$token=sunoApi::getToken("1",$cookie);
$action=get('action');
switch($action){
	case "feed":
		$ids=get("ids");
		$list=sunoApi::feed($ids);
		if(empty($list)){
			echo json_encode([
				"error"=>1,
				"message"=>"获取歌曲出错",
				 
			]);
			
		}else{
			echo json_encode([
				"error"=>0,
				"message"=>"success",
				"data"=>[
					"list"=>$list
				]
			]);
			
		}
		
		break;
	case "createSong":
		$prompt=post('prompt');
		$isCustom=post("isCustom");
		$tags=post("tags");
		$title=post("title");
		$make_instrumental=post("make_instrumental");
		$ops=[
			"prompt"=>$prompt,
			"model"=>"chirp-v3.5",
			"isCustom"=>$isCustom,
			"tags"=>$tags,
			"title"=>$title,
			"make_instrumental"=>$make_instrumental
		];
		$ids=sunoApi::generateSongs($ops);
		if(empty($ids)){
			echo json_encode([
				"error"=>1,
				"message"=>"生成歌曲出错",
				 
			]);
			
		}else{
			echo json_encode([
				"error"=>0,
				"message"=>"success",
				"data"=>[
					"ids"=>$ids
				]
			]);
			
		}
		break;
	case "get_credits":
		$data=sunoApi::get_credits();
		if(empty($data)){
			echo json_encode([
				"error"=>1,
				"message"=>"获取会员信息出错",
				 
			]);
			
		}else{
			echo json_encode([
				"error"=>0,
				"message"=>"success",
				"data"=>[
					"data"=>$data
				]
			]);
			
		}
		break;
	default:
		echo json_encode([
			"error"=>0,
			"message"=>"Hi suno Api",
			 
		]);
		break;
}
?>