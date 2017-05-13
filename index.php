<?php
require_once "config.php";
require_once "class/Twitter.php";
require_once "class/phpQuery/phpQuery.php";
require_once "class/Amazon/Amazon.php";
require_once 'vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

$app = new Bullet\App();


//ISBNから本の情報を取得する
$app->path('/google', function($request) use($app) {
	$app->path('/book', function($request) use($app) {
		if(strlen($request->get("isbn"))===13){
    		$url="https://www.googleapis.com/books/v1/volumes?q=isbn:".$request->get("isbn");
    		$json=file_get_contents($url);
			return $json;
    	}
        return false;
    });
});

//Twitterのアイコンを取得する
$app->path("/twitter",function($request) use($app){
	$app->path('/icon_url', function($request) use($app) {
	    $twitter=new Twitter(TWITTER_API_KEY,TWITTER_API_SECRET,TWITTER_ACCESS_TOKEN,TWITTER_ACCESS_TOKEN_SECRET);
	    $icon_url=$twitter->getIcon($request->get("screen_name"));
	    if(empty($icon_url)){
	    	return false;
	    }
	    return $icon_url;
    });
});

//WrapperAPI/meiji/library/book_state?isbn=9784774184111&campus=nakano
$app->path("/meiji",function($request) use($app){
	$app->path("/library",function($request) use($app){
		$app->path("/book_state",function($request) use($app){
			$isbn=$request->get("isbn");
			$campus=$request->get("campus");
			if(empty($isbn) || (strlen($isbn)!==13 && strlen($isbn)!==10)){
				return false;
			}
			$url="http://opac.lib.meiji.ac.jp/webopac/ctlsrh.do";
			switch($campus){
				case "nakano":$campus="MN";break;
				case "ikuta":$campus="MS";break;
				case "surugadai":$campus="MH";break;
				case "izumi":$campus="MW";break;
				//case "all":$campus=array("MN","MS","MH","MW");break;//書き直さないといけない
				default: $campus="MN";break;
			}
			$postdata=array("holar"=>$campus,"isbn_issn"=>$isbn);
			$ch=curl_init($url);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata);
			$html=curl_exec($ch);
			curl_close($ch);

			//所蔵がある場合
			if(strpos($html,"所蔵はありません")===false && strpos($html,"指定された条件に該当する資料がありませんでした")===false){
				$start_pos=mb_strpos($html,"件の所蔵があります")-10;//</strong>があるので10文字分戻しています
				$book_num=mb_substr($html,$start_pos,1);//本の数が手に入る
				preg_match_all("/貸出中/",$html,$match);
				if($book_num==count($match[0])){
					return "貸出中";
				}else if($book_num>count($match[0])){
					return "OK";
				}
			}else{//所蔵が無い場合
				return "所蔵無し";
			}
		});
	});
});

$app->path("/amazon",function($request) use($app){
	$url=$request->get("url");
	$url=mb_substr($url,mb_strpos($url,"dp/")+mb_strlen("dp/"));
	if(mb_strpos($url,"/")>0){
        $url=mb_substr($url,0,mb_strpos($url,"/"));
    }
    $amazon = new Services_Amazon(AMAZON_ACCESS_KEY_ID,AMAZON_SECRET_ACCESS_KEY,AMAZON_ASSOCIATE_TAG);
    $amazon->setLocale('JP');
    $options = array(
        'Keywords' => $url,
        'ResponseGroup' => 'ItemAttributes,Offers'// 取得する情報
    );
    // 第1引数にカテゴリを指定し、第2引数に検索条件や取得する情報などを指定
    $result = $amazon->ItemSearch('Books', $options);
    $item=$result["Item"][0];
    if (!PEAR::isError($result)){
    	/*** 著者情報は配列になっているので事前に処理する ***/
    	$authors="";
        foreach($item["ItemAttributes"]["Author"] as $author){
            $authors.=$author.",";
        }
        $authors=mb_substr($authors,0,mb_strlen($authors)-1);
    	$array=array(
			"title"=>$item['ItemAttributes']['Title'],
    		"publisher"=>$item["ItemAttributes"]["Publisher"],
    		"date"=>mb_substr($item["ItemAttributes"]["PublicationDate"],0,4),
    		"isbn"=>$item["ItemAttributes"]["ISBN"],
    		"author"=>$authors,
    		"price"=>$item["Offers"]["Offer"]["OfferListing"]["Price"]["Amount"]
    	);
    	return json_encode($array);
    }
});

echo $app->run(new Bullet\Request());

