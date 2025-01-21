<?php

declare(strict_types=1);

namespace Flexsyscz\DateTime;

use DateTimeImmutable as DateTimeImmutableNative;
use DateTimeZone;
use Exception;
use Flexsyscz\Localization\Translations\TranslatedComponent;
use Nette\Utils\Strings;
use Nextras\Dbal\Utils\DateTimeImmutable;


class DateTimeProvider
{
	use TranslatedComponent;

	/** @var string[] */
	private array $locale;

	private PublicHolidayChecker $publicHolidayChecker;


	public function __construct(
		PublicHolidayChecker $publicHolidayChecker,
		?string $formatDate = null,
		?string $formatTime = null,
	)
	{
		$this->publicHolidayChecker = $publicHolidayChecker;

		$this->locale['date'] = $formatDate ?? DateTimeFormat::Date->value;
		$this->locale['time'] = $formatTime ?? DateTimeFormat::TimeWoSecs->value;
	}


	public static function now(): DateTimeImmutable
	{
		return new DateTimeImmutable;
	}


	/**
	 * @param string $format
	 * @param string $datetime
	 * @param DateTimeZone|null $timezone
	 * @return DateTimeImmutable|false
	 * @throws Exception
	 */
	public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTimeImmutable|false
	{
		if ($format === 'c') {
			try {
				return new DateTimeImmutable($datetime);
			} catch (Exception $e) {
				throw new InvalidDateTimeException('Invalid datetime format', 0, $e);
			}
		}

		$datetimeObject = DateTimeImmutableNative::createFromFormat($format, $datetime, $timezone);
		if ($datetimeObject instanceof DateTimeImmutableNative) {
			$now = self::now();
			if ($timezone) {
				$now = $now->setTimezone($timezone);
			}

			return $now->setTimestamp($datetimeObject->getTimestamp());
		}

		return false;
	}


	public function ago(DateTimeImmutable $ago): string
	{
		$params = [
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		];

		$diff = self::now()->diff($ago);
		$values = [];
		foreach ($params as $k => $n) {
			if ($k === 'w') {
				$values[$n] = (int) (floor($diff->d / 7));
			} else {
				$values[$n] = $diff->$k ?? 0;
			}
		}

		foreach ($values as $n => $v) {
			if ($v > 0) {
				return $this->translatorNamespace->translate(sprintf('ago.%s', $n), $v);
			}
		}

		return $this->translatorNamespace->translate('ago.justNow');
	}


	public function format(DateTimeImmutable $dateTime): string
	{
		return $dateTime->format(sprintf('%s %s', $this->locale['date'], $this->locale['time']));
	}


	public function formatDate(DateTimeImmutable $dateTime): string
	{
		return $dateTime->format($this->locale['date']);
	}


	public function formatTime(DateTimeImmutable $dateTime): string
	{
		return $dateTime->format($this->locale['time']);
	}


	public function formatMonth(DateTimeImmutable $dateTime): string
	{
		return $this->translatorNamespace->translate(sprintf('months.%s', Strings::lower($dateTime->format('F'))));
	}


	public function formatDay(DateTimeImmutable $dateTime): string
	{
		return $this->translatorNamespace->translate(sprintf('days.%s', Strings::lower($dateTime->format('l'))));
	}


	public function formatCallback(DateTimeImmutable $dateTime, callable $callback): string
	{
		return call_user_func($callback, $dateTime);
	}

	
	public function isToday(DateTimeImmutable $dateTime): bool
	{
		$mask = 'Y-m-d';
		return self::now()->format($mask) === $dateTime->format($mask);
	}


	public function isYesterday(DateTimeImmutable $dateTime): bool
	{
		$mask = 'Y-m-d';
		return self::now()->modify('-1 day')->format($mask) === $dateTime->format($mask);
	}


	public function isTomorrow(DateTimeImmutable $dateTime): bool
	{
		$mask = 'Y-m-d';
		return self::now()->modify('+1 day')->format($mask) === $dateTime->format($mask);
	}


	public function isCurrentMonth(DateTimeImmutable $dateTime): bool
	{
		return self::now()->format('F') === $dateTime->format('F');
	}


	public function isCurrentYear(DateTimeImmutable $dateTime): bool
	{
		return self::now()->format('Y') === $dateTime->format('Y');
	}


	public function isPast(DateTimeImmutable $dateTime): bool
	{
		return self::now() > $dateTime;
	}


	public function isFuture(DateTimeImmutable $dateTime): bool
	{
		return self::now() < $dateTime;
	}


	public function isPublicHoliday(DateTimeImmutable $dateTime): bool
	{
		return $this->publicHolidayChecker->check($this->translatorNamespace->translator->getLanguage(), $dateTime);
	}


	public function isWeekend(DateTimeImmutable $dateTime): bool
	{
		return (int) ($dateTime->format('N')) >= 6;
	}
}
