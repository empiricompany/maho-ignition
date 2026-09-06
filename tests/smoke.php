<?php

// SPDX-License-Identifier: OSL-3.0

declare(strict_types=1);

$root = dirname(__DIR__);
$package = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
if (($package['name'] ?? null) !== 'empiricompany/maho-ignition') {
    fwrite(STDERR, "The package must be named empiricompany/maho-ignition.\n");
    exit(1);
}
$xmlFiles = [
    $root . '/app/etc/modules/Maho_Ignition.xml',
    $root . '/app/code/community/Maho/Ignition/etc/config.xml',
    $root . '/app/code/community/Maho/Ignition/etc/system.xml',
];

foreach ($xmlFiles as $file) {
    if (!is_file($file) || simplexml_load_file($file) === false) {
        fwrite(STDERR, "Invalid or missing XML: {$file}\n");
        exit(1);
    }
}

$moduleXml = simplexml_load_file($xmlFiles[0]);
if ((string) $moduleXml->modules->Maho_Ignition->active !== 'true') {
    fwrite(STDERR, "Maho_Ignition must remain active.\n");
    exit(1);
}

$configContents = file_get_contents($xmlFiles[1]);
if ($configContents === false || preg_match('/<observers>|<controller_front_init_before>|<mage_run_exception>|<mage_run_installed_exception>|<controller_front_init_routers>/', $configContents)) {
    fwrite(STDERR, "Duplicate XML observer registrations must be absent.\n");
    exit(1);
}

$attributeChecks = [
    ['app/code/community/Maho/Ignition/Model/Observer/HandleIgnitionRegister.php', "#[Maho\\Config\\Observer('controller_front_init_before')]"],
    ['app/code/community/Maho/Ignition/Model/Observer/HandleIgnitionException.php', "#[Maho\\Config\\Observer('mage_run_exception')]"],
    ['app/code/community/Maho/Ignition/Model/Observer/HandleIgnitionException.php', "#[Maho\\Config\\Observer('mage_run_installed_exception')]"],
    ['app/code/community/Maho/Ignition/Controller/Router.php', "#[Maho\\Config\\Observer('controller_front_init_routers')]"],
];
foreach ($attributeChecks as [$file, $attribute]) {
    $contents = file_get_contents($root . '/' . $file);
    if ($contents === false || !str_contains($contents, $attribute)) {
        fwrite(STDERR, "Missing observer attribute: {$attribute}\n");
        exit(1);
    }
}

$abstractContents = file_get_contents($root . '/app/code/community/Maho/Ignition/Model/Observer/Abstract.php');
if ($abstractContents === false
    || !str_contains($abstractContents, '->shouldDisplayException($this->getHelper()->shouldPrintIgnition() && Mage::getIsDeveloperMode())')
    || !str_contains($abstractContents, '$ignition->getFlare()->report($exception, report: $report)')
) {
    fwrite(STDERR, "Developer-mode Flare reporting workaround is missing.\n");
    exit(1);
}

echo "Maho Ignition smoke checks passed.\n";
