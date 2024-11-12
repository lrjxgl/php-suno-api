<?php
class sunoApi{
	public static $apiHost='https://studio-api.suno.ai'; 
	public static $clerkHost='https://clerk.suno.ai';
	public static $token='';
	public static $uid='';
	public static $header=[
		"Content-Type: text/plain;charset=UTF-8",
		"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36",
		"Referer: https://suno.com",
		"Origin: https://suno.com",
	];
	public static $cookie="";
	public static function getToken($uid,$cookie_str){
		self::$uid=$uid;
		$cacheFile= dirname(__FILE__)."/cache/suno_token_{$uid}.txt";
		if(file_exists($cacheFile)){
			$con=file_get_contents($cacheFile);
			$arr=unserialize($con);
			if($arr['expire']>time()){
				self::$token=$arr['token'];
				return $arr['token'];
			}
		}
		self::$cookie=$cookie_str;
		$url1=self::$clerkHost.'/v1/client?_clerk_js_version=5.20.0';
		$res=self::get($url1);
		$a=json_decode($res,true);
		 
		if(!empty($a['response']['last_active_session_id'])){
			$session_id= $a['response']['last_active_session_id'];
		}else{
			return '';
		}
		
		 
		$url2 = self::$clerkHost."/v1/client/sessions/{$session_id}/tokens?_clerk_js_version=5.20.0";		
		$body=self::post($url2,[]);
		  
		$json = json_decode($body, true);
		if (isset($json['jwt'])) {
			$cache_data=[
				"token"=>$json['jwt'],
				"expire"=>time()+10
			];
			file_put_contents($cacheFile,serialize($cache_data));
			self::$token=$json['jwt'];
			return $json['jwt'];
		}
		return '';
		
	}
	
	public static function get($url,$timeout=50){
		 $ch = curl_init();
		 curl_setopt($ch, CURLOPT_URL, $url);
		 curl_setopt($ch, CURLOPT_HEADER, 0);
		 curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		 curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		 $header=self::$header;
		 if(!empty(self::$token)){
		 	$header[]='Authorization: Bearer '.self::$token;
		 }
		  
		 curl_setopt($ch, CURLOPT_HTTPHEADER,$header); 
		 curl_setopt($ch, CURLOPT_COOKIE, self::$cookie);
		 
		 $content= curl_exec($ch);
		 curl_close($ch);
		 return $content;
	} 
	public static function post($url, $data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_POST, TRUE); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$header=self::$header;
		if(!empty(self::$token)){
			$header[]='Authorization: Bearer '.self::$token;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch, CURLOPT_COOKIE, self::$cookie);
		 
		$ret = curl_exec($ch);
	
