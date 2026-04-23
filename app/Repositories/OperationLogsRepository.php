<?php

namespace App\Repositories;

use App\Models\OperationLog;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\CommonTrait;

class OperationLogsRepository extends BaseRepository
{
    public function __construct(OperationLog $operation_log)
    {
        parent::__construct($operation_log);
    }

    /**
     * 獲取get
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function  get(){
        return $this->model->orderBy('seq','asc')->orderBy('updated_at','desc')->get();;
    }

    public function  record(){
        return $this->model->with('created_user')->latest()->take(10)->get();
    }


    public function  paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc',$filters = []){
        return $this->model->orderBy($sortColumn, $sortDirection)
        ->filter($filters)
        ->paginate($perPage)
        ->withQueryString()
        ->through(fn ($log) => [
            'id' => $log->id,
            'name' => $log->created_user->name,
            'email' => $log->created_user->email,
            'ip_address' => $log->ip_address,
            'message' => $log->message,
            'created_at' => $log->created_at->toDateTimeString(),
        ]);
    }

}
