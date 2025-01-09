<?php

declare(strict_types=1);

namespace Tests\DateTime;

use Flexsyscz\DateTime\DateTimeFormat;
use Flexsyscz\DateTime\DateTimeProvider;
use Flexsyscz\DateTime\PublicHolidayChecker;
use Flexsyscz\Localization;
use Nextras\Dbal\Utils\DateTimeImmutable;
use Tester\Assert;
use Tester\TestCase;
use Tests\Resources\SupportedLanguages;
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class DateTimeProviderTest extends TestCase
{
	private string $logDir;

	private Localization\Translator $translator;
	private DateTimeProvider $dateTimeProvider;


	public function setUp(): void
	{
		$this->logDir = __DIR__ . '/../log/' . getmypid();
		if(!is_dir($this->logDir)) {
			@mkdir($this->logDir);
		}

		$properties = new Localization\EnvironmentProperties();
		$properties->supportedLanguages = SupportedLanguages::cases();
		$properties->appDir = __DIR__ . '/../';
		$properties->translationsDirectoryName = 'translations';
		$properties->defaultNamespace = 'Flexsyscz\DateTime\DateTimeProvider';
		$properties->logging = true;
		$properties->debugMode = true;

		$dateTimeProvider = new DateTimeProvider(new PublicHolidayChecker());

		$logger = new Logger($this->logDir);
		$environment = new Localization\Environment($properties, $logger);
		$dictionariesRepository = new Localization\DictionariesRepository($environment);
		$translator = new Localization\Translator($dictionariesRepository);
		$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);
		$dateTimeProvider->injectTranslator(new Localization\TranslatorNamespaceFactory($translator, $dictionariesRepository));

		$this->translator = $translator;
		$this->dateTimeProvider = $dateTimeProvider;
	}

	public function testLanguageCs(): void
	{
		$dateTimeProvider = $this->dateTimeProvider;

		$this->translator->setLanguage(SupportedLanguages::CZECH->value);

		$a = new DateTimeImmutable();
		$b = $a->setDate(2020, 10, 28)
			->setTime(16, 8, 0);

		Assert::equal('28. 10. 2020 16:08', $dateTimeProvider->format($b));

		Assert::true($dateTimeProvider->isPublicHoliday($b));

		Assert::true($dateTimeProvider->isToday($a));
		Assert::false($dateTimeProvider->isToday($b));

		Assert::true($dateTimeProvider->isYesterday($a->modify('-1 day')));
		Assert::true($dateTimeProvider->isTomorrow($a->modify('+1 day')));

		Assert::true($dateTimeProvider->isPast($a->modify('-1 month')));
		Assert::true($dateTimeProvider->isFuture($a->modify('+1 month')));

		Assert::false($dateTimeProvider->isWeekend($b));
		Assert::true($dateTimeProvider->isWeekend($b->modify('+3 days'))); // saturday 31. 10. 2020

		Assert::true($dateTimeProvider->isCurrentMonth($a));
		Assert::false($dateTimeProvider->isCurrentMonth($b));

		Assert::true($dateTimeProvider->isCurrentYear($a));
		Assert::false($dateTimeProvider->isCurrentYear($b));

		Assert::equal('středa', $dateTimeProvider->formatDay($b));
		Assert::equal('říjen', $dateTimeProvider->formatMonth($b));

		Assert::equal('28. 10. 2020', $dateTimeProvider->formatDate($b));
		Assert::equal('16:08', $dateTimeProvider->formatTime($b));

		Assert::equal('právě teď', $dateTimeProvider->ago($a));
		Assert::equal('před chvílí', $dateTimeProvider->ago($a->modify('-1 second')));
		Assert::equal('před chvílí', $dateTimeProvider->ago($a->modify('-2 seconds')));
		Assert::equal('před 3 sek.', $dateTimeProvider->ago($a->modify('-3 seconds')));
		Assert::equal('před 5 sek.', $dateTimeProvider->ago($a->modify('-5 seconds')));

		Assert::equal('před minutou', $dateTimeProvider->ago($a->modify('-1 minute')));
		Assert::equal('před 4 min.', $dateTimeProvider->ago($a->modify('-4 minutes')));

		Assert::equal('před hodinou', $dateTimeProvider->ago($a->modify('-1 hour')));
		Assert::equal('před 2 hod.', $dateTimeProvider->ago($a->modify('-2 hours')));

		Assert::equal('včera', $dateTimeProvider->ago($a->modify('-1 day')));
		Assert::equal('předevčírem', $dateTimeProvider->ago($a->modify('-2 days')));
		Assert::equal('před 5 dny', $dateTimeProvider->ago($a->modify('-5 days')));

		Assert::equal('před týdnem', $dateTimeProvider->ago($a->modify('-1 week')));
		Assert::equal('před 2 týdny', $dateTimeProvider->ago($a->modify('-2 weeks')));

		Assert::equal('před měsícem', $dateTimeProvider->ago($a->modify('-1 month')));
		Assert::equal('před 2 měsíci', $dateTimeProvider->ago($a->modify('-2 months')));

		Assert::equal('před rokem', $dateTimeProvider->ago($a->modify('-1 year')));
		Assert::equal('před 2 roky', $dateTimeProvider->ago($a->modify('-2 years')));
	}


	public function testLanguageEn(): void
	{
		$dateTimeProvider = $this->dateTimeProvider;

		$this->translator->setLanguage(SupportedLanguages::ENGLISH->value);

		$a = new DateTimeImmutable();
		$b = $a->setDate(2020, 01, 01)
			->setTime(16, 8);
		$c = $a->setDate(2020, (int) date('n') + 1, 02)
			->setTime(9, 30);

		Assert::equal('1. 1. 2020 16:08', $dateTimeProvider->format($b));

		Assert::true($dateTimeProvider->isPublicHoliday($b));

		Assert::true($dateTimeProvider->isToday($a));
		Assert::false($dateTimeProvider->isToday($b));

		Assert::true($dateTimeProvider->isYesterday($a->modify('-1 day')));
		Assert::true($dateTimeProvider->isTomorrow($a->modify('+1 day')));

		Assert::true($dateTimeProvider->isPast($a->modify('-1 month')));
		Assert::true($dateTimeProvider->isFuture($a->modify('+1 month')));

		Assert::false($dateTimeProvider->isWeekend($b));
		Assert::true($dateTimeProvider->isWeekend($b->modify('+3 days'))); // saturday 4. 1. 2023

		Assert::true($dateTimeProvider->isCurrentMonth($a));
		Assert::false($dateTimeProvider->isCurrentMonth($c));

		Assert::true($dateTimeProvider->isCurrentYear($a));
		Assert::false($dateTimeProvider->isCurrentYear($b));

		Assert::equal('wednesday', $dateTimeProvider->formatDay($b));
		Assert::equal('january', $dateTimeProvider->formatMonth($b));

		Assert::equal('1. 1. 2020', $dateTimeProvider->formatDate($b));
		Assert::equal('16:08', $dateTimeProvider->formatTime($b));

		Assert::equal('just now', $dateTimeProvider->ago($a));
		Assert::equal('few moments ago', $dateTimeProvider->ago($a->modify('-1 second')));
		Assert::equal('few moments ago', $dateTimeProvider->ago($a->modify('-2 seconds')));
		Assert::equal('3 secs. ago', $dateTimeProvider->ago($a->modify('-3 seconds')));
		Assert::equal('5 secs. ago', $dateTimeProvider->ago($a->modify('-5 seconds')));

		Assert::equal('a minute ago', $dateTimeProvider->ago($a->modify('-1 minute')));
		Assert::equal('4 mins. ago', $dateTimeProvider->ago($a->modify('-4 minutes')));

		Assert::equal('an hour ago', $dateTimeProvider->ago($a->modify('-1 hour')));
		Assert::equal('2 hrs. ago', $dateTimeProvider->ago($a->modify('-2 hours')));

		Assert::equal('yesterday', $dateTimeProvider->ago($a->modify('-1 day')));
		Assert::equal('2 days ago', $dateTimeProvider->ago($a->modify('-2 days')));
		Assert::equal('5 days ago', $dateTimeProvider->ago($a->modify('-5 days')));

		Assert::equal('a week ago', $dateTimeProvider->ago($a->modify('-1 week')));
		Assert::equal('2 weeks ago', $dateTimeProvider->ago($a->modify('-2 weeks')));

		Assert::equal('a month ago', $dateTimeProvider->ago($a->modify('-1 month')));
		Assert::equal('2 months ago', $dateTimeProvider->ago($a->modify('-2 months')));

		Assert::equal('last year', $dateTimeProvider->ago($a->modify('-1 year')));
		Assert::equal('2 years ago', $dateTimeProvider->ago($a->modify('-2 years')));
	}


	public function testLanguageSk(): void
	{
		$dateTimeProvider = $this->dateTimeProvider;

		$this->translator->setLanguage(SupportedLanguages::SLOVAK->value);

		$a = new DateTimeImmutable();
		$b = $a->setDate(2020, 10, 28)
			->setTime(16, 8, 0);

		Assert::equal('28. 10. 2020 16:08', $dateTimeProvider->format($b));

		Assert::true($dateTimeProvider->isPublicHoliday($b));

		Assert::true($dateTimeProvider->isToday($a));
		Assert::false($dateTimeProvider->isToday($b));

		Assert::true($dateTimeProvider->isYesterday($a->modify('-1 day')));
		Assert::true($dateTimeProvider->isTomorrow($a->modify('+1 day')));

		Assert::true($dateTimeProvider->isPast($a->modify('-1 month')));
		Assert::true($dateTimeProvider->isFuture($a->modify('+1 month')));

		Assert::false($dateTimeProvider->isWeekend($b));
		Assert::true($dateTimeProvider->isWeekend($b->modify('+3 days'))); // saturday 31. 10. 2020

		Assert::true($dateTimeProvider->isCurrentMonth($a));
		Assert::false($dateTimeProvider->isCurrentMonth($b));

		Assert::true($dateTimeProvider->isCurrentYear($a));
		Assert::false($dateTimeProvider->isCurrentYear($b));

		Assert::equal('streda', $dateTimeProvider->formatDay($b));
		Assert::equal('október', $dateTimeProvider->formatMonth($b));

		Assert::equal('28. 10. 2020', $dateTimeProvider->formatDate($b));
		Assert::equal('16:08', $dateTimeProvider->formatTime($b));

		Assert::equal('práve teraz', $dateTimeProvider->ago($a));
		Assert::equal('pred chvíľou', $dateTimeProvider->ago($a->modify('-1 second')));
		Assert::equal('pred chvíľou', $dateTimeProvider->ago($a->modify('-2 seconds')));
		Assert::equal('pred 5 sek.', $dateTimeProvider->ago($a->modify('-5 seconds')));

		Assert::equal('pred minútou', $dateTimeProvider->ago($a->modify('-1 minute')));
		Assert::equal('pred 4 min.', $dateTimeProvider->ago($a->modify('-4 minutes')));

		Assert::equal('pred hodinou', $dateTimeProvider->ago($a->modify('-1 hour')));
		Assert::equal('pred 2 hod.', $dateTimeProvider->ago($a->modify('-2 hours')));

		Assert::equal('včera', $dateTimeProvider->ago($a->modify('-1 day')));
		Assert::equal('predvčerom', $dateTimeProvider->ago($a->modify('-2 days')));
		Assert::equal('pred 5 dňami', $dateTimeProvider->ago($a->modify('-5 days')));

		Assert::equal('pred týždňom', $dateTimeProvider->ago($a->modify('-1 week')));
		Assert::equal('pred 2 týždňami', $dateTimeProvider->ago($a->modify('-2 weeks')));

		Assert::equal('pred mesiacom', $dateTimeProvider->ago($a->modify('-1 month')));
		Assert::equal('pred 2 mesiacmi', $dateTimeProvider->ago($a->modify('-2 months')));

		Assert::equal('pred rokom', $dateTimeProvider->ago($a->modify('-1 year')));
		Assert::equal('pred 2 rokmi', $dateTimeProvider->ago($a->modify('-2 years')));
	}


	public function testCustomConfig(): void
	{
		$properties = new Localization\EnvironmentProperties();
		$properties->supportedLanguages = SupportedLanguages::cases();
		$properties->appDir = __DIR__ . '/../';
		$properties->translationsDirectoryName = 'translations';
		$properties->defaultNamespace = 'Flexsyscz\DateTime\DateTimeProvider';
		$properties->logging = true;
		$properties->debugMode = true;

		$publicHolidayChecker = new PublicHolidayChecker(['cs_CZ' => ['Y-04-01']]);
		$dateTimeProvider = new DateTimeProvider($publicHolidayChecker, DateTimeFormat::Ymd->value, DateTimeFormat::Time->value);

		$logger = new Logger($this->logDir);

		$environment = new Localization\Environment($properties, $logger);
		$dictionariesRepository = new Localization\DictionariesRepository($environment);
		$translator = new Localization\Translator($dictionariesRepository);
		$translator->setup(SupportedLanguages::CZECH->value, SupportedLanguages::ENGLISH->value);
		$dateTimeProvider->injectTranslator(new Localization\TranslatorNamespaceFactory($translator, $dictionariesRepository));


		$a = new DateTimeImmutable();
		$b = $a->setDate(2020, 4, 1)
			->setTime(22, 15, 36);

		Assert::equal('2020-04-01 22:15:36', $dateTimeProvider->format($b));

		Assert::true($dateTimeProvider->isPublicHoliday($b));

		$c = $a->setDate(2022, 4, 15);
		Assert::true($dateTimeProvider->isPublicHoliday($c)); // easter (friday)

		$c = $a->setDate(2022, 4, 18);
		Assert::true($dateTimeProvider->isPublicHoliday($c)); // easter (monday)

		$translator->setLanguage(SupportedLanguages::SLOVAK->value);
		Assert::false($dateTimeProvider->isPublicHoliday($b)); // sk_SK not defined
	}
}

(new DateTimeProviderTest)->run();
