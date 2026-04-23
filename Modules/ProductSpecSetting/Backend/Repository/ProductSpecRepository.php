<?php

namespace Modules\ProductSpecSetting\Backend\Repository;

use App\Repositories\BaseRepository;
use Modules\ProductSpecSetting\Model\SpecGroup;
use Modules\ProductSpecSetting\Model\SpecValue;
use Modules\ProductSpecSetting\Model\SpecCombination;
use Modules\ProductSpecSetting\Model\SpecCombinationGroup;
use Illuminate\Support\Facades\DB;

class ProductSpecRepository extends BaseRepository
{
    protected SpecValue $specValueModel;
    protected SpecCombination $specCombinationModel;
    protected SpecCombinationGroup $specCombinationGroupModel;

    public function __construct(
        SpecGroup $model,
        SpecValue $specValueModel,
        SpecCombination $specCombinationModel,
        SpecCombinationGroup $specCombinationGroupModel
    ) {
        parent::__construct($model);
        $this->specValueModel = $specValueModel;
        $this->specCombinationModel = $specCombinationModel;
        $this->specCombinationGroupModel = $specCombinationGroupModel;
    }

    // ===== Spec Group =====

    public function getAllGroupsOrdered()
    {
        return $this->model->ordered()->with('values')->get();
    }

    public function getActiveGroupsOrdered()
    {
        return $this->model->active()->ordered()->with('activeValues')->get();
    }

    public function findGroupOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function createGroup(array $data)
    {
        return $this->model->create($data);
    }

    public function updateGroup($id, array $data)
    {
        $record = $this->findGroupOrFail($id);
        $record->update($data);
        return $record;
    }

    public function deleteGroup($id)
    {
        $record = $this->findGroupOrFail($id);
        return $record->delete();
    }

    public function batchUpdateGroupSort(array $items): void
    {
        foreach ($items as $item) {
            $this->model->where('id', $item['id'])->update([
                'seq' => (int) ($item['seq'] ?? 0),
            ]);
        }
    }

    // ===== Spec Value =====

    public function getValuesByGroupId($groupId)
    {
        return $this->specValueModel
            ->where('spec_group_id', $groupId)
            ->ordered()
            ->get();
    }

    public function getActiveValuesByGroupId($groupId)
    {
        return $this->specValueModel
            ->where('spec_group_id', $groupId)
            ->active()
            ->ordered()
            ->get();
    }

    public function findValueOrFail($id)
    {
        return $this->specValueModel->findOrFail($id);
    }

    public function createValue(array $data)
    {
        return $this->specValueModel->create($data);
    }

    public function updateValue($id, array $data)
    {
        $record = $this->findValueOrFail($id);
        $record->update($data);
        return $record;
    }

    public function deleteValue($id)
    {
        $record = $this->findValueOrFail($id);
        return $record->delete();
    }

    public function batchUpdateValueSort(array $items): void
    {
        foreach ($items as $item) {
            $this->specValueModel->where('id', $item['id'])->update([
                'seq' => (int) ($item['seq'] ?? 0),
            ]);
        }
    }

    // ===== Spec Combination =====

    public function getAllCombinationsOrdered()
    {
        return $this->specCombinationModel
            ->ordered()
            ->with(['combinationGroups.group'])
            ->get();
    }

    public function findCombinationOrFail($id)
    {
        return $this->specCombinationModel->findOrFail($id);
    }

    public function createCombination(array $data)
    {
        return $this->specCombinationModel->create($data);
    }

    public function updateCombination($id, array $data)
    {
        $record = $this->findCombinationOrFail($id);
        $record->update($data);
        return $record;
    }

    public function deleteCombination($id)
    {
        $record = $this->findCombinationOrFail($id);
        return $record->delete();
    }

    public function createCombinationGroup(array $data)
    {
        return $this->specCombinationGroupModel->create($data);
    }

    public function deleteCombinationGroupsByCombinationId($combinationId): void
    {
        $this->specCombinationGroupModel
            ->where('spec_combination_id', $combinationId)
            ->delete();
    }

    public function batchUpdateCombinationSort(array $items): void
    {
        foreach ($items as $item) {
            $this->specCombinationModel->where('id', $item['id'])->update([
                'seq' => (int) ($item['seq'] ?? 0),
            ]);
        }
    }
}
