<?php

namespace Accompli\Test\Deployment\Strategy;

use Accompli\AccompliEvents;
use Accompli\Deployment\Host;
use Accompli\Deployment\Strategy\RemoteInstallStrategy;
use Accompli\EventDispatcher\Event\FailedEvent;
use Accompli\EventDispatcher\Event\HostEvent;
use Accompli\EventDispatcher\Event\InstallReleaseEvent;
use Accompli\EventDispatcher\Event\PrepareReleaseEvent;
use Accompli\EventDispatcher\Event\WorkspaceEvent;
use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\Event;

/**
 * RemoteInstallStrategyTest.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RemoteInstallStrategyTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests if RemoteInstallStrategy::install retrieves the hosts from the configuration through ConfigurationInterface::getHosts.
     */
    public function testInstallWithoutStageRetrievesHostsFromConfiguration()
    {
        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())->method('getHosts')->willReturn(array());
        $configurationMock->expects($this->never())->method('getHostsByStage');

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);

        $this->assertTrue($strategy->install('0.1.0'));
    }

    /**
     * Tests if RemoteInstallStrategy::install retrieves the hosts from the configuration through ConfigurationInterface::getHostsByStage.
     *
     * @depends testInstallWithoutStageRetrievesHostsFromConfiguration
     */
    public function testInstallWithStageRetrievesHostsByStageFromConfiguration()
    {
        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())->method('getHosts')->willReturn(array());
        $configurationMock->expects($this->once())
                ->method('getHostsByStage')
                ->with($this->equalTo(Host::STAGE_TEST))
                ->willReturn(array());

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);

        $this->assertTrue($strategy->install('0.1.0', Host::STAGE_TEST));
    }

    /**
     * Tests if RemoteInstallStrategy::install dispatches all the events successfully.
     *
     * @depends testInstallWithStageRetrievesHostsByStageFromConfiguration
     */
    public function testInstallDispatchesEventsSuccessfully()
    {
        $hostMock = $this->getMockBuilder('Accompli\Deployment\Host')
                ->setConstructorArgs(array(Host::STAGE_TEST, 'local', null, __DIR__))
                ->getMock();

        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())
                ->method('getHostsByStage')
                ->with($this->equalTo(Host::STAGE_TEST))
                ->willReturn(array($hostMock));

        $eventDispatcherMock = $this->getMockBuilder('Accompli\EventDispatcher\EventDispatcherInterface')->getMock();
        $eventDispatcherMock->expects($this->exactly(5))
                ->method('dispatch')
                ->withConsecutive(
                    array(
                        $this->equalTo(AccompliEvents::CREATE_CONNECTION),
                        $this->callback(function ($event) {
                            return ($event instanceof HostEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_WORKSPACE),
                        $this->callback(array($this, 'provideDispatchCallbackForWorkspaceEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_RELEASE),
                        $this->callback(array($this, 'provideDispatchCallbackForPrepareReleaseEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE_COMPLETE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    )
                );

        $outputFormatterMock = $this->getMockBuilder('Symfony\Component\Console\Formatter\OutputFormatterInterface')
                ->getMock();

        $outputMock = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
                ->getMock();
        $outputMock->expects($this->once())
                ->method('getFormatter')
                ->willReturn($outputFormatterMock);

        $loggerMock = $this->getMockBuilder('Accompli\Console\Logger\ConsoleLoggerInterface')
                ->getMock();
        $loggerMock->expects($this->once())
                ->method('getOutput')
                ->willReturn($outputMock);

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);
        $strategy->setEventDispatcher($eventDispatcherMock);
        $strategy->setLogger($loggerMock);

        $this->assertTrue($strategy->install('0.1.0', Host::STAGE_TEST));
    }

    /**
     * Tests if RemoteInstallStrategy::install dispatches all the events successfully for multiple hosts.
     *
     * @depends testInstallDispatchesEventsSuccessfully
     */
    public function testInstallDispatchesEventsSuccessfullyForMultipleHosts()
    {
        $hostMock = $this->getMockBuilder('Accompli\Deployment\Host')
                ->setConstructorArgs(array(Host::STAGE_TEST, 'local', null, __DIR__))
                ->getMock();

        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())
                ->method('getHostsByStage')
                ->willReturn(array($hostMock, $hostMock));

        $eventDispatcherMock = $this->getMockBuilder('Accompli\EventDispatcher\EventDispatcherInterface')->getMock();
        $eventDispatcherMock->expects($this->exactly(10))
                ->method('dispatch')
                ->withConsecutive(
                    array(
                        $this->equalTo(AccompliEvents::CREATE_CONNECTION),
                        $this->callback(function ($event) {
                            return ($event instanceof HostEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_WORKSPACE),
                        $this->callback(array($this, 'provideDispatchCallbackForWorkspaceEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_RELEASE),
                        $this->callback(array($this, 'provideDispatchCallbackForPrepareReleaseEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE_COMPLETE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::CREATE_CONNECTION),
                        $this->callback(function ($event) {
                            return ($event instanceof HostEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_WORKSPACE),
                        $this->callback(array($this, 'provideDispatchCallbackForWorkspaceEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_RELEASE),
                        $this->callback(array($this, 'provideDispatchCallbackForPrepareReleaseEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE_COMPLETE),
                        $this->callback(function ($event) {
                            return ($event instanceof InstallReleaseEvent);
                        }),
                    )
                );

        $outputFormatterMock = $this->getMockBuilder('Symfony\Component\Console\Formatter\OutputFormatterInterface')
                ->getMock();

        $outputMock = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
                ->getMock();
        $outputMock->expects($this->exactly(2))
                ->method('getFormatter')
                ->willReturn($outputFormatterMock);

        $loggerMock = $this->getMockBuilder('Accompli\Console\Logger\ConsoleLoggerInterface')
                ->getMock();
        $loggerMock->expects($this->exactly(2))
                ->method('getOutput')
                ->willReturn($outputMock);

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);
        $strategy->setEventDispatcher($eventDispatcherMock);
        $strategy->setLogger($loggerMock);

        $this->assertTrue($strategy->install('0.1.0', Host::STAGE_TEST));
    }

    /**
     * Tests if RemoteInstallStrategy::install dispatches all the events untill after the PrepareWorkspaceEvent.
     *
     * @depends testInstallDispatchesEventsSuccessfully
     */
    public function testInstallDispatchesEventsSuccessfullyUntillAfterPrepareWorkspaceEvent()
    {
        $hostMock = $this->getMockBuilder('Accompli\Deployment\Host')
                ->setConstructorArgs(array(Host::STAGE_TEST, 'local', null, __DIR__))
                ->getMock();

        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())
                ->method('getHostsByStage')
                ->with($this->equalTo(Host::STAGE_TEST))
                ->willReturn(array($hostMock));

        $eventDispatcherMock = $this->getMockBuilder('Accompli\EventDispatcher\EventDispatcherInterface')->getMock();
        $eventDispatcherMock->expects($this->once())
                ->method('getLastDispatchedEvent')
                ->willReturn(new Event());

        $eventDispatcherMock->expects($this->exactly(4))
                ->method('dispatch')
                ->withConsecutive(
                    array(
                        $this->equalTo(AccompliEvents::CREATE_CONNECTION),
                        $this->callback(function ($event) {
                            return ($event instanceof HostEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_WORKSPACE),
                        $this->callback(array($this, 'provideDispatchCallbackForWorkspaceEvent')),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_RELEASE),
                        $this->callback(function ($event) {
                            return ($event instanceof PrepareReleaseEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE_FAILED),
                        $this->callback(function ($event) {
                            return ($event instanceof FailedEvent);
                        }),
                    )
                );

        $outputFormatterMock = $this->getMockBuilder('Symfony\Component\Console\Formatter\OutputFormatterInterface')
                ->getMock();

        $outputMock = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
                ->getMock();
        $outputMock->expects($this->once())
                ->method('getFormatter')
                ->willReturn($outputFormatterMock);

        $loggerMock = $this->getMockBuilder('Accompli\Console\Logger\ConsoleLoggerInterface')
                ->getMock();
        $loggerMock->expects($this->once())
                ->method('getOutput')
                ->willReturn($outputMock);

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);
        $strategy->setEventDispatcher($eventDispatcherMock);
        $strategy->setLogger($loggerMock);

        $this->assertFalse($strategy->install('0.1.0', Host::STAGE_TEST));
    }

    /**
     * Tests if RemoteInstallStrategy::install dispatches all the events untill after the PrepareReleaseEvent.
     *
     * @depends testInstallDispatchesEventsSuccessfully
     */
    public function testInstallDispatchesEventsSuccessfullyUntillAfterPrepareReleaseEvent()
    {
        $hostMock = $this->getMockBuilder('Accompli\Deployment\Host')
                ->setConstructorArgs(array(Host::STAGE_TEST, 'local', null, __DIR__))
                ->getMock();

        $configurationMock = $this->getMockBuilder('Accompli\Configuration\ConfigurationInterface')->getMock();
        $configurationMock->expects($this->once())
                ->method('getHostsByStage')
                ->with($this->equalTo(Host::STAGE_TEST))
                ->willReturn(array($hostMock));

        $eventDispatcherMock = $this->getMockBuilder('Accompli\EventDispatcher\EventDispatcherInterface')->getMock();
        $eventDispatcherMock->expects($this->once())
                ->method('getLastDispatchedEvent')
                ->willReturn(new Event());

        $eventDispatcherMock->expects($this->exactly(3))
                ->method('dispatch')
                ->withConsecutive(
                    array(
                        $this->equalTo(AccompliEvents::CREATE_CONNECTION),
                        $this->callback(function ($event) {
                            return ($event instanceof HostEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::PREPARE_WORKSPACE),
                        $this->callback(function ($event) {
                            return ($event instanceof WorkspaceEvent);
                        }),
                    ),
                    array(
                        $this->equalTo(AccompliEvents::INSTALL_RELEASE_FAILED),
                        $this->callback(function ($event) {
                            return ($event instanceof FailedEvent);
                        }),
                    )
                );

        $outputFormatterMock = $this->getMockBuilder('Symfony\Component\Console\Formatter\OutputFormatterInterface')
                ->getMock();

        $outputMock = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
                ->getMock();
        $outputMock->expects($this->once())
                ->method('getFormatter')
                ->willReturn($outputFormatterMock);

        $loggerMock = $this->getMockBuilder('Accompli\Console\Logger\ConsoleLoggerInterface')
                ->getMock();
        $loggerMock->expects($this->once())
                ->method('getOutput')
                ->willReturn($outputMock);

        $strategy = new RemoteInstallStrategy();
        $strategy->setConfiguration($configurationMock);
        $strategy->setEventDispatcher($eventDispatcherMock);
        $strategy->setLogger($loggerMock);

        $this->assertFalse($strategy->install('0.1.0', Host::STAGE_TEST));
    }

    /**
     * Provides the dispatch test callback for the PrepareWorkspaceEvent.
     *
     * @see testInstallDispatchesEventsSuccessfully
     *
     * @param Event $event
     *
     * @return bool
     */
    public function provideDispatchCallbackForWorkspaceEvent(Event $event)
    {
        $workspaceMock = $this->getMockBuilder('Accompli\Deployment\Workspace')
                ->setConstructorArgs(array($event->getHost()))
                ->getMock();

        $event->setWorkspace($workspaceMock);

        return ($event instanceof WorkspaceEvent);
    }

    /**
     * Provides the dispatch test callback for the PrepareReleaseEvent.
     *
     * @see testInstallDispatchesEventsSuccessfully
     *
     * @param Event $event
     *
     * @return bool
     */
    public function provideDispatchCallbackForPrepareReleaseEvent(Event $event)
    {
        $releaseMock = $this->getMockBuilder('Accompli\Deployment\Release')
                ->setConstructorArgs(array($event->getVersion()))
                ->getMock();

        $event->setRelease($releaseMock);

        return ($event instanceof PrepareReleaseEvent);
    }
}
