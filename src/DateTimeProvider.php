<?php
declare(strict_types=1);

namespace Flexsyscz\DateTime;

use Exception;
use Flexsyscz\Localization\TranslatedComponent;
use Nette\Utils\Strings;
use Nextras\Dbal\Utils\DateTimeImmutable;


class DateTimeProvider
{
	use TranslatedComponent;

	/** @var string[] */
	private array $locale;

	/** @var DateTimeImmutable[] */
	private array $publicHolidays;


	/**
	 * @param array|string[] $format
	 * @param array|string[] $publicHolidays
	 * @throws Exception
	 */
	public function __construct(array $format = ['date' => 'j. n. Y', 'time' => 'H:i'], array $publicHolidays = ['Y-01-01', 'Y-05-01', 'Y-05-08', 'Y-07-05', 'Y-07-06', 'Y-09-28', 'Y-10-28', 'Y-11-17', 'Y-12-24', 'Y-12-25', 'Y-12-26'])
	{
		$this->locale['date'] = $format['date'];
		$this->locale['time'] = $format['time'];

		$this->publicHolidays = [];
		foreach ($publicHolidays as $publicHoliday) {
			$this->publicHolidays[] = new DateTimeImmutable(date($publicHoliday));
		}
	}


	public static function now(): DateTimeImmutable
	{
		return new DateTimeImmutable;
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
		foreach ($this->publicHolidays as $publicHoliday) {
			if ($publicHoliday->format('m-d') === $dateTime->format('m-d')) {
				return true;
			}
		}

		$now = self::now();
		$easter = $now->setTimestamp(easter_date((int) $dateTime->format('Y')));

		foreach (['-2 days', '+1 day'] as $offset) {
			if ($easter->modify($offset)->format('m-d') === $dateTime->format('m-d')) {
				return true;
			}
		}

		return false;
	}


	public function isWeekend(DateTimeImmutable $dateTime): bool
	{
		return intval($dateTime->format('N')) >= 6;
	}
}