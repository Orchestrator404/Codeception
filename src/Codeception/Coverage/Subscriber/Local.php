<?php

declare(strict_types=1);

namespace Codeception\Coverage\Subscriber;

use Codeception\Coverage\Filter;
use Codeception\Coverage\SuiteSubscriber;
use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Exception\ConfigurationException;
use Codeception\Exception\ModuleException;
use Codeception\Lib\Interfaces\Remote;
use Codeception\Stub;
use Exception;
use PHPUnit\Runner\CodeCoverage as PHPUnitCodeCoverage;
use PHPUnit\Runner\Version as PHPUnitVersion;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter as CodeCoverageFilter;

/**
 * Collects code coverage from unit and functional tests.
 * Results from all suites are merged.
 */
class Local extends SuiteSubscriber
{
    /**
     * @var array<string, string>
     */
    public static array $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::SUITE_AFTER  => 'afterSuite',
    ];

    protected ?Remote $module = null;

    protected function isEnabled(): bool
    {
        return !$this->module instanceof Remote && $this->settings['enabled'];
    }

    /**
     * @throws ConfigurationException|ModuleException|Exception
     */
    public function beforeSuite(SuiteEvent $event): void
    {
        $this->applySettings($event->getSettings());
        $this->module = $this->getServerConnectionModule($event->getSuite()->getModules());
        if (!$this->isEnabled()) {
            return;
        }

        $event->getSuite()->collectCodeCoverage(true);

        $result = $event->getResult();

        if (PHPUnitVersion::series() < 10) {
            $driver = Stub::makeEmpty('SebastianBergmann\CodeCoverage\Driver\Driver');
            $result->setCodeCoverage(new CodeCoverage($driver, new CodeCoverageFilter()));
        }

        Filter::setup($this->coverage)
            ->whiteList($this->filters)
            ->blackList($this->filters);

        if (PHPUnitVersion::series() < 10) {
            $result->setCodeCoverage($this->coverage);
        }
    }

    public function afterSuite(SuiteEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (PHPUnitVersion::series() < 10) {
            $codeCoverage = $event->getResult()->getCodeCoverage();
        } else {
            $codeCoverage = PHPUnitCodeCoverage::instance();
            PHPUnitCodeCoverage::deactivate();
        }

        $this->mergeToPrint($codeCoverage);
    }
}
