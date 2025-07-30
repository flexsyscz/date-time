<?php

declare(strict_types=1);

namespace Flexsyscz\DateTime;

use DateTimeImmutable as DateTimeImmutableNative;
use Nette\Neon\Neon;
use Nextras\Dbal\Utils\DateTimeImmutable;


final class PublicHolidayChecker
{
	/** @var array<DateTimeImmutable[]> */
	private array $publicHolidays;


	/**
	 * @param string|array<string[]>$customConfig
	 * @throws \Exception
	 */
	public function __construct(string|array|null $customConfig = null)
	{
		$this->publicHolidays = [];

		$publicHolidays = is_array($customConfig) ? $customConfig : [];
		if($customConfig === null || is_string($customConfig)) {
			$config = file_get_contents(is_string($customConfig) ? $customConfig : __DIR__ . '/config/publicHolidays.neon');
			if ($config) {
				$publicHolidays = Neon::decode($config);
			}
		}

		foreach ((array)$publicHolidays as $language => $days) {
			$this->publicHolidays[$language] = [];
			foreach ((array) $days as $day) {
				if(is_string($day)) {
					$this->publicHolidays[$language][] = new DateTimeImmutable(date($day));
				}
			}
		}
	}


	public function check(?string $language, DateTimeImmutableNative $dateTime): bool
	{
		if($language && isset($this->publicHolidays[$language])) {
			foreach ($this->publicHolidays[$language] as $publicHoliday) {
				if ($publicHoliday->format('m-d') === $dateTime->format('m-d')) {
					return true;
				}
			}

			$now = DateTimeProvider::now();
			$easter = $now->setTimestamp(easter_date((int) $dateTime->format('Y')));

			foreach (['-2 days', '+1 day'] as $offset) {
				if ($easter->modify($offset)->format('m-d') === $dateTime->format('m-d')) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * @param string $language
	 * @return DateTimeImmutable[]
	 */
	public function getPublicHolidays(string $language): array
	{
		return $this->publicHolidays[$language] ?? [];
	}
}
