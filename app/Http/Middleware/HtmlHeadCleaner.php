<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HtmlHeadCleaner
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (
            $response instanceof LaravelResponse &&
            !($response instanceof JsonResponse) &&
            !($response instanceof BinaryFileResponse)
        ) {
            $content = $response->getContent();

            if ($content && stripos($content, '<html') !== false) {
                try {
                    // 擷取 head 區塊
                    $content = preg_replace_callback(
                        '/<head\b[^>]*>(.*?)<\/head>/is',
                        function ($matches) {
                            $head = $matches[1];

                            // 1. 移除 head 裡的 HTML 註解 (保留 IE 條件註解)
                            $head = preg_replace('/<!--(?!\[if).*?-->/s', '', $head);

                            // 2. 移除 <style> 裡的 CSS 註解
                            $head = preg_replace_callback(
                                '/<style\b[^>]*>(.*?)<\/style>/is',
                                function ($m) {
                                    $cleanedCss = preg_replace('/\/\*[\s\S]*?\*\//', '', $m[1]);
                                    return "<style>{$cleanedCss}</style>";
                                },
                                $head
                            );

                            // 3. 壓縮多餘空白
                            $head = preg_replace('/\s{2,}/', ' ', $head);

                            return "<head>{$head}</head>";
                        },
                        $content
                    );


                    $response->setContent($content);
                } catch (\Throwable $e) {
                    \Log::warning('Head cleaning failed: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }
}
