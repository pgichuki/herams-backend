<?php
declare(strict_types=1);

namespace prime\components;

use CrEOF\Geo\WKB\Parser;
use Ramsey\Uuid\Uuid;

class Formatter extends \yii\i18n\Formatter
{
    public const FORMAT_UUID = 'uuid';
    public const FORMAT_COORDS = 'coords';
    public function asUuid(string|null $value)
    {
        return isset($value) ? Uuid::fromBytes($value) : null;
    }

    public function asCoords($value)
    {
        $parser = new Parser();
        return isset($value) ? $parser->parse($value) : null;
    }
}
