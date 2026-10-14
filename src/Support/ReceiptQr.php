<?php

namespace Amarenkov\MutableContentFinance\Support;

use Throwable;

use Illuminate\Support\Carbon;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Money;

/**
 * QR code of a Russian fiscal receipt: t=20210307T120900&s=9680.84&fn=...&i=...&fp=...&n=1.
 * The time is local to the shop and has no timezone: it is read in the display timezone.
 */
final readonly class ReceiptQr
{
    // const
    public const TYPE_INCOME = 1;
    public const TYPE_INCOME_RETURN = 2;
    public const TYPE_EXPENSE = 3;
    public const TYPE_EXPENSE_RETURN = 4;

    protected const TIME_FORMATS = ['Ymd\THis', 'Ymd\THi'];

    // static
    public static function parse(string $qr): ?self
    {
        parse_str(trim($qr), $data);

        foreach (['t', 's', 'fn', 'i', 'fp'] as $key) {
            if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
                return null;
            }
        }

        if (!Money::isAmount($data['s'])) {
            return null;
        }

        foreach (self::TIME_FORMATS as $format) {
            try {
                $time = Carbon::createFromFormat('!'.$format, $data['t'], ModelWithFields::displayTimezone());
            } catch (Throwable) {
                continue;
            }

            if ($time instanceof Carbon && $time->format($format) === $data['t']) {
                return new self($time, Money::toDecimalString($data['s']), $data['fn'], $data['i'], $data['fp'], (int)($data['n'] ?? self::TYPE_INCOME));
            }
        }

        return null;
    }

    // public
    /**
     * @param string $total amount as a decimal string
     * @param string $fn fiscal drive number
     * @param string $fd fiscal document number
     * @param string $fp fiscal sign
     * @param int $type operation type: 1 income (a purchase), 2 its return, 3 expense, 4 its return
     */
    public function __construct(
        public Carbon $time,
        public string $total,
        public string $fn,
        public string $fd,
        public string $fp,
        public int $type
    )
    {
    }

    /**
     * Fiscal document identity: the same receipt scanned twice has the same key.
     */
    public function key(): string
    {
        return $this->fn.'/'.$this->fd.'/'.$this->fp;
    }
}
