<?php
namespace App\Services;

use App\Repositories\OperationLogsRepository;


class OperationLogService extends BaseService
{
    public function __construct(private OperationLogsRepository $operationLog ) {
        parent::__construct($operationLog);
    }

    public function  record(){
        return $this->operationLog->record();
    }

}
