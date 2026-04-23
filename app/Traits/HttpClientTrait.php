<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * HTTP 客戶端 Trait
 * 
 * 統一管理 HTTP 客戶端的建立邏輯，支援本地開發環境的 SSL 跳過設定
 * 用於 CNA RSS 相關服務的 HTTP 請求處理
 */
trait HttpClientTrait
{
    /**
     * 建立預設 HTTP 客戶端
     *
     * @param int $timeout 超時時間（秒）
     * @param array $headers 額外的 HTTP 標頭
     * @return PendingRequest
     */
    protected function createHttpClient(int $timeout = null, array $headers = []): PendingRequest
    {
        // 使用設定檔中的預設值或傳入的參數
        $timeout = $timeout ?? config('cna.sync.timeout', 15);
        $userAgent = config('cna.sync.user_agent', 'SJTV RSS Reader/1.0');
        
        // 合併預設標頭和自訂標頭
        $defaultHeaders = [
            'User-Agent' => $userAgent,
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        // 建立 HTTP 客戶端
        $httpClient = Http::timeout($timeout)->withHeaders($allHeaders);
        
        // 在本地環境跳過 SSL 驗證
        if (config('app.env') === 'local') {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }
        
        return $httpClient;
    }

    /**
     * 建立 CNA RSS 專用的 HTTP 客戶端
     * 
     * 使用 CNA RSS 爬蟲的標準設定
     *
     * @param int $timeout 超時時間（秒）
     * @return PendingRequest
     */
    protected function createCnaHttpClient(int $timeout = null): PendingRequest
    {
        $timeout = $timeout ?? 15;
        
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        return $this->createHttpClient($timeout, $headers);
    }

    /**
     * 建立 RSS Feed 專用的 HTTP 客戶端
     *
     * @return PendingRequest
     */
    protected function createRssHttpClient(): PendingRequest
    {
        $headers = [
            'User-Agent' => config('cna.sync.user_agent', 'SJTV RSS Reader/1.0')
        ];
        
        return $this->createHttpClient(config('cna.sync.timeout', 15), $headers);
    }

    /**
     * 發送 GET 請求並處理常見錯誤
     *
     * @param string $url 請求 URL
     * @param array $options HTTP 客戶端選項
     * @return array 包含 success、data、error 的回應陣列
     */
    protected function httpGet(string $url, array $options = []): array
    {
        try {
            $timeout = $options['timeout'] ?? config('cna.sync.timeout', 15);
            $headers = $options['headers'] ?? [];
            
            $response = $this->createHttpClient($timeout, $headers)->get($url);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->body(),
                    'status' => $response->status(),
                    'headers' => $response->headers()
                ];
            }
            
            return [
                'success' => false,
                'error' => 'HTTP request failed',
                'status' => $response->status(),
                'data' => $response->body()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => null,
                'data' => null
            ];
        }
    }
}