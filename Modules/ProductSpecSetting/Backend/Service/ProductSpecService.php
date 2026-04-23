<?php

namespace Modules\ProductSpecSetting\Backend\Service;

use Modules\ProductSpecSetting\Backend\Repository\ProductSpecRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSpecService
{
    public function __construct(
        private ProductSpecRepository $repository
    ) {}

    /**
     * 根據 config 動態取得模型的多語系欄位
     */
    protected function getTranslatableFields($model, string $attribute): array
    {
        $locales = array_keys(config('translatable.locales', ['zh_TW' => []]));
        $result = [];
        foreach ($locales as $locale) {
            $result[$locale] = $model->getTranslation($attribute, $locale) ?? '';
        }
        return $result;
    }

    protected function primaryLocale(): string
    {
        return config('translatable.primary', 'zh_TW');
    }

    // ========================================
    // Spec Group 規格群組
    // ========================================

    public function getGroupList()
    {
        $groups = $this->repository->getAllGroupsOrdered();

        return $groups->map(function ($group) {
            return $this->formatGroupItem($group);
        })->values()->toArray();
    }

    public function getGroupFormData($id)
    {
        $group = $this->repository->findGroupOrFail($id);
        $group->load('values');

        return [
            'id' => $group->id,
            'name' => $this->getTranslatableFields($group, 'name'),
            'seq' => $group->seq,
            'status' => $group->status,
            'values' => $group->values->map(function ($val) {
                return [
                    'id' => $val->id,
                    'name' => $this->getTranslatableFields($val, 'name'),
                    'seq' => $val->seq,
                    'status' => $val->status,
                ];
            })->toArray(),
        ];
    }

    public function storeGroup(array $data)
    {
        return DB::transaction(function () use ($data) {
            $group = $this->repository->createGroup([
                'name' => $data['name'],
                'seq' => $data['seq'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            if (!empty($data['values'])) {
                foreach ($data['values'] as $index => $valueData) {
                    $this->repository->createValue([
                        'spec_group_id' => $group->id,
                        'name' => $valueData['name'],
                        'seq' => $valueData['seq'] ?? $index,
                        'status' => $valueData['status'] ?? true,
                    ]);
                }
            }

            return ['status' => true, 'msg' => '規格群組新增成功'];
        });
    }

    public function updateGroup($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $this->repository->updateGroup($id, [
                'name' => $data['name'],
                'seq' => $data['seq'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            $existingValues = $this->repository->getValuesByGroupId($id);
            $existingIds = $existingValues->pluck('id')->toArray();
            $incomingIds = [];

            if (!empty($data['values'])) {
                foreach ($data['values'] as $index => $valueData) {
                    if (!empty($valueData['id'])) {
                        $this->repository->updateValue($valueData['id'], [
                            'name' => $valueData['name'],
                            'seq' => $valueData['seq'] ?? $index,
                            'status' => $valueData['status'] ?? true,
                        ]);
                        $incomingIds[] = $valueData['id'];
                    } else {
                        $newValue = $this->repository->createValue([
                            'spec_group_id' => $id,
                            'name' => $valueData['name'],
                            'seq' => $valueData['seq'] ?? $index,
                            'status' => $valueData['status'] ?? true,
                        ]);
                        $incomingIds[] = $newValue->id;
                    }
                }
            }

            $toDelete = array_diff($existingIds, $incomingIds);
            foreach ($toDelete as $deleteId) {
                $this->repository->deleteValue($deleteId);
            }

            return ['status' => true, 'msg' => '規格群組更新成功'];
        });
    }

    public function destroyGroup($id)
    {
        return DB::transaction(function () use ($id) {
            $this->repository->deleteGroup($id);
            return ['status' => true, 'msg' => '規格群組刪除成功'];
        });
    }

    public function toggleGroupActive($id)
    {
        $group = $this->repository->findGroupOrFail($id);
        $group->status = !$group->status;
        $group->save();

        return [
            'status' => true,
            'msg' => $group->status ? '已啟用' : '已停用',
        ];
    }

    public function updateGroupSort(array $items)
    {
        $this->repository->batchUpdateGroupSort($items);
        return ['status' => true, 'msg' => '排序更新成功'];
    }

    // ========================================
    // Spec Value 規格值（獨立操作）
    // ========================================

    public function storeValue($groupId, array $data)
    {
        $this->repository->findGroupOrFail($groupId);

        $value = $this->repository->createValue([
            'spec_group_id' => $groupId,
            'name' => $data['name'],
            'seq' => $data['seq'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return ['status' => true, 'msg' => '規格值新增成功', 'data' => $value];
    }

    public function updateValue($id, array $data)
    {
        $this->repository->updateValue($id, [
            'name' => $data['name'],
            'seq' => $data['seq'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return ['status' => true, 'msg' => '規格值更新成功'];
    }

    public function destroyValue($id)
    {
        $this->repository->deleteValue($id);
        return ['status' => true, 'msg' => '規格值刪除成功'];
    }

    public function toggleValueActive($id)
    {
        $value = $this->repository->findValueOrFail($id);
        $value->status = !$value->status;
        $value->save();

        return [
            'status' => true,
            'msg' => $value->status ? '已啟用' : '已停用',
        ];
    }

    public function updateValueSort(array $items)
    {
        $this->repository->batchUpdateValueSort($items);
        return ['status' => true, 'msg' => '排序更新成功'];
    }

    // ========================================
    // Spec Combination 規格組合（群組搭配）
    // ========================================

    /**
     * 取得所有組合列表
     */
    public function getCombinationList()
    {
        $combinations = $this->repository->getAllCombinationsOrdered();

        return $combinations->map(function ($combo) {
            return $this->formatCombinationItem($combo);
        })->values()->toArray();
    }

    /**
     * 取得單一組合表單資料
     */
    public function getCombinationFormData($id)
    {
        $combo = $this->repository->findCombinationOrFail($id);
        $combo->load('combinationGroups.group');

        return [
            'id' => $combo->id,
            'name' => $this->getTranslatableFields($combo, 'name'),
            'seq' => $combo->seq,
            'status' => $combo->status,
            'group_ids' => $combo->combinationGroups->pluck('spec_group_id')->toArray(),
        ];
    }

    /**
     * 新增規格組合（選擇哪些群組搭配）
     *
     * @param array $data 包含 name, group_ids (array), seq, status
     */
    public function storeCombination(array $data)
    {
        return DB::transaction(function () use ($data) {
            $groupIds = $data['group_ids'] ?? [];

            if (empty($groupIds)) {
                return ['status' => false, 'msg' => '請至少選擇一個規格群組'];
            }

            // 驗證每個 group_id 存在
            foreach ($groupIds as $groupId) {
                $this->repository->findGroupOrFail($groupId);
            }

            // 建立組合
            $combination = $this->repository->createCombination([
                'name' => $data['name'],
                'seq' => $data['seq'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            // 建立組合 ↔ 群組關聯
            foreach ($groupIds as $groupId) {
                $this->repository->createCombinationGroup([
                    'spec_combination_id' => $combination->id,
                    'spec_group_id' => $groupId,
                ]);
            }

            return ['status' => true, 'msg' => '規格組合新增成功'];
        });
    }

    /**
     * 更新規格組合（名稱 + 群組搭配）
     */
    public function updateCombination($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $groupIds = $data['group_ids'] ?? [];

            if (empty($groupIds)) {
                return ['status' => false, 'msg' => '請至少選擇一個規格群組'];
            }

            foreach ($groupIds as $groupId) {
                $this->repository->findGroupOrFail($groupId);
            }

            $this->repository->updateCombination($id, [
                'name' => $data['name'],
                'seq' => $data['seq'] ?? 0,
                'status' => $data['status'] ?? true,
            ]);

            // 重建關聯
            $this->repository->deleteCombinationGroupsByCombinationId($id);
            foreach ($groupIds as $groupId) {
                $this->repository->createCombinationGroup([
                    'spec_combination_id' => $id,
                    'spec_group_id' => $groupId,
                ]);
            }

            return ['status' => true, 'msg' => '規格組合更新成功'];
        });
    }

    /**
     * 刪除規格組合
     */
    public function destroyCombination($id)
    {
        $this->repository->deleteCombination($id);
        return ['status' => true, 'msg' => '規格組合刪除成功'];
    }

    /**
     * 切換組合啟用狀態
     */
    public function toggleCombinationActive($id)
    {
        $combo = $this->repository->findCombinationOrFail($id);
        $combo->status = !$combo->status;
        $combo->save();

        return [
            'status' => true,
            'msg' => $combo->status ? '已啟用' : '已停用',
        ];
    }

    // ========================================
    // 前台 API
    // ========================================

    /**
     * 完整規格結構（群組 + 值 + 組合定義）
     */
    public function getSpecStructure($locale = 'zh_TW')
    {
        $groups = $this->repository->getActiveGroupsOrdered();
        $combinations = $this->repository->getAllCombinationsOrdered()->where('status', true);

        return [
            'groups' => $groups->map(function ($group) use ($locale) {
                return [
                    'id' => $group->id,
                    'name' => $group->getTranslation('name', $locale),
                    'values' => $group->activeValues->map(function ($val) use ($locale) {
                        return [
                            'id' => $val->id,
                            'name' => $val->getTranslation('name', $locale),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
            'combinations' => $combinations->map(function ($combo) use ($locale) {
                return [
                    'id' => $combo->id,
                    'name' => $combo->getTranslation('name', $locale),
                    'group_ids' => $combo->combinationGroups->pluck('spec_group_id')->toArray(),
                    'groups' => $combo->combinationGroups
                        ->sortBy(fn($cg) => $cg->group->seq ?? 0)
                        ->map(function ($cg) use ($locale) {
                            return [
                                'id' => $cg->spec_group_id,
                                'name' => $cg->group->getTranslation('name', $locale),
                            ];
                        })->values()->toArray(),
                ];
            })->values()->toArray(),
        ];
    }

    // ========================================
    // Helper
    // ========================================

    protected function formatGroupItem($group)
    {
        $primary = $this->primaryLocale();

        return [
            'id' => $group->id,
            'name' => $group->name,
            'name_primary' => $group->getTranslation('name', $primary),
            'seq' => $group->seq,
            'status' => $group->status,
            'values_count' => $group->values->count(),
            'values' => $group->values->map(function ($val) use ($primary) {
                return [
                    'id' => $val->id,
                    'name' => $val->name,
                    'name_primary' => $val->getTranslation('name', $primary),
                    'seq' => $val->seq,
                    'status' => $val->status,
                ];
            })->toArray(),
        ];
    }

    protected function formatCombinationItem($combo)
    {
        $primary = $this->primaryLocale();
        $groups = $combo->combinationGroups
            ->sortBy(fn($cg) => $cg->group->seq ?? 0)
            ->values();

        $groupNames = $groups->map(fn($cg) => $cg->group->getTranslation('name', $primary));

        return [
            'id' => $combo->id,
            'name' => $combo->name,
            'name_zh' => $combo->getTranslation('name', $primary),
            'seq' => $combo->seq,
            'status' => $combo->status,
            'label' => $groupNames->implode(' + '),
            'group_ids' => $groups->pluck('spec_group_id')->toArray(),
            'groups' => $groups->map(function ($cg) use ($primary) {
                return [
                    'id' => $cg->spec_group_id,
                    'name_primary' => $cg->group->getTranslation('name', $primary),
                ];
            })->toArray(),
        ];
    }
}
