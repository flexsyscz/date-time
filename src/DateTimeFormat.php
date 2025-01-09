<?php

declare(strict_types=1);

namespace Flexsyscz\DateTime;


enum DateTimeFormat: string
{
	case Date = 'j. n. Y';
	case DateTime = 'j. n. Y H:i:s';
	case DateTimeWoSecs = 'j. n. Y H:i';
	case Ymd = 'Y-m-d';
	case YmdHis = 'Y-m-d H:i:s';
	case Time = 'H:i:s';
	case TimeWoSecs = 'H:i';
}
