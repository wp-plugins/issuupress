<?php
if (!class_exists('issuuAPI')) {
	class issuuAPI {
		// url to Issuu api
		var $requestUrl = 'http://api.issuu.com/1_0';
		var $uploadUrl = 'http://api.issuu.com/1_0';

		var $apiKey;
		var $apiSecret;
		var $issuuCacheFile;
		var $cacheFolder;
		var $cacheDuration = 3600; // in seconds




		function __construct($key)
		{
			$this->apiKey = $key['apiKey'];
			$this->apiSecret = $key['apiSecret'];
			$this->cacheFolder= plugin_dir_path(__FILE__).'cache';
			$this->issuuCacheFile = $this->cacheFolder . '/issuu.json';
			$this->cacheDuration = ($key['cacheDuration']!='')? $key['cacheDuration']: $this->cacheDuration;
			
		}

		public function getListing()
		{
			// see: http://issuu.com/services/api/issuu.document.list.html

			if (!$this->cache_is_valid()){

				$apiKey = $this->apiKey;
				$apiSecret = $this->apiSecret;

				$args=array(
					'format'=>'json',
					'action'=>'issuu.documents.list',
					'apiKey'=>$apiKey,
					'documentSortBy'=>'publishDate',
					'documentStates'=>'A',
					'pageSize'=>'30',
					'resultOrder'=>'desc',
					'startIndex'=>'0',
					'access'=>'public'
				);

				ksort($args);
				$argumentsAsSignature = $argAsUrlParameters = '';

				foreach($args as $k=>$v){
					$argAsSignature .=$k.$v;
					$argAsUrlParameters.= '&'.$k.'='.$v;
				}

				$signature = md5($apiSecret.$argAsSignature);
				$request_url = $this->requestUrl .'?signature='.$signature.$argAsUrlParameters;
				$response = wp_remote_get($request_url);
				if( is_wp_error($response) || isset($response->errors) || $response == null ) {
					return false;
				}
				file_put_contents($this->issuuCacheFile, $response['body']);

			}
			else{
				// Fetch from cache
				$response['body'] = file_get_contents($this->issuuCacheFile);

			}
			$response = json_decode( $response['body'] );
			if (empty( $response) )
				return false;

			if ( $response->rsp->stat == "fail" )
				return false;

			return $response->rsp->_content->result;
		}


		function cache_is_valid(){
			// Check if we need to refresh the cache (see: http://php.net/manual/en/function.filemtime.php)
			if(!is_file($this->issuuCacheFile)){
				chmod($this->cacheFolder, 0777);
				$file = fopen($this->issuuCacheFile, 'w');// or die("can't create file");
				fclose($file);
				return false;
			}
			$filemtime = @filemtime($this->issuuCacheFile);
			return ($filemtime && (time() - $filemtime < $this->cacheDuration));
		}
	}
}
?>