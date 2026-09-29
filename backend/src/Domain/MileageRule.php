<?php

declare(strict_types=1);

namespace CarMoneyLab\Domain;

/**
 * Правило решения по пробегу: пробег известен и строго выше порога → review.
 * Неизвестный пробег (`null`) этим правилом не обрабатывается — это зона ответственности
 * сервиса оценки, там у неизвестного пробега своя причина review.
 */
final class MileageRule
{
    public function __construct(private readonly int $reviewAboveKm)
    {
    }

    public function requiresReview(int $mileage): bool
    {
        return $mileage > $this->reviewAboveKm;
    }

    public function threshold(): int
    {
        return $this->reviewAboveKm;
    }
}