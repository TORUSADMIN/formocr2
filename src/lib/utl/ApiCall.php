<?php

require_once(LIB_DIR.'/util/Logger.php');
require_once(LIB_DIR.'/util/trait/EmptyTrait.php');
require_once(LIB_DIR.'/util/trait/TrimTrait.php');

class ApiCall {
	
	public static function getApi($url) {
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($curl, CURLOPT_HEADER, true);   // ヘッダーも出力する
		
		$response = curl_exec($curl);
		// echo $response . "\n";
		
		// ステータスコード取得
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		// header & body 取得
		/*
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); // ヘッダサイズ取得
		$header = substr($response, 0, $header_size); // headerだけ切り出し
		$body = substr($response, $header_size); // bodyだけ切り出し
		*/
		// json形式で返ってくるので、配列に変換
		$result = json_decode($response, true);
		curl_close($curl);
		
		return array($code, $result);
	}
	
	/**
	 * 
	 */
	public static function postApi() {
		
		// サンプルコードを以下に記載
		
		$token = 'xxxxxxxxxxxxxxxxxxxxxx'; // 前準備で作っておいた、tokenを設定
		$base_url = 'https://qiita.com';
		
		$data = [
				'body' => 'example',
				'coediting' => false,
				'private' => true,      // テストで作る時は限定公開で
				'title'=> 'sample test',
				'tags' => [
						[
								'name' => 'PHP',
								'versions' => ["4.3.0",">="]
						],
						[
								'name' => 'sample',
						]
				]
		];
		
		$header = [
				'Authorization: Bearer '.$token,  // 前準備で取得したtokenをヘッダに含める
				'Content-Type: application/json',
		];
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $base_url.'/api/v2/items');
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST'); // post
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); // jsonデータを送信
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header); // リクエストにヘッダーを含める
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		
		$response = curl_exec($curl);
		
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$result = json_decode($body, true);
		
		curl_close($curl);
	}
}