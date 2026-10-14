<?php

namespace Amarenkov\MutableContentFinance\Services\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

use Amarenkov\MutableContent\Models\ModelWithFields;

/**
 * Who does the action and why: the user becomes the change log author.
 */
trait ActsAs
{
    // protected
    protected ?int $userId = null;

    protected ?string $logComment = null;

    protected function prepareLog(ModelWithFields $model): void
    {
        $model->withLogContext($this->logComment)->user($this->userId);
    }

    // public
    public function by(Authenticatable|int|null $user): static
    {
        $this->userId = $user instanceof Authenticatable ? (int)$user->getAuthIdentifier() : $user;

        return $this;
    }

    public function logComment(?string $comment): static
    {
        $this->logComment = $comment;

        return $this;
    }
}
