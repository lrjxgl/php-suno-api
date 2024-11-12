<?php
require "sunoApi.php";
$cookie='suno cookie';
/*
获取token,可以指定多个suno账号
getToken($service_id,$cookie);
*/

sunoApi::$cookie=$cookie;
$token=sunoApi::getToken("1",$cookie);
//生成歌曲
$ops=[
	"prompt"=>"男歌手，摇滚，励志",
	"model"=>"chirp-v3.5",
	"isCustom"=>false,
	"tags"=>"",
	"title"=>"",
	"make_instrumental"=>false
];
$ids=sunoApi::generateSongs($ops);

//扩展歌曲
$ops=[
	 
	"audio_id"=>"742dfcaa",
	"continue_at"=>"120",
	"prompt"=>"[lrc]【主歌】\n春花秋月何时了？\n往事知多少。\n小楼昨夜又东风，\n故国不堪回首月明中。\n【副歌】\n雕栏玉砌应犹在，\n只是朱颜改。\n问君能有几多愁？\n恰似一江春水向东流。[endlrc]",
];

$ids=sunoApi::extendAudio($ops);
 
//检查歌曲
$arr=sunoApi::feed($ids);
print_r($arr); 