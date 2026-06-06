<?php

declare(strict_types=1);

/**
 * CLI bootstrap without Composer autoload: {@see Runtime::getRoot()} must reach the failure branch.
 */
spl_autoload_register(static function (string $class): bool {
    $base = dirname(__DIR__, 2) . '/src/';
    $map = [
        'Switon\\Core\\Runtime' => $base . 'Runtime.php',
        'Switon\\Core\\Exception\\ProjectRootDetectionException' => $base . 'Exception/ProjectRootDetectionException.php',
        'Switon\\Core\\Exception\\RuntimeException' => $base . 'Exception/RuntimeException.php',
        'Switon\\Core\\Exception' => $base . 'Exception.php',
    ];
    if (!isset($map[$class])) {
        return false;
    }
    require $map[$class];

    return true;
});

putenv('SWITON_ROOT');

try {
    \Switon\Core\Runtime::getRoot();
    fwrite(STDERR, "Runtime::getRoot() was expected to throw ProjectRootDetectionException\n");
    exit(1);
} catch (\Switon\Core\Exception\ProjectRootDetectionException) {
    exit(0);
}