		curl_close($ch);
		return $ret;
	}
	public static function post_json($url, $json,$Async="enable")
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($ch, CURLOPT_POST, TRUE); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		
		curl_setopt($ch, CURLOPT_COOKIE, self::$cookie); 
		$header=self::$header;
		if(!empty(self::$token)){
			$header[]='Authorization: Bearer '.self::$token;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		curl_setopt($ch, CURLOPT_COOKIE, self::$cookie);
		 
		$ret = curl_exec($ch);
	
		curl_close($ch);
		return $ret;
	}
	
	public static function feed($ids=""){
		$url=self::$apiHost."/api/feed/";
		if(!empty($ids)){
			$url.="?ids=".$ids;
		}
		$res=self::get($url);
		 
		$arr=json_decode($res,true);
		//print_r($arr);
		$list=[];
		if(!empty($arr)){
			foreach($arr as $audio){
				$item=[
					"id"=>$audio["id"],
					"title"=>$audio["title"],
					"image_url"=>!empty($audio["image_url"])?$audio["image_url"]:'',
					"lyric"=>$audio["metadata"]["prompt"] ? $audio["metadata"]["prompt"]: "",
					"audio_url"=>$audio["audio_url"],
					"video_url"=>$audio["video_url"],
					"created_at"=>$audio["created_at"],
					"model_name"=>$audio["model_name"],
					"status"=>$audio["status"],
					"gpt_description_prompt"=>!empty($audio["metadata"]["gpt_description_prompt"])?$audio["metadata"]["gpt_description_prompt"]:"",
					"prompt"=>$audio["metadata"]["prompt"],
					"type"=>$audio["metadata"]["type"],
					"tags"=>$audio["metadata"]["tags"],
					"duration"=>!empty($audio["metadata"]["duration"])?$audio["metadata"]["duration"]:0,  
					"error_message"=>!empty($audio["metadata"]["error_message"])?$audio["metadata"]["error_message"]:"",
				];
				$list[]=$item;	
			}
		}
		return $list;
	}
	
	public static function get_credits(){
		$url=self::$apiHost."/api/billing/info/";
		$res=self::get($url);
		$arr=json_decode($res,true);
		 
		if(isset($arr['total_credits_left'])){
			return [
				"credits_left"=>$arr['total_credits_left'],
				"period"=>!empty($arr['period'])?$arr['period']:0,
				"monthly_limit"=>$arr['monthly_limit'],
				"monthly_usage"=>$arr['monthly_usage']
			];
		}
		return [];
	}
	
	public static function generateLyrics($prompt,$async=true){
		$url=self::$apiHost."/api/generate/lyrics/";
		$res=self::post_json($url,json_encode(["prompt"=>$prompt]));
		$arr=json_decode($res,true);
		 
		if(isset($arr["id"])){
			$id=$arr["id"];
			return $id;
			
		}
		return "";
	}
	
	public static function checkLyrics($id){
		$url=self::$apiHost."/api/generate/lyrics/".$id;
		$res2=self::get($url);
		 
		$arr2=json_decode($res2,true);
		 
		if(isset($arr2["status"]) && $arr2["status"]=="complete"){
			return [
				"title"=>$arr2["title"],
				"content"=>$arr2["text"],
				"status"=>"complete"
			];
		}else{
			return [
				"status"=>"doing"
			];
		}
		
	}
	
	public static function generateSongs($ops){
		$prompt="";
		$prompt=isset($ops["prompt"])?$ops["prompt"]:$prompt;
		$model="chirp-v3.5";
		$model=isset($ops["model"])?$ops["model"]:$model;
		$isCustom=false;
		$isCustom=isset($ops["isCustom"])?$ops["isCustom"]:$isCustom;
		$tags="";
		$tags=isset($ops["tags"])?$ops["tags"]:$tags;
		$title="";
		$title=isset($ops["title"])?$ops["title"]:$title;
		$make_instrumental=false;	
		$make_instrumental=isset($ops["make_instrumental"])?$ops["make_instrumental"]:$make_instrumental;	
		$wait_audio= false;
		$url=self::$apiHost."/api/generate/v2/";
		$payload = [
		    'make_instrumental' => $make_instrumental,
		    'mv' => $model ,
		    'prompt' => $prompt,
		];
		
		if ($isCustom) {
		    $payload['tags'] = $tags;
		    $payload['title'] = $title;
		    $payload['prompt'] = $prompt;
		} else {
		    $payload['gpt_description_prompt'] = $prompt;
		}
		$json=json_encode($payload);
		$res=self::post_json($url,$json);
		//file_put_contents("temp/suno_log.txt",$res,FILE_APPEND);
		$arr=json_decode($res,true);
		$ids=[];
		if(isset($arr["clips"])){
			$ids=[
				$arr["clips"][0]["id"],
				$arr["clips"][1]["id"],
			];
			return implode(",",$ids);
		}
		return "";
	}
	
	public static function extendAudio($ops){
		$prompt="";
		$prompt=isset($ops["prompt"])?$ops["prompt"]:$prompt;
		$model="chirp-v3.5";
		$model=isset($ops["model"])?$ops["model"]:$model;
		$isCustom=false;
		$isCustom=isset($ops["isCustom"])?$ops["isCustom"]:$isCustom;
		$tags="";
		$tags=isset($ops["tags"])?$ops["tags"]:$tags;
		$title="";
		$title=isset($ops["title"])?$ops["title"]:$title;
		$continue_at=$ops["continue_at"];
		$audio_id=$ops["audio_id"];
		$url=self::$apiHost."/api/generate/v2/";
		$payload=[
			"continue_clip_id"=>$audio_id,
			"prompt"=>$prompt,
			"continue_at"=>$continue_at,
			"tags"=>"",
			 "title"=>"",
			'mv' => $model ,
			"make_instrumental"=>false
		];
		//print_r($payload);
		$json=json_encode($payload);
		$res=self::post_json($url,$json);
		$arr=json_decode($res,true);
		$ids=[];
		//print_r($arr);
		if(isset($arr["clips"])){
			$ids=[
				$arr["clips"][0]["id"],
				$arr["clips"][1]["id"],
			];
			return implode(",",$ids);
		}
		return "";
	}
	
	
}

?>