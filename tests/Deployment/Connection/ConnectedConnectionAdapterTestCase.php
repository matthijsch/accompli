<?php

namespace Accompli\Test\Deployment\Connection;

/**
 * ConnectedConnectionAdapterTestCase.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
abstract class ConnectedConnectionAdapterTestCase extends ConnectionAdapterTestCase
{
    /**
     * Tests if ConnectionAdapterInterface::disconnect returns false without connection.
     */
    public function testDisconnectReturnsFalseWithoutConnection()
    {
        $this->assertFalse($this->connectionAdapter->disconnect());
    }

    /**
     * Tests if ConnectionAdapterInterface::isConnected returns false when not connected.
     */
    public function testIsConnectedReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->isConnected());
    }

    /**
     * Tests if ConnectionAdapterInterface::isFile returns false when not connected.
     */
    public function testIsFileReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createFile('/test.txt');

        $this->assertFalse($this->connectionAdapter->isFile($this->workspaceUtility->getWorkspacePath().'/test.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::isLink returns false when not connected.
     */
    public function testIsLinkReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createFile('/test.txt');
        symlink($this->workspaceUtility->getWorkspacePath().'/test.txt', $this->workspaceUtility->getWorkspacePath().'/testLink');

        $this->assertFalse($this->connectionAdapter->isLink($this->workspaceUtility->getWorkspacePath().'/testLink'));
    }

    /**
     * Tests if ConnectionAdapterInterface::isDirectory returns false when not connected.
     */
    public function testIsDirectoryReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createDirectory('/existing-directory/');

        $this->assertFalse($this->connectionAdapter->isDirectory($this->workspaceUtility->getWorkspacePath().'/existing-directory'));
    }

    /**
     * Tests if ConnectionAdapterInterface::readLink returns false when not connected.
     */
    public function testReadLinkReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createFile('/test.txt');
        symlink($this->workspaceUtility->getWorkspacePath().'/test.txt', $this->workspaceUtility->getWorkspacePath().'/testLink');

        $this->assertFalse($this->connectionAdapter->readLink($this->workspaceUtility->getWorkspacePath().'/testLink'));
    }

    /**
     * Tests if ConnectionAdapterInterface::changeWorkingDirectory returns false without connection.
     */
    public function testChangeWorkingDirectoryReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->changeWorkingDirectory($this->workspaceUtility->getWorkspacePath()));
    }

    /**
     * Tests if ConnectionAdapterInterface::executeCommand returns ProcessExecutionResult with failure exit code and error output without connection.
     */
    public function testExecuteCommandReturnsFalseWhenNotConnected()
    {
        $result = $this->connectionAdapter->executeCommand('echo test');
        $this->assertInstanceOf('Accompli\Chrono\Process\ProcessExecutionResult', $result);
        $this->assertSame(126, $result->getExitCode());
        $this->assertSame('', $result->getOutput());
        $this->assertSame("Connection adapter not connected.\n", $result->getErrorOutput());
    }

    /**
     * Tests if ConnectionAdapterInterface::getWorkingDirectory returns false without connection.
     */
    public function testGetWorkingDirectoryReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->getWorkingDirectory());
    }

    /**
     * Tests if ConnectionAdapterInterface::getDirectoryContentsList returns false without connection.
     */
    public function testGetDirectoryContentsListReturnsEmtpyArrayWhenNotConnected()
    {
        $this->workspaceUtility->createDirectory('/existing-directory/subdirectory', true);
        $this->workspaceUtility->createFile('/existing-directory/test.txt');

        $result = $this->connectionAdapter->getDirectoryContentsList($this->workspaceUtility->getWorkspacePath().'/existing-directory/');

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    /**
     * Tests if ConnectionAdapterInterface::getContents returns false without connection.
     */
    public function testGetContentsReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->getContents(__DIR__.'/test.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::getFile returns false without connection.
     */
    public function testGetFileReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->getFile(__DIR__.'/test.txt', __DIR__.'/test2.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::createDirectory returns false without connection.
     */
    public function testCreateDirectoryReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->createDirectory($this->workspaceUtility->getWorkspacePath().'/existing-directory'));
        $this->assertFalse(is_dir($this->workspaceUtility->getWorkspacePath().'/existing-directory'));
    }

    /**
     * Tests if ConnectionAdapterInterface::createFile returns false without connection.
     */
    public function testCreateFileReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->createFile($this->workspaceUtility->getWorkspacePath().'/test.txt'));
        $this->assertFileNotExists($this->workspaceUtility->getWorkspacePath().'/test.txt');
    }

    /**
     * Tests if ConnectionAdapterInterface::linkFile returns false without connection.
     */
    public function testLinkReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->link(__DIR__.'/test.txt', __DIR__.'/test2.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::renameFile returns false without connection.
     */
    public function testMoveReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->move(__DIR__.'/test.txt', __DIR__.'/test2.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::copy returns false without connection.
     */
    public function testCopyReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createFile('/test.txt');

        $this->assertFalse($this->connectionAdapter->copy($this->workspaceUtility->getWorkspacePath().'/test.txt', $this->workspaceUtility->getWorkspacePath().'/test2.txt'));
        $this->assertFileNotExists($this->workspaceUtility->getWorkspacePath().'/test2.txt');
    }

    /**
     * Tests if ConnectionAdapterInterface::copy returns false when remote source is not available.
     */
    public function testCopyReturnsFalseWhenRemoteSourceNotAvailable()
    {
        $this->connectionAdapter->connect();

        $this->assertFalse($this->connectionAdapter->copy($this->workspaceUtility->getWorkspacePath().'/test.txt', $this->workspaceUtility->getWorkspacePath().'/test2.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::changeFileMode returns false without connection.
     */
    public function testChangePermissionsReturnsFalseWhenNotConnected()
    {
        $this->workspaceUtility->createFile('/test.txt');

        $this->assertFalse($this->connectionAdapter->changePermissions($this->workspaceUtility->getWorkspacePath().'/test.txt', 0700));
        $this->assertNotSame('0700', substr(sprintf('%o', fileperms($this->workspaceUtility->getWorkspacePath().'/test.txt')), -4));
    }

    /**
     * Tests if ConnectionAdapterInterface::putContents returns false without connection.
     */
    public function testPutContentsReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->putContents(__DIR__.'/test.txt', 'test'));
    }

    /**
     * Tests if ConnectionAdapterInterface::putFile returns false without connection.
     */
    public function testPutFileReturnsFalseWhenNotConnected()
    {
        $this->assertFalse($this->connectionAdapter->putFile(__DIR__.'/test.txt', __DIR__.'/test2.txt'));
    }

    /**
     * Tests if ConnectionAdapterInterface::delete returns false without connection.
     */
    public function testDeleteWhenNotConnected()
    {
        $this->workspaceUtility->createDirectory('/existing-directory/subdirectory', true);
        $this->workspaceUtility->createFile('/existing-directory/test.txt');

        $this->assertFalse($this->connectionAdapter->delete($this->workspaceUtility->getWorkspacePath().'/existing-directory', true));
        $this->assertFileExists($this->workspaceUtility->getWorkspacePath().'/existing-directory');
    }
}
