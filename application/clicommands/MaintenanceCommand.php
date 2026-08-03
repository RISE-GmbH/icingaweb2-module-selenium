<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Application\Logger;
use Icinga\Cli\Command;

class MaintenanceCommand extends Command
{
    public function tmpAction()
    {
        $tmpPath = rtrim((string) $this->params->get('path', '/tmp'), '/');
        $minutes = (int) $this->params->get('minutes', 60);
        $dryRun  = (bool) $this->params->get('dry-run', false);
        $verbose = $this->isVerbose;

        if ($minutes < 0) {
            $this->fail('minutes must be >= 0');
        }

        if (!is_dir($tmpPath)) {
            $this->fail(sprintf('Path "%s" is not a directory', $tmpPath));
        }

        if (!is_readable($tmpPath)) {
            $this->fail(sprintf('Path "%s" is not readable', $tmpPath));
        }

        $threshold = time() - ($minutes * 60);

        $patterns = [
            '.org.chromium.',
            '.com.google.Chrome.',
            'com.google.Chrome.',
            'scoped_dir',
        ];

        $deleted = [];
        $matched = [];
        $errors  = [];

        $entries = @scandir($tmpPath);
        if ($entries === false) {
            $this->fail(sprintf('Failed to scan directory "%s"', $tmpPath));
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $tmpPath . DIRECTORY_SEPARATOR . $entry;


            if (!$this->isChromeRelated($entry, $patterns)) {
                if ($verbose) {
                    Logger::info(sprintf(
                        "SKIP  %s \n",
                        $fullPath,

                    ));
                }
                continue;
            }

            $mtime = @filemtime($fullPath);
            if ($mtime === false) {
                $errors[] = sprintf('Could not stat %s', $fullPath);
                continue;
            }

            if ($mtime > $threshold) {
                if ($verbose) {
                    Logger::info(sprintf(
                        "SKIP  %s (age=%d minutes)\n",
                        $fullPath,
                        (int) floor((time() - $mtime) / 60)
                    ));
                }
                continue;
            }

            $matched[] = $fullPath;

            if ($dryRun) {
                Logger::info(sprintf("DRY   %s\n", $fullPath));
                continue;
            }

            try {
                $this->deleteFileOrFolder($fullPath);
                $deleted[] = $fullPath;
                Logger::info(sprintf("DEL   %s\n", $fullPath));
            } catch (\Throwable $e) {
                $errors[] = sprintf('Failed deleting %s: %s', $fullPath, $e->getMessage());
            }
        }

        Logger::info(sprintf(
            "Summary: matched=%d deleted=%d errors=%d dry_run=%s threshold_minutes=%d path=%s\n",
            count($matched),
            count($deleted),
            count($errors),
            $dryRun ? 'yes' : 'no',
            $minutes,
            $tmpPath
        ));

        if (!empty($errors)) {
            foreach ($errors as $error) {
                Logger::error("ERROR " . $error . "\n");
            }
            exit(1);
        }

        exit(0);
    }

    protected function isChromeRelated(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (strpos($name, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function deleteFileOrFolder(string $dir): void
    {
        $real = realpath($dir);

        if ($real === false) {
            throw new \RuntimeException(sprintf('Invalid directory: %s', $dir));
        }

        // Safety: only allow deletion inside /tmp
        if (strpos($real, '/tmp/') !== 0) {
            throw new \RuntimeException(sprintf('Refusing to delete non-/tmp path: %s', $real));
        }

        // Extra safety: avoid deleting /tmp itself
        if ($real === '/tmp') {
            throw new \RuntimeException('Refusing to delete /tmp');
        }

        $cmd = sprintf(
            '/bin/rm -rf -- %s',
            escapeshellarg($real)
        );

        exec($cmd, $output, $rc);

        if ($rc !== 0) {
            throw new \RuntimeException(sprintf(
                'rm -rf failed for %s (rc=%d)',
                $real,
                $rc
            ));
        }
    }
}