<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Mine\Crontab;

use Hyperf\Crontab\Crontab as Base;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;

class Crontab extends Base
{
    public const TABLE = 'setting_crontab';

    public const TABLE_KEY = 'id';

    public const ENABLE_COLUMN = 'status';

    public const MEMO_COLUMN = 'remark';

    public const TYPE_COLUMN = 'type';

    public const VALUE_COLUMN = 'target';

    public const RULE_COLUMN = 'rule';

    public const NAME_COLUMN = 'name';

    public const IS_ON_ONE_SERVER_COLUMN = 'onOneServer';

    public const IS_SINGLETON = 'singleton';

    public int $cronId = 0;

    public function __construct(int $cronId) {
        $this->cronId = $cronId;
    }

    public function getName(): ?string
    {
        return $this->getBuilder()->value(self::NAME_COLUMN);
    }

    public function isEnable(): bool
    {
        return (bool) $this->getBuilder()->value(self::ENABLE_COLUMN);
    }

    public function getType(): string
    {
        $type = $this->getBuilder()->value(self::TYPE_COLUMN);
        return match ($type) {
            1 => 'command',
            3, 2 => 'callback',
            4 => 'eval',
            default => 'class',
        };
//        $crontab_type = self::CRONTAB_TYPE[$type] ?? $type;
//        return match ($crontab_type) {
//            'url', 'class' => 'callback',
//            default => $crontab_type
//        };
    }

    public function getMemo(): ?string
    {
        return (string) $this->getBuilder()->value(self::MEMO_COLUMN);
    }

    public function getBuilder(): Builder
    {
        return Db::connection(CrontabContainer::$connectionName)->table(self::TABLE)->where(self::TABLE_KEY, $this->cronId);
    }

    /**
     * @throws \JsonException
     */
    public function getCallback(): mixed
    {
        $type = $this->getBuilder()->value(self::TYPE_COLUMN);
        $value = $this->getBuilder()->value(self::VALUE_COLUMN);
        switch ($type) {
            case 'eval':
                return $value;
            case 'url':
                return [
                    CrontabUrl::class,
                    'execute',
                    explode(',', $value),
                ];
            case 'class':
                return [$value, 'execute'];
            case 'command':
            case 'callback':
                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        return $value;
    }

    public function getRule(): ?string
    {
        return $this->getBuilder()->value(self::RULE_COLUMN);
    }

    public function getCronId(): int
    {
        return $this->cronId;
    }

    public function isOnOneServer(): bool
    {
        return $this->getBuilder()->value(self::IS_ON_ONE_SERVER_COLUMN) == 1;
    }

    public function isSingleton(): bool
    {
        return $this->getBuilder()->value(self::IS_SINGLETON) == 1;
    }
}
