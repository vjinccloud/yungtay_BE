<?php

namespace App\Repositories;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;

class SocialAccountRepository extends BaseRepository
{
    public function __construct(SocialAccount $socialAccount)
    {
        parent::__construct($socialAccount);
    }

    /**
     * 根據提供商和提供商ID查找社群帳號
     */
    public function findByProviderAndId($provider, $providerId)
    {
        return $this->model->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->with('user')
            ->first();
    }

    /**
     * 根據用戶和提供商查找社群帳號
     */
    public function findByUserAndProvider($userId, $provider)
    {
        return $this->model->where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * 取得用戶的所有社群帳號
     */
    public function getUserSocialAccounts($userId)
    {
        return $this->model->where('user_id', $userId)
            ->get();
    }

    /**
     * 建立社群帳號（含事務）
     */
    public function createWithTransaction(array $data)
    {
        try {
            DB::beginTransaction();
            
            $socialAccount = $this->model->create($data);
            
            DB::commit();
            return $socialAccount;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 刪除社群帳號（含事務）
     */
    public function deleteWithTransaction($id)
    {
        try {
            DB::beginTransaction();
            
            $socialAccount = $this->model->findOrFail($id);
            $socialAccount->delete();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}