<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdminMenu;

class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 先清空所有選單資料
        AdminMenu::truncate();

        // 重新插入選單資料
        AdminMenu::upsert(
            [
                //權限管理
                ['id' => '1', 'title' => '權限管理', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'permission-management', 'icon_image' => 'fa fa-fw fa-wrench', 'status' => '1', 'seq' => '1'],

                //角色管理設定
                ['id' => '2', 'title' => '角色權限管理', 'parent_id' => '1', 'type' => '1', 'level' => '1', 'url' => 'admin/administration-settings', 'url_name' => 'admin.administration-settings', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '3', 'title' => '新增角色權限', 'parent_id' => '2', 'type' => '0', 'level' => '2', 'url' => 'admin/administration-settings/add', 'url_name' => 'admin.administration-settings.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '4', 'title' => '編輯角色權限', 'parent_id' => '2', 'type' => '0', 'level' => '2', 'url' => 'admin/administration-settings/edit', 'url_name' => 'admin.administration-settings.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '5', 'title' => '刪除角色權限', 'parent_id' => '2', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.administration-settings.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //管理員管理設定
                ['id' => '6', 'title' => '管理員管理', 'parent_id' => '1', 'type' => '1', 'level' => '1', 'url' => 'admin/admin-settings', 'url_name' => 'admin.admin-settings', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '7', 'title' => '新增管理員', 'parent_id' => '6', 'type' => '0', 'level' => '2', 'url' => 'admin/admin-settings/add', 'url_name' => 'admin.admin-settings.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '8', 'title' => '編輯管理員', 'parent_id' => '6', 'type' => '0', 'level' => '2', 'url' => 'admin/admin-settings/edit', 'url_name' => 'admin.admin-settings.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '9', 'title' => '刪除管理員', 'parent_id' => '6', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.admin-settings.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //操作紀錄
                ['id' => '10', 'title' => '操作紀錄', 'parent_id' => '0', 'type' => '0', 'level' => '0', 'url' => 'admin/operation-logs', 'url_name' => 'admin.operation-logs', 'icon_image' => '', 'status' => '1', 'seq' => '0'],

                //重新計算統計數據
                ['id' => '109', 'title' => '重新計算統計數據', 'parent_id' => '0', 'type' => '0', 'level' => '0', 'url' => '', 'url_name' => 'admin.statistics.recalculate', 'icon_image' => '', 'status' => '1', 'seq' => '1'],

                //更新儀表板數據
                ['id' => '110', 'title' => '更新儀表板數據', 'parent_id' => '0', 'type' => '0', 'level' => '0', 'url' => '', 'url_name' => 'admin.dashboard.refresh', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                //首頁系統
                ['id' => '11', 'title' => '首頁系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'home-page-systems', 'icon_image' => 'fa-solid fa-house-chimney', 'status' => '1', 'seq' => '2'],
                ['id' => '12', 'title' => '首頁輪播管理', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/banners', 'url_name' => 'admin.banners', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '13', 'title' => '新增首頁輪播', 'parent_id' => '12', 'type' => '0', 'level' => '2', 'url' => 'admin/banners/create', 'url_name' => 'admin.banners.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '14', 'title' => '編輯首頁輪播', 'parent_id' => '12', 'type' => '0', 'level' => '2', 'url' => 'admin/banners/edit', 'url_name' => 'admin.banners.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '15', 'title' => '刪除首頁輪播', 'parent_id' => '12', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.banners.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 首頁圖片設定（單一頁面）
                ['id' => '137', 'title' => '首頁圖片設定', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/home-image-setting', 'url_name' => 'admin.home-image-setting', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                // 首頁影片管理（列表頁面）
                ['id' => '138', 'title' => '首頁影片管理', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/home-video-settings', 'url_name' => 'admin.home-video-settings.index', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '139', 'title' => '新增首頁影片', 'parent_id' => '138', 'type' => '0', 'level' => '2', 'url' => 'admin/home-video-settings/add', 'url_name' => 'admin.home-video-settings.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '140', 'title' => '編輯首頁影片', 'parent_id' => '138', 'type' => '0', 'level' => '2', 'url' => 'admin/home-video-settings/edit', 'url_name' => 'admin.home-video-settings.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '141', 'title' => '刪除首頁影片', 'parent_id' => '138', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.home-video-settings.destroy', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 片頭動畫（單一頁面）
                ['id' => '150', 'title' => '片頭動畫', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/intro-video', 'url_name' => 'admin.intro-video', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                // 銷售據點圖片管理（單一頁面）
                ['id' => '151', 'title' => '銷售據點圖片管理', 'parent_id' => '11', 'type' => '1', 'level' => '1', 'url' => 'admin/sales-location-image', 'url_name' => 'admin.sales-location-image', 'icon_image' => '', 'status' => '1', 'seq' => '5'],

                // ======== 產品服務系統 ========
                ['id' => '147', 'title' => '產品服務系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'product-service-systems', 'icon_image' => 'fas fa-box', 'status' => '1', 'seq' => '3'],

                // 產品及服務（列表頁面）
                ['id' => '142', 'title' => '產品及服務', 'parent_id' => '147', 'type' => '1', 'level' => '1', 'url' => 'admin/product-services', 'url_name' => 'admin.product-services.index', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '143', 'title' => '新增產品服務', 'parent_id' => '142', 'type' => '0', 'level' => '2', 'url' => 'admin/product-services/add', 'url_name' => 'admin.product-services.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '144', 'title' => '編輯產品服務', 'parent_id' => '142', 'type' => '0', 'level' => '2', 'url' => 'admin/product-services/edit', 'url_name' => 'admin.product-services.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '145', 'title' => '刪除產品服務', 'parent_id' => '142', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.product-services.destroy', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 工廠服務設定（單一頁面）
                ['id' => '146', 'title' => '工廠服務設定', 'parent_id' => '147', 'type' => '1', 'level' => '1', 'url' => 'admin/factory-service-settings', 'url_name' => 'admin.factory-service-settings.index', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                // 工廠設定（列表＋編輯）
                ['id' => '148', 'title' => '工廠設定', 'parent_id' => '147', 'type' => '1', 'level' => '1', 'url' => 'admin/factory-settings', 'url_name' => 'admin.factory-settings.index', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '149', 'title' => '編輯工廠', 'parent_id' => '148', 'type' => '0', 'level' => '2', 'url' => 'admin/factory-settings/edit', 'url_name' => 'admin.factory-settings.edit', 'icon_image' => '', 'status' => '1', 'seq' => '1'],

                // ======== 會員系統 ========
                ['id' => '91', 'title' => '會員系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'member-systems', 'icon_image' => 'fas fa-users', 'status' => '1', 'seq' => '4'],

                //會員管理
                ['id' => '92', 'title' => '會員管理', 'parent_id' => '91', 'type' => '1', 'level' => '1', 'url' => 'admin/members', 'url_name' => 'admin.members', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '93', 'title' => '查看會員詳情', 'parent_id' => '92', 'type' => '0', 'level' => '2', 'url' => 'admin/members/show', 'url_name' => 'admin.members.show', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                //['id'=>'94','title' => '編輯會員','parent_id'=>'92','type'=>'0','level'=>'2','url'=>'admin/members/edit','url_name'=>'admin.members.edit','icon_image'=>'','status'=>'1','seq'=>'2'],
                //['id'=>'95','title' => '刪除會員','parent_id'=>'92','type'=>'0','level'=>'2','url'=>'','url_name'=>'admin.members.delete','icon_image'=>'','status'=>'1','seq'=>'3'],
                ['id' => '96', 'title' => '啟用/停用會員', 'parent_id' => '92', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.members.toggle-status', 'icon_image' => '', 'status' => '1', 'seq' => '4'],


                //最新消息
                ['id' => '16', 'title' => '最新消息', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'news-systems', 'icon_image' => 'fas fa-newspaper', 'status' => '1', 'seq' => '4'],
                ['id' => '17', 'title' => '最新消息管理', 'parent_id' => '16', 'type' => '1', 'level' => '1', 'url' => 'admin/news', 'url_name' => 'admin.news', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '18', 'title' => '新增最新消息', 'parent_id' => '17', 'type' => '0', 'level' => '2', 'url' => 'admin/news/add', 'url_name' => 'admin.news.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '19', 'title' => '編輯最新消息', 'parent_id' => '17', 'type' => '0', 'level' => '2', 'url' => 'admin/news/edit', 'url_name' => 'admin.news.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '20', 'title' => '刪除最新消息', 'parent_id' => '17', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.news.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 最新消息分類管理
                ['id' => '116', 'title' => '最新消息分類管理', 'parent_id' => '16', 'type' => '1', 'level' => '1', 'url' => 'admin/news-categories', 'url_name' => 'admin.news-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '117', 'title' => '新增最新消息分類', 'parent_id' => '116', 'type' => '0', 'level' => '2', 'url' => 'admin/news-categories/add', 'url_name' => 'admin.news-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '118', 'title' => '編輯最新消息分類', 'parent_id' => '116', 'type' => '0', 'level' => '2', 'url' => 'admin/news-categories/edit', 'url_name' => 'admin.news-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '119', 'title' => '刪除最新消息分類', 'parent_id' => '116', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.news-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //新聞系統
                ['id' => '68', 'title' => '新聞系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'article-systems', 'icon_image' => 'fas fa-file-text', 'status' => '1', 'seq' => '5'],
                ['id' => '69', 'title' => '新聞分類管理', 'parent_id' => '68', 'type' => '1', 'level' => '1', 'url' => 'admin/article-categories', 'url_name' => 'admin.article-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '70', 'title' => '新增新聞分類', 'parent_id' => '69', 'type' => '0', 'level' => '2', 'url' => 'admin/article-categories/add', 'url_name' => 'admin.article-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '71', 'title' => '編輯新聞分類', 'parent_id' => '69', 'type' => '0', 'level' => '2', 'url' => 'admin/article-categories/edit', 'url_name' => 'admin.article-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '72', 'title' => '刪除新聞分類', 'parent_id' => '69', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.article-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '73', 'title' => '新聞管理', 'parent_id' => '68', 'type' => '1', 'level' => '1', 'url' => 'admin/articles', 'url_name' => 'admin.articles', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '74', 'title' => '新增新聞', 'parent_id' => '73', 'type' => '0', 'level' => '2', 'url' => 'admin/articles/add', 'url_name' => 'admin.articles.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '75', 'title' => '編輯新聞', 'parent_id' => '73', 'type' => '0', 'level' => '2', 'url' => 'admin/articles/edit', 'url_name' => 'admin.articles.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '76', 'title' => '刪除新聞', 'parent_id' => '73', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.articles.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 影音系統 (4層架構) ========
                //第1層 - 影音系統主分類
                ['id' => '21', 'title' => '影音系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'video-systems', 'icon_image' => 'fas fa-video', 'status' => '1', 'seq' => '6'],

                // ======== 第2層 - 影音系統 ========
                ['id' => '22', 'title' => '影音專區', 'parent_id' => '21', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'drama-systems', 'icon_image' => '', 'status' => '1', 'seq' => '1'],

                //第3層 - 影音分類管理
                ['id' => '23', 'title' => '影音分類管理', 'parent_id' => '22', 'type' => '1', 'level' => '2', 'url' => 'admin/drama-categories', 'url_name' => 'admin.drama-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '24', 'title' => '新增影音分類', 'parent_id' => '23', 'type' => '0', 'level' => '3', 'url' => 'admin/drama-categories/add', 'url_name' => 'admin.drama-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '25', 'title' => '編輯影音分類', 'parent_id' => '23', 'type' => '0', 'level' => '3', 'url' => 'admin/drama-categories/edit', 'url_name' => 'admin.drama-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '26', 'title' => '刪除影音分類', 'parent_id' => '23', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.drama-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //第3層 - 影音管理
                ['id' => '27', 'title' => '影音管理', 'parent_id' => '22', 'type' => '1', 'level' => '2', 'url' => 'admin/dramas', 'url_name' => 'admin.dramas', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '28', 'title' => '新增影音', 'parent_id' => '27', 'type' => '0', 'level' => '3', 'url' => 'admin/dramas/add', 'url_name' => 'admin.dramas.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '29', 'title' => '編輯影音', 'parent_id' => '27', 'type' => '0', 'level' => '3', 'url' => 'admin/dramas/edit', 'url_name' => 'admin.dramas.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '30', 'title' => '刪除影音', 'parent_id' => '27', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.dramas.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '97', 'title' => '查看觀看紀錄', 'parent_id' => '27', 'type' => '0', 'level' => '3', 'url' => 'admin/dramas/view-logs', 'url_name' => 'admin.dramas.view-logs', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                //第3層 - 影音主題管理
                ['id' => '31', 'title' => '影音主題管理', 'parent_id' => '22', 'type' => '1', 'level' => '2', 'url' => 'admin/drama-themes', 'url_name' => 'admin.drama-themes', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '32', 'title' => '新增影音主題', 'parent_id' => '31', 'type' => '0', 'level' => '3', 'url' => 'admin/drama-themes/add', 'url_name' => 'admin.drama-themes.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '33', 'title' => '編輯影音主題', 'parent_id' => '31', 'type' => '0', 'level' => '3', 'url' => 'admin/drama-themes/edit', 'url_name' => 'admin.drama-themes.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '34', 'title' => '刪除影音主題', 'parent_id' => '31', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.drama-themes.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 第2層 - 節目系統 ========
                ['id' => '35', 'title' => '節目專區', 'parent_id' => '21', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'program-systems', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                //第3層 - 節目分類管理
                ['id' => '36', 'title' => '節目分類管理', 'parent_id' => '35', 'type' => '1', 'level' => '2', 'url' => 'admin/program-categories', 'url_name' => 'admin.program-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '37', 'title' => '新增節目分類', 'parent_id' => '36', 'type' => '0', 'level' => '3', 'url' => 'admin/program-categories/add', 'url_name' => 'admin.program-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '38', 'title' => '編輯節目分類', 'parent_id' => '36', 'type' => '0', 'level' => '3', 'url' => 'admin/program-categories/edit', 'url_name' => 'admin.program-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '39', 'title' => '刪除節目分類', 'parent_id' => '36', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.program-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //第3層 - 節目管理
                ['id' => '40', 'title' => '節目管理', 'parent_id' => '35', 'type' => '1', 'level' => '2', 'url' => 'admin/programs', 'url_name' => 'admin.programs', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '41', 'title' => '新增節目', 'parent_id' => '40', 'type' => '0', 'level' => '3', 'url' => 'admin/programs/add', 'url_name' => 'admin.programs.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '42', 'title' => '編輯節目', 'parent_id' => '40', 'type' => '0', 'level' => '3', 'url' => 'admin/programs/edit', 'url_name' => 'admin.programs.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '43', 'title' => '刪除節目', 'parent_id' => '40', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.programs.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '98', 'title' => '查看觀看紀錄', 'parent_id' => '40', 'type' => '0', 'level' => '3', 'url' => 'admin/programs/view-logs', 'url_name' => 'admin.programs.view-logs', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                //第3層 - 節目主題管理
                ['id' => '44', 'title' => '節目主題管理', 'parent_id' => '35', 'type' => '1', 'level' => '2', 'url' => 'admin/program-themes', 'url_name' => 'admin.program-themes', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '45', 'title' => '新增節目主題', 'parent_id' => '44', 'type' => '0', 'level' => '3', 'url' => 'admin/program-themes/add', 'url_name' => 'admin.program-themes.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '46', 'title' => '編輯節目主題', 'parent_id' => '44', 'type' => '0', 'level' => '3', 'url' => 'admin/program-themes/edit', 'url_name' => 'admin.program-themes.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '47', 'title' => '刪除節目主題', 'parent_id' => '44', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.program-themes.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 第2層 - 直播系統 ========
                ['id' => '48', 'title' => '直播專區', 'parent_id' => '21', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'live-systems', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //第3層 - 直播管理
                ['id' => '49', 'title' => '直播管理', 'parent_id' => '48', 'type' => '1', 'level' => '2', 'url' => 'admin/lives', 'url_name' => 'admin.lives', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '50', 'title' => '新增直播', 'parent_id' => '49', 'type' => '0', 'level' => '3', 'url' => 'admin/lives/add', 'url_name' => 'admin.lives.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '51', 'title' => '編輯直播', 'parent_id' => '49', 'type' => '0', 'level' => '3', 'url' => 'admin/lives/edit', 'url_name' => 'admin.lives.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '52', 'title' => '刪除直播', 'parent_id' => '49', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.lives.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 第2層 - 廣播系統 ========
                ['id' => '53', 'title' => '廣播專區', 'parent_id' => '21', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'radio-systems', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                //第3層 - 廣播分類管理
                ['id' => '54', 'title' => '廣播分類管理', 'parent_id' => '53', 'type' => '1', 'level' => '2', 'url' => 'admin/radio-categories', 'url_name' => 'admin.radio-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '55', 'title' => '新增廣播分類', 'parent_id' => '54', 'type' => '0', 'level' => '3', 'url' => 'admin/radio-categories/add', 'url_name' => 'admin.radio-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '56', 'title' => '編輯廣播分類', 'parent_id' => '54', 'type' => '0', 'level' => '3', 'url' => 'admin/radio-categories/edit', 'url_name' => 'admin.radio-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '57', 'title' => '刪除廣播分類', 'parent_id' => '54', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.radio-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //第3層 - 廣播管理
                ['id' => '58', 'title' => '廣播管理', 'parent_id' => '53', 'type' => '1', 'level' => '2', 'url' => 'admin/radios', 'url_name' => 'admin.radios', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '59', 'title' => '新增廣播', 'parent_id' => '58', 'type' => '0', 'level' => '3', 'url' => 'admin/radios/add', 'url_name' => 'admin.radios.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '60', 'title' => '編輯廣播', 'parent_id' => '58', 'type' => '0', 'level' => '3', 'url' => 'admin/radios/edit', 'url_name' => 'admin.radios.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '61', 'title' => '刪除廣播', 'parent_id' => '58', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.radios.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '111', 'title' => '查看觀看統計', 'parent_id' => '58', 'type' => '0', 'level' => '3', 'url' => 'admin/radios/view-stats', 'url_name' => 'admin.radios.view-stats', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                //第3層 - 廣播主題管理
                ['id' => '112', 'title' => '廣播主題管理', 'parent_id' => '53', 'type' => '1', 'level' => '2', 'url' => 'admin/radio-themes', 'url_name' => 'admin.radio-themes', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '113', 'title' => '新增廣播主題', 'parent_id' => '112', 'type' => '0', 'level' => '3', 'url' => 'admin/radio-themes/add', 'url_name' => 'admin.radio-themes.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '114', 'title' => '編輯廣播主題', 'parent_id' => '112', 'type' => '0', 'level' => '3', 'url' => 'admin/radio-themes/edit', 'url_name' => 'admin.radio-themes.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '115', 'title' => '刪除廣播主題', 'parent_id' => '112', 'type' => '0', 'level' => '3', 'url' => '', 'url_name' => 'admin.radio-themes.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 客服中心系統 ========
                ['id' => '77', 'title' => '客服中心系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'customer-service-systems', 'icon_image' => 'fas fa-headset', 'status' => '1', 'seq' => '7'],

                //收件信箱總管
                ['id' => '78', 'title' => '收件信箱管理', 'parent_id' => '77', 'type' => '1', 'level' => '1', 'url' => 'admin/mail-recipients', 'url_name' => 'admin.mail-recipients', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '79', 'title' => '新增收件信箱', 'parent_id' => '78', 'type' => '0', 'level' => '2', 'url' => 'admin/mail-recipients/create', 'url_name' => 'admin.mail-recipients.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '80', 'title' => '編輯收件信箱', 'parent_id' => '78', 'type' => '0', 'level' => '2', 'url' => 'admin/mail-recipients/edit', 'url_name' => 'admin.mail-recipients.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '81', 'title' => '刪除收件信箱', 'parent_id' => '78', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.mail-recipients.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //信件總管
                ['id' => '82', 'title' => '客服信件管理', 'parent_id' => '77', 'type' => '1', 'level' => '1', 'url' => 'admin/customer-services', 'url_name' => 'admin.customer-services', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '83', 'title' => '查看信件', 'parent_id' => '82', 'type' => '0', 'level' => '2', 'url' => 'admin/customer-services/show', 'url_name' => 'admin.customer-services.show', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '84', 'title' => '回覆信件', 'parent_id' => '82', 'type' => '0', 'level' => '2', 'url' => 'admin/customer-services/reply', 'url_name' => 'admin.customer-services.reply', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '85', 'title' => '更新信件', 'parent_id' => '82', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.customer-services.update', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '86', 'title' => '刪除信件', 'parent_id' => '82', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.customer-services.delete', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                //會員通知管理
                ['id' => '87', 'title' => '會員通知管理', 'parent_id' => '77', 'type' => '1', 'level' => '1', 'url' => 'admin/member-notifications', 'url_name' => 'admin.member-notifications', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '88', 'title' => '新增會員通知', 'parent_id' => '87', 'type' => '0', 'level' => '2', 'url' => 'admin/member-notifications/add', 'url_name' => 'admin.member-notifications.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '89', 'title' => '查看通知詳情', 'parent_id' => '87', 'type' => '0', 'level' => '2', 'url' => 'admin/member-notifications/show', 'url_name' => 'admin.member-notifications.show', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '90', 'title' => '刪除會員通知', 'parent_id' => '87', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.member-notifications.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // ======== 數據報表系統 ========
                ['id' => '100', 'title' => '數據報表', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'analytics-systems', 'icon_image' => 'fas fa-chart-bar', 'status' => '1', 'seq' => '8'],

                //新聞數據報表
                ['id' => '101', 'title' => '新聞數據報表', 'parent_id' => '100', 'type' => '1', 'level' => '1', 'url' => 'admin/analytics/articles', 'url_name' => 'admin.analytics.articles', 'icon_image' => '', 'status' => '1', 'seq' => '1'],

                //影音數據報表
                ['id' => '102', 'title' => '影音數據報表', 'parent_id' => '100', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'analytics-dramas', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '103', 'title' => '主分類統計', 'parent_id' => '102', 'type' => '1', 'level' => '2', 'url' => 'admin/analytics/dramas/main-categories', 'url_name' => 'admin.analytics.dramas.main-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '104', 'title' => '子分類統計', 'parent_id' => '102', 'type' => '1', 'level' => '2', 'url' => 'admin/analytics/dramas/sub-categories', 'url_name' => 'admin.analytics.dramas.sub-categories', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                //節目數據報表
                ['id' => '105', 'title' => '節目數據報表', 'parent_id' => '100', 'type' => '1', 'level' => '1', 'url' => '', 'url_name' => 'analytics-programs', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '106', 'title' => '主分類統計', 'parent_id' => '105', 'type' => '1', 'level' => '2', 'url' => 'admin/analytics/programs/main-categories', 'url_name' => 'admin.analytics.programs.main-categories', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '107', 'title' => '子分類統計', 'parent_id' => '105', 'type' => '1', 'level' => '2', 'url' => 'admin/analytics/programs/sub-categories', 'url_name' => 'admin.analytics.programs.sub-categories', 'icon_image' => '', 'status' => '1', 'seq' => '2'],

                //廣播數據報表
                ['id' => '108', 'title' => '廣播數據報表', 'parent_id' => '100', 'type' => '1', 'level' => '1', 'url' => 'admin/analytics/radios', 'url_name' => 'admin.analytics.radios', 'icon_image' => '', 'status' => '1', 'seq' => '4'],

                // ======== 專家系統 ========
                ['id' => '120', 'title' => '專家系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'expert-systems', 'icon_image' => 'fas fa-user-tie', 'status' => '1', 'seq' => '9'],

                // 專家領域管理
                ['id' => '121', 'title' => '專家領域管理', 'parent_id' => '120', 'type' => '1', 'level' => '1', 'url' => 'admin/expert-fields', 'url_name' => 'admin.expert-fields', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '122', 'title' => '新增專家領域', 'parent_id' => '121', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-fields/add', 'url_name' => 'admin.expert-fields.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '123', 'title' => '編輯專家領域', 'parent_id' => '121', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-fields/edit', 'url_name' => 'admin.expert-fields.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '124', 'title' => '刪除專家領域', 'parent_id' => '121', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.expert-fields.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 專家分類管理
                ['id' => '125', 'title' => '專家分類管理', 'parent_id' => '120', 'type' => '1', 'level' => '1', 'url' => 'admin/expert-categories', 'url_name' => 'admin.expert-categories', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '126', 'title' => '新增專家分類', 'parent_id' => '125', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-categories/add', 'url_name' => 'admin.expert-categories.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '127', 'title' => '編輯專家分類', 'parent_id' => '125', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-categories/edit', 'url_name' => 'admin.expert-categories.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '128', 'title' => '刪除專家分類', 'parent_id' => '125', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.expert-categories.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 專家管理
                ['id' => '129', 'title' => '專家管理', 'parent_id' => '120', 'type' => '1', 'level' => '1', 'url' => 'admin/experts', 'url_name' => 'admin.experts', 'icon_image' => '', 'status' => '1', 'seq' => '3'],
                ['id' => '130', 'title' => '新增專家', 'parent_id' => '129', 'type' => '0', 'level' => '2', 'url' => 'admin/experts/add', 'url_name' => 'admin.experts.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '131', 'title' => '編輯專家', 'parent_id' => '129', 'type' => '0', 'level' => '2', 'url' => 'admin/experts/edit', 'url_name' => 'admin.experts.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '132', 'title' => '刪除專家', 'parent_id' => '129', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.experts.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                // 專家專欄管理
                ['id' => '133', 'title' => '專家專欄管理', 'parent_id' => '120', 'type' => '1', 'level' => '1', 'url' => 'admin/expert-articles', 'url_name' => 'admin.expert-articles', 'icon_image' => '', 'status' => '1', 'seq' => '4'],
                ['id' => '134', 'title' => '新增專欄文章', 'parent_id' => '133', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-articles/add', 'url_name' => 'admin.expert-articles.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '135', 'title' => '編輯專欄文章', 'parent_id' => '133', 'type' => '0', 'level' => '2', 'url' => 'admin/expert-articles/edit', 'url_name' => 'admin.expert-articles.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '136', 'title' => '刪除專欄文章', 'parent_id' => '133', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.expert-articles.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

                //其他管理系統（固定最後）
                ['id' => '62', 'title' => '其他管理系統', 'parent_id' => '0', 'type' => '1', 'level' => '0', 'url' => '', 'url_name' => 'other-management-systems', 'icon_image' => 'fa fa-globe', 'status' => '1', 'seq' => '99'],

                //網站基本資訊
                ['id' => '63', 'title' => '網站基本資訊', 'parent_id' => '62', 'type' => '1', 'level' => '1', 'url' => 'admin/basic-website-settings', 'url_name' => 'admin.basic-website-settings', 'icon_image' => '', 'status' => '1', 'seq' => '1'],

                //模組描述管理
                ['id' => '64', 'title' => '模組描述管理', 'parent_id' => '62', 'type' => '1', 'level' => '1', 'url' => 'admin/module-descriptions', 'url_name' => 'admin.module-descriptions', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '65', 'title' => '新增模組描述', 'parent_id' => '64', 'type' => '0', 'level' => '2', 'url' => 'admin/module-descriptions/add', 'url_name' => 'admin.module-descriptions.add', 'icon_image' => '', 'status' => '1', 'seq' => '1'],
                ['id' => '66', 'title' => '編輯模組描述', 'parent_id' => '64', 'type' => '0', 'level' => '2', 'url' => 'admin/module-descriptions/edit', 'url_name' => 'admin.module-descriptions.edit', 'icon_image' => '', 'status' => '1', 'seq' => '2'],
                ['id' => '67', 'title' => '刪除模組描述', 'parent_id' => '64', 'type' => '0', 'level' => '2', 'url' => '', 'url_name' => 'admin.module-descriptions.delete', 'icon_image' => '', 'status' => '1', 'seq' => '3'],

            ],
            ['id'],
            ['title', 'parent_id', 'type', 'url', 'url_name', 'icon_image', 'status', 'seq']
        );
    }
}
