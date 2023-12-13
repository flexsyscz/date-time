<?php

declare(strict_types=1);

namespace Flexsyscz\DateTime;

use DateTimeImmutable as DateTimeImmutableNative;
use DateTimeZone;
use Flexsyscz\Localization\TranslatedComponent;
use Nette\Utils\Strings;
use Nextras\Dbal\Utils\DateTimeImmutable;


class DateTimeProvider
{
	use TranslatedComponent;

	/** @var string[] */
	private array $locale;

	private PublicHolidayChecker $publicHolidayChecker;


	/**
	 * @param array|string[] $format
	 * @param PublicHolidayChecker $publicHolidayChecker
	 */
	public function __construct(
		PublicHolidayChecker $publicHolidayChecker,
		array $format = ['date' => 'j. n. Y', 'time' => 'H:i'],
	)
	{
		$this->publicHolidayChecker = $publicHolidayChecker;

		$this->locale['date'] = $format['date'];
		$this->locale['time'] = $format['time'];
	}


	public static function now(): DateTimeImmutable
	{
		return new DateTimeImmutable;
	}


	public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): DateTimeImmutable|false
	{
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
		return self::now()->getTimestamp() > $dateTime->getTimestamp();
	}


	public function isFuture(DateTimeImmutable $dateTime): bool
	{
		return self::now()->getTimestamp() < $dateTime->getTimestamp();
	}


	public function isPublicHoliday(DateTimeImmutable $dateTime): bool
	{
		return $this->publicHolidayChecker->check($this->translatorNamespace->getTranslator()->getLanguage(), $dateTime);
	}


	public function isWeekend(DateTimeImmutable $dateTime): bool
	{
		return (int) ($dateTime->format('N')) >= 6;
	}
}
