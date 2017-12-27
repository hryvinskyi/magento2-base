<?php
/**
 * Copyright (c) 2017. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

namespace Script\Base\Helper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Contains logic for interacting with the server environment
 */
class Environment
{
    const MAGENTO_ROOT = __DIR__ . '/../../../../../';
    const STATIC_CONTENT_DEPLOY_FLAG = 'var/.static_content_deploy';
    const PRE_DEPLOY_FLAG = self::MAGENTO_ROOT . 'var/.predeploy_in_progress';
    const REGENERATE_FLAG = self::MAGENTO_ROOT . 'var/.regenerate';

    /**
     * Build log file.
     */
    const BUILD_LOG = self::MAGENTO_ROOT . 'var/log/script_deploy.log';

    public $writableDirs = ['var/di', 'var/generation', 'app/etc', 'pub/media', 'var/view_preprocessed'];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new Logger('default');

        $formatter = new LineFormatter();
        $formatter->allowInlineLineBreaks(true);

        $logHandler = (new StreamHandler(static::BUILD_LOG))->setFormatter($formatter);
        $stdOutHandler = (new StreamHandler('php://stdout'))->setFormatter($formatter);

        $this->logger->pushHandler($logHandler);
        $this->logger->pushHandler($stdOutHandler);
    }

    /**
     * Log message to stream.
     *
     * @param string $message The message string.
     * @return void
     */
    public function log($message)
    {
        $this->logger->notice($message);
    }

    public function execute($command)
    {
	    $command .= ' 2>&1';
	    $process = new Process($command);
	    $process->run();

	    return $process->getOutput();
    }

    public function backgroundExecute($command)
    {
        $command = "nohup {$command} 1>/dev/null 2>&1 &";
        $this->log("Execute command in background: $command");
        shell_exec($command);
    }

    public function setStaticDeployInBuild($flag)		
    {
        if ($flag) {
         $this->log('Setting flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
         touch(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        } else {
            if ($this->isStaticDeployInBuild()) {
                    $this->log('Removing flag file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                    unlink(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
                }
        }
    }

    public function isStaticDeployInBuild()
    {
        return file_exists(Environment::MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG);
    }

    public function removeStaticContent()
    {
        // atomic move within pub/static directory
        $staticContentLocation = realpath(Environment::MAGENTO_ROOT . 'pub/static/') . '/';
        $timestamp = time();
        $oldStaticContentLocation = $staticContentLocation . 'old_static_content_' . $timestamp;

        $this->log("Moving out old static content into $oldStaticContentLocation");

        if (!file_exists($oldStaticContentLocation)) {
            mkdir($oldStaticContentLocation);
        }

        $dir = new \DirectoryIterator($staticContentLocation);

        foreach ($dir as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            if (!$fileInfo->isDot() && strpos($fileName, 'old_static_content_') !== 0) {
                $this->log("Rename " . $staticContentLocation . '/' . $fileName . " to " . $oldStaticContentLocation . '/' . $fileName);
                rename($staticContentLocation . '/' . $fileName, $oldStaticContentLocation . '/' . $fileName);
            }
        }

        $this->log("Removing $oldStaticContentLocation in the background");
        $this->backgroundExecute("rm -rf $oldStaticContentLocation");

        $preprocessedLocation = realpath(Environment::MAGENTO_ROOT . 'var') . '/view_preprocessed';
        if (file_exists($preprocessedLocation)) {
            $oldPreprocessedLocation = $preprocessedLocation . '_old_' . $timestamp;
            $this->log("Rename $preprocessedLocation  to $oldPreprocessedLocation");
            rename($preprocessedLocation, $oldPreprocessedLocation);
            $this->log("Removing $oldPreprocessedLocation in the background");
            $this->backgroundExecute("rm -rf $oldPreprocessedLocation");
        }
    }

    public function removePathInBackground($relativePath)
    {
        $fullPath = rtrim(realpath(Environment::MAGENTO_ROOT) . '/' . $relativePath, '/');
        $timestamp = time();
        if (file_exists($fullPath)) {
            $backgroundLocation = $fullPath . '_old_' . $timestamp;
            $this->log("Rename $fullPath  to $backgroundLocation");
            rename($fullPath, $backgroundLocation);
            $this->log("Removing $backgroundLocation in the background");
            $this->backgroundExecute("rm -rf $backgroundLocation");
        }
    }

    public function atomicCopyPath($relativeSourcePath, $relativeDestinationPath) {
        $fullDestinationPath = rtrim(realpath(Environment::MAGENTO_ROOT) . '/' . $relativeDestinationPath, '/');
        $fullSourcePath = rtrim(realpath(Environment::MAGENTO_ROOT) . '/' . $relativeSourcePath, '/');
        $timestamp = time();
        if (file_exists($fullSourcePath)) {
            $tmpLocation = $fullDestinationPath . '_tmp_' . $timestamp;
            $this->log("Copy $fullSourcePath  to $tmpLocation");
            $this->execute(sprintf('bash -c "shopt -s dotglob; cp -R %s/ %s/ || true"', $fullSourcePath, $tmpLocation));
            $this->removePathInBackground($relativeDestinationPath);
            $this->log("Rename $tmpLocation to $fullDestinationPath");
            rename($tmpLocation, $fullDestinationPath);
        }
    }

    private $componentVersions = [];  // We only want to look up each component version once since it shouldn't change

    private function getVersionOfComponent($component) {
        $composerjsonpath = Environment::MAGENTO_ROOT . "/vendor/magento/" .$component . "/composer.json";
        $version = null;
        try {
            if (file_exists($composerjsonpath)) {
                $jsondata = json_decode(file_get_contents($composerjsonpath), true);
                if (array_key_exists("version", $jsondata)) {
                    $version = $jsondata["version"];
                }
            }
        } catch (\Exception $e) {
            // If we get an exception (or error), we don't worry because we just won't use the version.
            // Note: We could use Throwable to catch them both, but that only works in PHP >= 7
        }
        $this->componentVersions[$component] = $version;
    }

    public function versionOfComponent($component) {
        if (! array_key_exists( $component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }
        return $this->componentVersions[$component];
    }

    public function hasVersionOfComponent($component) {
        if (! array_key_exists( $component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }
        return ! is_null($this->componentVersions[$component]);
    }

    public function startingMessage($starttype)
    {
        $componentsWeCareAbout = ["magento2-base"];
        $message = "Starting " . $starttype . ".";
        $first = true;
        foreach ($componentsWeCareAbout as $component) {
            if ($this->hasVersionOfComponent($component)) {
                if ($first) {
                    $first = false;
                    $message .= " (";
                } else {
                    $message .= ", ";
                }
                $message .= $component . " version: " . $this->versionOfComponent($component);
            }
        }
        if (!$first) {
            $message .= ")";
        }
        return $message;
    }
}
