<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Tests\Fixtures\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\Actions\Tests\Fixtures\Records\GetUserPostsRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class GetUserPostsRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            // Pas de règles spécifiques pour cet exemple
        ];
    }

    public function getRecord(): AbstractRecord
    {
        return GetUserPostsRecord::from([
            'userId' => (int) $this->route('userId'),
            'postId' => (int) $this->route('postId'),
        ]);
    }
}
