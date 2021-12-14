<?php

/**
 * Test: Flexsyscz\DateTime\DateTimeProvider
 */

declare(strict_types=1);


use Tester\Assert;
use Tracy\Logger;
use Flexsyscz\DateTime;
use Flexsyscz\Localization;
use Nextras\Dbal\Utils\DateTimeImmutable;

require __DIR__ . '/../bootstrap.php';

enum SupportedLanguages: string {
	case CZECH = 'cs_CZ';
	case ENGLISH = 'en_US';
	case SLOVAK = 'sk_SK';
}

$logDir = getLogDir();
$logger = new Logger($logDir);

$properties = new Localization\EnvironmentProperties();
$properties->supportedLanguages = SupportedLanguages::cases();
$properties->appDir = __DIR__ . '/../';
$properties->translationsDirectoryName = 'translations';
$properties->logging = true;
$properties->debugMode = true;

$dateTimeProvider = new DateTime\DateTimeProvider([
	'date' => 'Y-m-d',
	'time' => 'H:i:s'
], ['Y-04-01']);

$environment = new Localization\Environment($properties, $logger);
$dictionariesRepository = new Localization\DictionariesRepository($environment);
$translator = new Localization\Translator($dictionariesRepository);
$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);
$dateTimeProvider->injectTranslator(new Localization\TranslatorNamespaceFactory($translator, $dictionariesRepository));

test('', function () use ($dateTimeProvider) {
	$a = new DateTimeImmutable();
	$b = $a->setDate(2020, 4, 1)
		->setTime(22, 15, 36);

	Assert::equal('2020-04-01 22:15:36', $dateTimeProvider->format($b));

	Assert::true($dateTimeProvider->isPublicHoliday($b));

	$c = $a->setDate(2022, 4, 15);
	Assert::true($dateTimeProvider->isPublicHoliday($c)); // easter (friday)

	$c = $a->setDate(2022, 4, 18);
	Assert::true($dateTimeProvider->isPublicHoliday($c)); // easter (monday)
});