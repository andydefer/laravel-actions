<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Actions;

use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;
use Exception;

abstract class AbstractAction
{
    private AbstractRecord $recordRequest;

    final public function run(AbstractRecord $recordRequest = new EmptyRecord): ResponseFactory
    {
        $this->recordRequest = $recordRequest;

        try {
            $this->before($recordRequest);
            $response = $this->handle($recordRequest);
            $this->after(true, null, $recordRequest);

            return $response;
        } catch (Exception $e) {
            $this->after(false, $e, $recordRequest);
            throw $e;
        }
    }

    protected function before(AbstractRecord $recordRequest): void
    {
        // Override in concrete actions
    }

    abstract protected function handle(AbstractRecord $recordRequest): ResponseFactory;

    protected function after(bool $success, ?Exception $error = null, AbstractRecord $recordRequest = new EmptyRecord): void
    {
        // Override in concrete actions
    }

    public function getRecordRequest(): AbstractRecord
    {
        return $this->recordRequest;
    }
}
