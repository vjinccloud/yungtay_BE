<?php

namespace App\Services;

use App\Traits\HttpClientTrait;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * CNA 圖片爬蟲服務
 * 專責處理 CNA 新聞圖片的抓取
 */
class CnaImageCrawlerService
{
    use HttpClientTrait;
    /**
     * 驗證圖片 URL 是否有效
     *
     * @param string $imageUrl 圖片 URL
     * @return bool 是否為有效的圖片 URL
     */
    public function validateImageUrl(string $imageUrl): bool
    {
        if (empty($imageUrl)) {
            return false;
        }

        // 檢查是否為圖片副檔名（排除 SVG）
        $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        return in_array($extension, $validExtensions);
    }


    /**
     * 檢查是否為廣告圖片
     *
     * @param string $imageUrl 圖片 URL
     * @return bool 是否為廣告圖片
     */
    private function isAdImage(string $imageUrl): bool
    {
        $adPatterns = [
            'pic_fb.jpg',           // Facebook 分享圖片
            'line-ad-pc.jpg',       // LINE 廣告 PC 版
            'line-ad-s.jpg',        // LINE 廣告小版本
            'line-ad-',             // 其他 LINE 廣告變體（必須在前面，才能匹配所有變體）
            '/website/img/',        // CNA 網站共用圖片目錄（LINE廣告、Logo等）
            'facebook-share',       // Facebook 分享相關圖片
            'social-share',         // 社群分享圖片
            'ad-banner',            // 廣告橫幅
            'advertisement',        // 廣告圖片
            'cnalogo_',             // CNA Logo 圖片
            'footer',               // 頁尾圖片
            'google-news.png',    // Google News 圖片
        ];

        foreach ($adPatterns as $pattern) {
            if (str_contains($imageUrl, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根據 CNA 圖片 ID 獲取完整圖片 URL（單一圖片版本）
     *
     * @param string $imageId 圖片 ID (如: 20250908000137)
     * @param string|null $newsUrl 新聞頁面 URL（可選，用於爬取圖片）
     * @return string|null 完整圖片 URL 或 null
     */
    public function getCnaImageUrlById(string $imageId, ?string $newsUrl = null): ?string
    {
        if (empty($newsUrl)) {
            return null;
        }

        // 使用批次方法處理單一圖片
        $results = $this->getCnaImageUrlsByIds([$imageId], $newsUrl);
        return $results[$imageId] ?? null;
    }

    /**
     * 批次獲取多個 CNA 圖片 ID 的完整圖片 URL
     *
     * @param array $imageIds 圖片 ID 陣列
     * @param string|null $newsUrl 新聞頁面 URL（可選，用於爬取圖片）
     * @return array 圖片 ID => URL 的對應陣列
     */
    public function getCnaImageUrlsByIds(array $imageIds, ?string $newsUrl = null): array
    {
        $results = [];
        if (empty($imageIds) || empty($newsUrl)) {
            return $results;
        }

        try {
            // 先獲取 HTML 內容
            $response = $this->createCnaHttpClient()->get($newsUrl);

            if (!$response->successful()) {
                return $results;
            }

            $html = $response->body();
            // 直接從 JSON-LD 中尋找特定 ID 的圖片
            $jsonLdImages = $this->extractFromJsonLd($html);

            // 對每個要找的 ID 進行精確比對
            foreach ($imageIds as $imageId) {
                $found = false;
                foreach ($jsonLdImages as $imageUrl) {
                    // 檢查 URL 是否包含圖片 ID（可能有 C 前綴）
                    if (strpos($imageUrl, $imageId) !== false || strpos($imageUrl, 'C' . $imageId) !== false) {
                        $results[$imageId] = $imageUrl;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    Log::error('CNA 批次爬取：找不到圖片', [
                        'image_id' => $imageId,
                        'news_url' => $newsUrl,
                        'total_json_ld_images' => count($jsonLdImages),
                        'sample_json_ld_images' => array_slice($jsonLdImages, 0, 3)
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::error('CNA 批次爬取失敗', [
                'url' => $newsUrl,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * 從 HTML 中提取圖片 URLs（包含封面圖片）
     *
     * @param string $html HTML 內容
     * @return array 圖片 URLs
     */
    public function extractImagesFromHtml(string $html): array
    {
        $images = [];
        $crawler = new Crawler($html);

        // 方法1: 從 JSON-LD 結構化資料中抓取圖片（最優先）
        try {
            $jsonLdImages = $this->extractFromJsonLd($html);
            
            foreach ($jsonLdImages as $imageUrl) {
                $isValid = $this->validateImageUrl($imageUrl);
                $isNotAd = !$this->isAdImage($imageUrl);
                $isUnique = !in_array($imageUrl, $images);

                
                if ($isValid && $isNotAd && $isUnique) {
                    $images[] = $imageUrl;
                }
            }
        } catch (Exception $e) {
            Log::warning('JSON-LD 圖片解析失敗', ['error' => $e->getMessage()]);
        }

        // 方法2: 抓取 link rel="image_src" (CNA 封面圖)
        try {
            $imageSrc = $crawler->filter('link[rel="image_src"]')->attr('href');
            if ($imageSrc && $this->validateImageUrl($imageSrc) && !$this->isAdImage($imageSrc) && !in_array($imageSrc, $images)) {
                $images[] = $imageSrc;
            }
        } catch (Exception $e) {
            // 如果沒有找到也沒關係，繼續下一個方法
        }

        // 方法3: 抓取 og:image
        try {
            $ogImage = $crawler->filter('meta[property="og:image"]')->attr('content');
            if ($ogImage && $this->validateImageUrl($ogImage) && !$this->isAdImage($ogImage) && !in_array($ogImage, $images)) {
                $images[] = $ogImage;
            }
        } catch (Exception $e) {
            // 如果沒有找到也沒關係，繼續下一個方法
        }

        // 方法4: 從 img 標籤中抓取圖片
        $crawler->filter('img')->each(function (Crawler $node) use (&$images) {
            $src = $node->attr('src');
            if ($src) {
                // 轉換相對路徑為絕對路徑
                if (strpos($src, 'http') !== 0) {
                    // 檢查是否為 // 開頭的協議相對 URL
                    if (strpos($src, '//') === 0) {
                        $src = 'https:' . $src;
                    } else {
                        $src = 'https://www.cna.com.tw' . $src;
                    }
                }

                // 驗證是否為有效的圖片 URL 且不重複
                if ($this->validateImageUrl($src) && !$this->isAdImage($src) && !in_array($src, $images)) {
                    $images[] = $src;
                }
            }
        });

        return $images;
    }

    /**
     * 從 JSON-LD 結構化資料中提取圖片
     *
     * @param string $html HTML 內容
     * @return array 圖片 URLs
     */
    private function extractFromJsonLd(string $html): array
    {
        $images = [];
        // 使用正則表達式找出所有 JSON-LD 腳本（支援單引號和雙引號）;
        
        // 使用更寬鬆的正規表達式找出所有 JSON-LD 腳本
        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s', $html, $matches)) {       
            foreach ($matches[1] as $jsonString) {
                $jsonData = json_decode($jsonString, true);
              
                if (!$jsonData) {
                    Log::warning('CNA JSON-LD：JSON 解析失敗', [
                        'json_string' => mb_substr($jsonString, 0, 200) . '...',
                        'json_error' => json_last_error_msg()
                    ]);
                    continue;
                }

                // 處理根層數組（CNA 的 JSON-LD 結構）
                if (is_array($jsonData) && isset($jsonData[0])) {
                    // 這是一個數組，遍歷每個項目
                    foreach ($jsonData as $index => $item) {
                        if (!is_array($item)) {
                            continue;
                        }

                        // ✅ 優先處理 NewsArticle 的 thumbnailUrl（封面圖片）
                        if (isset($item['@type']) && $item['@type'] === 'NewsArticle' && isset($item['thumbnailUrl'])) {
                            $images[] = $item['thumbnailUrl'];
                        }

                        // 處理 NewsArticle 的 image 數組
                        if (isset($item['@type']) && $item['@type'] === 'NewsArticle' && isset($item['image']) && is_array($item['image'])) {
                            foreach ($item['image'] as $imageIndex => $imageData) {
                                if (isset($imageData['@type']) && $imageData['@type'] === 'ImageObject' && isset($imageData['url'])) {
                                    $images[] = $imageData['url'];
                                }
                            }
                        }

                        // 處理單獨的 ImageObject
                        if (isset($item['@type']) && $item['@type'] === 'ImageObject' && isset($item['url'])) {
                            $images[] = $item['url'];
                        }
                    }
                }

                // 處理單個 ImageObject 類型（非數組情況）
                if (isset($jsonData['@type']) && $jsonData['@type'] === 'ImageObject' && isset($jsonData['url'])) {
                    $images[] = $jsonData['url'];
                }

                // 處理文章層級的 image 欄位
                if (isset($jsonData['image'])) {
                    if (is_array($jsonData['image'])) {
                        // 如果 image 是陣列，取出每個圖片的 URL
                        foreach ($jsonData['image'] as $imageData) {
                            if (is_array($imageData) && isset($imageData['@type']) && $imageData['@type'] === 'ImageObject' && isset($imageData['url'])) {
                                // ImageObject 結構
                                $images[] = $imageData['url'];
                            } elseif (is_array($imageData) && isset($imageData['url'])) {
                                // 一般 image 物件
                                $images[] = $imageData['url'];
                            } elseif (is_string($imageData)) {
                                // 字串 URL
                                $images[] = $imageData;
                            }
                        }
                    } elseif (is_string($jsonData['image'])) {
                        // 如果 image 是字串
                        $images[] = $jsonData['image'];
                    }
                }

                // 處理 @graph 結構（有些網站會用這種結構）
                if (isset($jsonData['@graph']) && is_array($jsonData['@graph'])) {
                    foreach ($jsonData['@graph'] as $graphItem) {
                        if (isset($graphItem['@type']) && $graphItem['@type'] === 'ImageObject' && isset($graphItem['url'])) {
                            $images[] = $graphItem['url'];
                        }
                    }
                }
            }
        }        
        return array_unique($images); // 去除重複的圖片 URL
    }
}
