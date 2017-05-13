<?php
class Twitter{
	private $api_key;
	private $api_secret;
	private $access_token;
	private $access_token_secret;
	function __construct($ak,$as,$at,$ats){
		$this->api_key=$ak;
		$this->api_secret=$as;
		$this->access_token=$at;
		$this->access_token_secret=$ats;
	}
	function getIcon($screen_name){
		$request_url = 'https://api.twitter.com/1.1/users/show.json';
		$request_method = 'GET' ;
		$params_a = array('screen_name'=>$screen_name);
		$signature_key=rawurlencode($this->api_secret).'&'.rawurlencode($this->access_token_secret);
		$params_b=array(
			'oauth_token'=>$this->access_token,
			'oauth_consumer_key'=>$this->api_key,
			'oauth_signature_method'=>'HMAC-SHA1' ,
			'oauth_timestamp'=>time() ,
			'oauth_nonce'=>microtime() ,
			'oauth_version'=>'1.0' ,
		);
		$params_c=array_merge($params_a,$params_b);
		ksort( $params_c ) ;
		$request_params=http_build_query($params_c,'','&');// パラメータの連想配列を[キー=値&キー=値...]の文字列に変換する
		// 一部の文字列をフォロー
		$request_params = str_replace( array( '+' , '%7E' ) , array( '%20' , '~' ) , $request_params ) ;
		// 変換した文字列をURLエンコードする
		$request_params = rawurlencode( $request_params ) ;
		// リクエストメソッドをURLエンコードする
		// ここでは、URL末尾の[?]以下は付けないこと
		$encoded_request_method = rawurlencode( $request_method ) ;
		// リクエストURLをURLエンコードする
		$encoded_request_url = rawurlencode( $request_url ) ;
		// リクエストメソッド、リクエストURL、パラメータを[&]で繋ぐ
		$signature_data = $encoded_request_method . '&' . $encoded_request_url . '&' . $request_params ;
		// キー[$signature_key]とデータ[$signature_data]を利用して、HMAC-SHA1方式のハッシュ値に変換する
		$hash = hash_hmac( 'sha1' , $signature_data , $signature_key , TRUE ) ;
		// base64エンコードして、署名[$signature]が完成する
		$signature = base64_encode( $hash ) ;
		// パラメータの連想配列、[$params]に、作成した署名を加える
		$params_c['oauth_signature'] = $signature ;
		// パラメータの連想配列を[キー=値,キー=値,...]の文字列に変換する
		$header_params = http_build_query( $params_c , '' , ',' ) ;
		// リクエスト用のコンテキスト
		$context = array(
			'http' => array(
				'method' => $request_method , // リクエストメソッド
				'header' => array(			  // ヘッダー
					'Authorization: OAuth ' . $header_params ,
				) ,
			) ,
		) ;
		// パラメータがある場合、URLの末尾に追加
		if( $params_a ){
			$request_url .= '?' . http_build_query( $params_a ) ;
		}
		$json = @file_get_contents( $request_url , false , stream_context_create( $context ) ) ;
		$json=json_decode($json);
		return $json->profile_image_url;
	}
}