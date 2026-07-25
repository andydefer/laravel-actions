<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

final class ActionNormalizerChain
{
    private static ?NormalizerInterface $instance = null;

    private static bool $currentPreserveCase = false;

    private function __construct() {}

    public static function get(bool $preserveRecordCase = false): ActionRootNormalizer
    {
        if (self::$instance === null || self::$currentPreserveCase !== $preserveRecordCase) {
            self::$instance = new ActionRootNormalizer($preserveRecordCase);
            self::$currentPreserveCase = $preserveRecordCase;
        }

        return self::$instance;
    }
}
