<?php

declare(strict_types=1);

namespace CarMoneyLab\Tests\Unit;

use CarMoneyLab\Domain\MileageRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MileageRuleTest extends TestCase
{
    private MileageRule $rule;

    protected function setUp(): void
    {
        $this->rule = new MileageRule(400000);
    }

    #[DataProvider('mileages')]
    public function testRequiresReviewAtAndAboveThreshold(int $mileage, bool $expected): void
    {
        self::assertSame($expected, $this->rule->requiresReview($mileage));
    }

    /** @return array<string,array{int,bool}> */
    public static function mileages(): array
    {
        return [
            'сразу под порогом' => [399999, false],
            'ровно порог (граница включительна)' => [400000, false],
            'сразу над порогом' => [400001, true],
            'нижняя граница диапазона' => [0, false],
            'верхняя граница валидации' => [500000, true],
        ];
    }
}
