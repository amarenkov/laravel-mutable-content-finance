<?php

namespace Amarenkov\MutableContentFinance\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Label as CommonFieldLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentFinance\Lovs\CategoryKind;
use Amarenkov\MutableContentFinance\Support\Users;

/**
 * What money is spent on or comes from: Food, Cafe, Salary. Categories form a tree; an operation is split by them.
 */
#[Table('finance.categories')]
#[ClassLabel('Category')]
#[CommonFieldLabel]
class Category extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_INT), CFALabel('Owner'), CFAIsRequired, CFAIsImmutable]
    public const FIELD_USER_ID = 'user_id';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Kind'), CFAIsRequired, CFALovCode(CategoryKind::CLASS_CODE)]
    public const FIELD_KIND = 'kind';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Parent'), CFAObjectClass(Category::class)]
    public const FIELD_PARENT_ID = 'parent_id';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    // public
    public function scopeOwnedBy(Builder $query, Authenticatable|int $user): Builder
    {
        return $query->whereField(self::FIELD_USER_ID, $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::model(), self::FIELD_USER_ID);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, self::FIELD_PARENT_ID);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, self::FIELD_PARENT_ID);
    }

    /**
     * @return HasMany<Split, $this>
     */
    public function splits(): HasMany
    {
        return $this->hasMany(Split::class, Split::FIELD_CATEGORY_ID);
    }
}
